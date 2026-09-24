<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

header('Content-Type: text/plain; charset=UTF-8');

$sitemap = absolute_url('sitemap.php');

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /actions/\n";
echo "Disallow: /config/\n";
echo "Disallow: /database/\n";
echo "Disallow: /storage/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /handlers/\n";
echo "Disallow: /scripts/\n";
echo "Disallow: /sql/\n";
echo "Disallow: /account/\n";
echo "Disallow: /api/\n\n";

echo "User-agent: Googlebot\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /actions/\n";
echo "Disallow: /config/\n";
echo "Disallow: /database/\n";
echo "Disallow: /storage/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /handlers/\n";
echo "Disallow: /scripts/\n";
echo "Disallow: /sql/\n";
echo "Disallow: /account/\n";
echo "Disallow: /api/\n\n";

echo 'Sitemap: ' . $sitemap . "\n";
