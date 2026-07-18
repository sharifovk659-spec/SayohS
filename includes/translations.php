<?php

declare(strict_types=1);

/**
 * Content translations (categories, dishes, pages) with Russian fallback.
 */

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function apply_dish_translation(array $row, ?string $lang = null): array
{
    $lang = $lang ? strtolower($lang) : current_lang();
    $id = (int) ($row['id'] ?? 0);
    if ($id <= 0 || $lang === AROMA_LANG_DEFAULT) {
        // Still try load if RU translation table has overrides; else keep columns.
        if ($lang === AROMA_LANG_DEFAULT) {
            $tr = fetch_dish_translation($id, 'ru');
            if ($tr) {
                $row['name'] = $tr['name'] ?: $row['name'];
                $row['short_description'] = $tr['short_description'] ?? ($row['short_description'] ?? null);
                $row['description'] = $tr['short_description'] ?? ($row['description'] ?? '');
                $row['full_description'] = $tr['description'] ?? ($row['full_description'] ?? ($row['description'] ?? ''));
                $row['ingredients'] = $tr['ingredients'] ?? ($row['ingredients'] ?? null);
            }
            return $row;
        }
    }

    $tr = fetch_dish_translation($id, $lang) ?: fetch_dish_translation($id, AROMA_LANG_DEFAULT);
    if ($tr) {
        $row['name'] = $tr['name'] !== '' ? $tr['name'] : ($row['name'] ?? '');
        $short = (string) ($tr['short_description'] ?? '');
        $full = (string) ($tr['description'] ?? '');
        $row['short_description'] = $short !== '' ? $short : ($row['short_description'] ?? null);
        $row['description'] = $short !== '' ? $short : (string) ($row['description'] ?? '');
        $row['full_description'] = $full !== '' ? $full : (string) ($row['full_description'] ?? ($row['description'] ?? ''));
        if (!empty($tr['ingredients'])) {
            $row['ingredients'] = $tr['ingredients'];
        }
    }
    return $row;
}

function fetch_dish_translation(int $dishId, string $lang): ?array
{
    static $cache = [];
    $key = $dishId . ':' . $lang;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $stmt = db()->prepare(
            'SELECT name, short_description, description, ingredients
             FROM dish_translations WHERE dish_id = ? AND language_code = ? LIMIT 1'
        );
        $stmt->execute([$dishId, $lang]);
        $row = $stmt->fetch();
        $cache[$key] = $row ?: null;
    } catch (Throwable $e) {
        $cache[$key] = null;
    }
    return $cache[$key];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function apply_category_translation(array $row, ?string $lang = null): array
{
    $lang = $lang ? strtolower($lang) : current_lang();
    $id = (int) ($row['id'] ?? 0);
    if ($id <= 0) {
        return $row;
    }
    $tr = fetch_category_translation($id, $lang) ?: fetch_category_translation($id, AROMA_LANG_DEFAULT);
    if ($tr) {
        if ($tr['name'] !== '') {
            $row['name'] = $tr['name'];
        }
        if ($tr['description'] !== null && $tr['description'] !== '') {
            $row['description'] = $tr['description'];
        }
    }
    return $row;
}

function fetch_category_translation(int $categoryId, string $lang): ?array
{
    static $cache = [];
    $key = $categoryId . ':' . $lang;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $stmt = db()->prepare(
            'SELECT name, description FROM category_translations
             WHERE category_id = ? AND language_code = ? LIMIT 1'
        );
        $stmt->execute([$categoryId, $lang]);
        $row = $stmt->fetch();
        $cache[$key] = $row ?: null;
    } catch (Throwable $e) {
        $cache[$key] = null;
    }
    return $cache[$key];
}

/**
 * @param array<string, mixed>|null $row
 * @return array<string, mixed>|null
 */
function apply_page_translation(?array $row, ?string $lang = null): ?array
{
    if ($row === null) {
        return null;
    }
    $lang = $lang ? strtolower($lang) : current_lang();
    $id = (int) ($row['id'] ?? 0);
    if ($id <= 0) {
        return $row;
    }
    $tr = fetch_page_translation($id, $lang) ?: fetch_page_translation($id, AROMA_LANG_DEFAULT);
    if ($tr) {
        foreach (['title', 'subtitle', 'content', 'meta_title', 'meta_description'] as $field) {
            if (!empty($tr[$field])) {
                $row[$field] = $tr[$field];
            }
        }
    }
    return $row;
}

function fetch_page_translation(int $pageId, string $lang): ?array
{
    static $cache = [];
    $key = $pageId . ':' . $lang;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $stmt = db()->prepare(
            'SELECT title, subtitle, content, meta_title, meta_description
             FROM page_translations WHERE page_id = ? AND language_code = ? LIMIT 1'
        );
        $stmt->execute([$pageId, $lang]);
        $row = $stmt->fetch();
        $cache[$key] = $row ?: null;
    } catch (Throwable $e) {
        $cache[$key] = null;
    }
    return $cache[$key];
}

function translated_setting(string $key, ?string $default = null, ?string $lang = null): ?string
{
    $lang = $lang ? strtolower($lang) : current_lang();
    try {
        $stmt = db()->prepare(
            'SELECT setting_value FROM setting_translations
             WHERE setting_key = ? AND language_code = ? LIMIT 1'
        );
        $stmt->execute([$key, $lang]);
        $val = $stmt->fetchColumn();
        if (is_string($val) && $val !== '') {
            return $val;
        }
        if ($lang !== AROMA_LANG_DEFAULT) {
            $stmt->execute([$key, AROMA_LANG_DEFAULT]);
            $val = $stmt->fetchColumn();
            if (is_string($val) && $val !== '') {
                return $val;
            }
        }
    } catch (Throwable $e) {
        // table may not exist yet
    }
    return setting($key, $default);
}
