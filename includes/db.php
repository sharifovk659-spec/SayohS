<?php

declare(strict_types=1);

/**
 * Load DB config: local database.php, else example + environment variables (Vercel).
 *
 * @return array{host:string,port:string,dbname:string,username:string,password:string,charset:string}
 */
function db_config(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }

    $local = __DIR__ . '/../config/database.php';
    $example = __DIR__ . '/../config/database.example.php';

    if (is_file($local)) {
        /** @var array $config */
        $config = require $local;
    } elseif (is_file($example)) {
        /** @var array $config */
        $config = require $example;
    } else {
        $config = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'dbname' => 'aroma_restaurant',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ];
    }

    $envMap = [
        'host' => 'DB_HOST',
        'port' => 'DB_PORT',
        'dbname' => 'DB_NAME',
        'username' => 'DB_USER',
        'password' => 'DB_PASS',
        'charset' => 'DB_CHARSET',
    ];
    foreach ($envMap as $key => $env) {
        $val = getenv($env);
        if ($val !== false && $val !== '') {
            $config[$key] = $val;
        }
    }

    return $config;
}

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

    try {
        $config = db_config();
        $host = $config['host'] === 'localhost' ? '127.0.0.1' : (string) $config['host'];
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, (int) $config['port'], $errno, $errstr, 1);
        if (is_resource($socket)) {
            fclose($socket);
            $open = true;
        } else {
            $open = false;
        }
    } catch (Throwable $e) {
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

    $config = db_config();
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
        $pdo = new PDO($dsn, (string) $config['username'], (string) $config['password'], $options);
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
