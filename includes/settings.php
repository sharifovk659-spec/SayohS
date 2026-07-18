<?php

declare(strict_types=1);

/**
 * @return array{loaded:bool,data:array<string,string>}
 */
function &settings_cache_state(): array
{
    static $state = ['loaded' => false, 'data' => []];

    return $state;
}

/**
 * @return array<string, string>
 */
function all_settings(bool $reload = false): array
{
    $state = &settings_cache_state();

    if ($reload) {
        $state['loaded'] = false;
        $state['data'] = [];
    }

    if ($state['loaded']) {
        return $state['data'];
    }

    $state['loaded'] = true;
    $state['data'] = [];

    if (!db_available()) {
        return $state['data'];
    }

    try {
        $stmt = db()->query('SELECT setting_key, setting_value FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $state['data'][(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
    } catch (Throwable $e) {
        storage_log('settings load: ' . $e->getMessage());
    }

    return $state['data'];
}

/** @return array<string, string> */
function settings_all(): array
{
    return all_settings();
}

function clear_settings_cache(): void
{
    all_settings(true);
}

function setting(string $key, ?string $default = null): ?string
{
    $all = all_settings();

    return array_key_exists($key, $all) ? $all[$key] : $default;
}

function get_setting(string $key, ?string $default = null): ?string
{
    return setting($key, $default);
}

function setting_int(string $key, int $default = 0): int
{
    return (int) (setting($key, (string) $default) ?? $default);
}

/**
 * @param array<string, string|null> $pairs
 */
function save_settings(array $pairs): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value, setting_type, updated_at)
         VALUES (:k, :v, :t, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type), updated_at = NOW()'
    );

    foreach ($pairs as $key => $value) {
        $value = (string) ($value ?? '');
        $type = 'string';
        if (mb_strlen($value) > 255) {
            $type = 'text';
        } elseif ($value !== '' && preg_match('/^-?\d+$/', $value)) {
            $type = 'int';
        } elseif (in_array(mb_strtolower($value), ['0', '1', 'true', 'false'], true)) {
            $type = 'bool';
        }

        $stmt->execute([
            'k' => (string) $key,
            'v' => $value,
            't' => $type,
        ]);
    }

    clear_settings_cache();
}
