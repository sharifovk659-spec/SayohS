<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/Restarant/';
$_SERVER['SCRIPT_NAME'] = '/Restarant/index.php';
$_SERVER['HTTPS'] = 'off';
ob_start();
include __DIR__ . '/../index.php';
$html = ob_get_clean();
$checks = [
    'hero--premium',
    'hero-leaf',
    'home.css',
    'header-cta--call',
    'hero-fresh-card',
    'site-header--compact',
];
foreach ($checks as $c) {
    echo (str_contains($html, $c) ? '[OK] ' : '[FAIL] ') . $c . PHP_EOL;
}
echo 'bytes=' . strlen($html) . PHP_EOL;
