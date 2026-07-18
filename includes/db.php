<?php

declare(strict_types=1);

function storage_log(string $message): void
{
    $dir = __DIR__ . '/../storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

function db_port_open(): bool
{
    static $open = null;
    if ($open !== null) {
        return $open;
    }

    $config = require __DIR__ . '/../config/database.php';
    $host = $config['host'] === 'localhost' ? '127.0.0.1' : $config['host'];
    $errno = 0;
    $errstr = '';
    $socket = @fsockopen($host, (int) $config['port'], $errno, $errstr, 1);
    if (is_resource($socket)) {
        fclose($socket);
        $open = true;
    } else {
        $open = false;
    }
    return $open;
}

/**
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!db_port_open()) {
        throw new RuntimeException('База данных недоступна.');
    }

    $config = require __DIR__ . '/../config/database.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['dbname'],
        $config['charset'] ?? 'utf8mb4'
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if (defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')) {
        $options[PDO::MYSQL_ATTR_CONNECT_TIMEOUT] = 3;
    }

    try {
        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (Throwable $e) {
        storage_log('DB connect failed: ' . $e->getMessage());
        throw new RuntimeException('Не удалось подключиться к базе данных.');
    }

    return $pdo;
}

function db_available(): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    try {
        db()->query('SELECT 1');
        $ok = true;
    } catch (Throwable $e) {
        storage_log('DB unavailable: ' . $e->getMessage());
        $ok = false;
    }
    return $ok;
}
