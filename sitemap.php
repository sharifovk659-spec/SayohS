<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/xml; charset=UTF-8');

$base = rtrim((string) app_config('public_url', ''), '/');
if ($base === '') {
    $candidate = rtrim(base_url(), '/');
    if (str_starts_with($candidate, 'http://') || str_starts_with($candidate, 'https://')) {
        $base = $candidate;
    } else {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = $scheme . '://' . $host . $candidate;
    }
}

$static = [
    ['loc' => $base . '/', 'changefreq' => 'weekly', 'priority' => '1.0'],
    ['loc' => $base . '/menu.php', 'changefreq' => 'daily', 'priority' => '0.9'],
    ['loc' => $base . '/gallery.php', 'changefreq' => 'weekly', 'priority' => '0.7'],
    ['loc' => $base . '/about.php', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['loc' => $base . '/reservation.php', 'changefreq' => 'monthly', 'priority' => '0.8'],
    ['loc' => $base . '/contacts.php', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['loc' => $base . '/privacy.php', 'changefreq' => 'yearly', 'priority' => '0.3'],
    ['loc' => $base . '/terms.php', 'changefreq' => 'yearly', 'priority' => '0.3'],
];

$urls = $static;

try {
    if (db_available()) {
        $stmt = db()->query(
            'SELECT slug, updated_at FROM dishes WHERE is_available = 1 ORDER BY sort_order ASC, id ASC LIMIT 500'
        );
        foreach ($stmt->fetchAll() as $row) {
            $urls[] = [
                'loc' => $base . '/dish.php?slug=' . rawurlencode((string) $row['slug']),
                'changefreq' => 'weekly',
                'priority' => '0.7',
                'lastmod' => !empty($row['updated_at'])
                    ? date('Y-m-d', strtotime((string) $row['updated_at']))
                    : null,
            ];
        }
    }
} catch (Throwable) {
    // sitemap still returns static pages
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    if (!empty($u['lastmod'])) {
        echo '    <lastmod>' . htmlspecialchars((string) $u['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
    }
    echo '    <changefreq>' . htmlspecialchars($u['changefreq'], ENT_XML1, 'UTF-8') . "</changefreq>\n";
    echo '    <priority>' . htmlspecialchars($u['priority'], ENT_XML1, 'UTF-8') . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>';
