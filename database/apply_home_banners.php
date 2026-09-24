<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$sqlFile = __DIR__ . '/migrations/20260924_home_banners.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Missing migration file.\n");
    exit(1);
}

if (!db_available()) {
    fwrite(STDERR, "Database unavailable. Start MySQL and check config/database.php\n");
    exit(1);
}

$raw = (string) file_get_contents($sqlFile);
$parts = array_filter(array_map('trim', preg_split('/;\s*\n/', $raw) ?: []));
$ok = 0;
foreach ($parts as $stmt) {
    if ($stmt === '' || str_starts_with($stmt, '--')) {
        continue;
    }
    try {
        db()->exec($stmt);
        $ok++;
    } catch (Throwable $e) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "OK: home_banners migration applied ($ok statements).\n";
