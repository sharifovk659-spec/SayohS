<?php

declare(strict_types=1);

/**
 * Production DB config template for Hostinger.
 * Copy to config/database.php on the server and fill real credentials.
 * Never commit real passwords.
 */
return [
    'host' => 'localhost', // Hostinger often uses localhost or a host like mysql.hostinger.com
    'port' => '3306',
    'dbname' => 'CHANGE_ME_DB_NAME',
    'username' => 'CHANGE_ME_DB_USER',
    'password' => 'CHANGE_ME_DB_PASSWORD',
    'charset' => 'utf8mb4',
];
