<?php

declare(strict_types=1);

/**
 * Vercel front controller — routes all non-static requests to project PHP files.
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = is_string($uri) ? rawurldecode($uri) : '/';
if ($uri === '') {
    $uri = '/';
}

$root = dirname(__DIR__);
$self = realpath(__FILE__) ?: __FILE__;

// Normalize /api router hits to home
if ($uri === '/api' || $uri === '/api/' || $uri === '/api/index.php') {
    $uri = '/';
}

$file = $root . DIRECTORY_SEPARATOR . 'index.php';

if ($uri !== '/') {
    $rel = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uri), DIRECTORY_SEPARATOR);
    $candidate = $root . DIRECTORY_SEPARATOR . $rel;

    if (is_dir($candidate) && is_file($candidate . DIRECTORY_SEPARATOR . 'index.php')) {
        $file = $candidate . DIRECTORY_SEPARATOR . 'index.php';
    } elseif (is_file($candidate) && str_ends_with(strtolower($candidate), '.php')) {
        $file = $candidate;
    } elseif (is_file($candidate . '.php')) {
        $file = $candidate . '.php';
    }
}

$resolved = realpath($file);
if ($resolved === false || $resolved === $self) {
    $file = $root . DIRECTORY_SEPARATOR . 'index.php';
} else {
    $file = $resolved;
}

$_SERVER['SCRIPT_FILENAME'] = $file;
$_SERVER['SCRIPT_NAME'] = '/' . ltrim(str_replace('\\', '/', substr($file, strlen($root))), '/');

chdir($root);
require $file;
