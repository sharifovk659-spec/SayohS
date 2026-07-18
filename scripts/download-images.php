<?php

declare(strict_types=1);

/**
 * Downloads free Unsplash images as local WebP (sized via Unsplash params).
 * Sources: Unsplash (license allows free use). No hotlinking.
 */

function ensure_dir(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

function download(string $url, string $dest): bool
{
    $ch = curl_init($url);
    if ($ch === false) {
        return false;
    }

    $fp = fopen($dest, 'wb');
    if ($fp === false) {
        curl_close($ch);
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 40,
        CURLOPT_USERAGENT => 'AromaRestaurantLocalDownloader/1.0',
        CURLOPT_FAILONERROR => true,
    ]);

    $ok = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if ($ok === false || $code >= 400 || !is_file($dest) || filesize($dest) < 1000) {
        if (is_file($dest)) {
            unlink($dest);
        }
        return false;
    }

    return true;
}

function unsplash(string $photoId, int $w, int $q = 72): string
{
    return "https://images.unsplash.com/{$photoId}?auto=format&fit=crop&w={$w}&q={$q}&fm=webp";
}

$root = dirname(__DIR__);
$dirs = [
    $root . '/assets/images/hero',
    $root . '/assets/images/dishes',
    $root . '/assets/images/categories',
    $root . '/assets/images/gallery',
];
foreach ($dirs as $dir) {
    ensure_dir($dir);
}

// Photo IDs from Unsplash (food / restaurant interiors)
$map = [
    // hero ~1200w
    'hero/hero-main.webp' => unsplash('photo-1517248135467-4c7edcad34c4', 1200, 70),
    'hero/about-preview.webp' => unsplash('photo-1559339352-11d035aa65de', 1000, 72),
    'hero/about-main.webp' => unsplash('photo-1414235077428-338989a2e8c0', 1000, 72),

    // categories thumbs ~400
    'categories/cat-pizza.webp' => unsplash('photo-1513104890138-7c749659a591', 400, 70),
    'categories/cat-burgers.webp' => unsplash('photo-1568901346375-23c9450c58cd', 400, 70),
    'categories/cat-shawarma.webp' => unsplash('photo-1529006557810-274b9b2fc783', 400, 70),
    'categories/cat-grill.webp' => unsplash('photo-1558030006-45067529262a', 400, 70),
    'categories/cat-salads.webp' => unsplash('photo-1512621776951-a57141f2eefd', 400, 70),
    'categories/cat-drinks.webp' => unsplash('photo-1544145945-f90425340c7e', 400, 70),
    'categories/cat-desserts.webp' => unsplash('photo-1578985545062-69928b1d9587', 400, 70),
    'categories/placeholder.webp' => unsplash('photo-1414235077428-338989a2e8c0', 400, 65),

    // dishes ~800
    'dishes/pizza-margherita.webp' => unsplash('photo-1574071318508-1cdbab80d002', 800, 72),
    'dishes/pizza-pepperoni.webp' => unsplash('photo-1628840042765-356cda07504e', 800, 72),
    'dishes/burger-classic.webp' => unsplash('photo-1568901346375-23c9450c58cd', 800, 72),
    'dishes/burger-bacon.webp' => unsplash('photo-1550547660-d9450f859349', 800, 72),
    'dishes/shawarma.webp' => unsplash('photo-1529006557810-274b9b2fc783', 800, 72),
    'dishes/grill-steak.webp' => unsplash('photo-1600891964092-4316c288032e', 800, 72),
    'dishes/salad-caesar.webp' => unsplash('photo-1546793665-c74683f339c1', 800, 72),
    'dishes/salad-greek.webp' => unsplash('photo-1540189549336-e6e99c3679fe', 800, 72),
    'dishes/lemonade.webp' => unsplash('photo-1523677011783-c91d1bbe2fdc', 800, 72),
    'dishes/fondant.webp' => unsplash('photo-1606313564200-e75d5e30476c', 800, 72),
    'dishes/tiramisu.webp' => unsplash('photo-1571877227200-a0d98ea607e9', 800, 72),
    'dishes/grill-veg.webp' => unsplash('photo-1540420773420-3366772f4999', 800, 72),
    'dishes/placeholder.webp' => unsplash('photo-1504674900247-0877df9cc836', 640, 68),

    // gallery ~900
    'gallery/gal-interior.webp' => unsplash('photo-1555396273-367ea4eb4db5', 900, 72),
    'gallery/gal-pizza.webp' => unsplash('photo-1513104890138-7c749659a591', 900, 72),
    'gallery/gal-hot.webp' => unsplash('photo-1504674900247-0877df9cc836', 900, 72),
    'gallery/gal-drinks.webp' => unsplash('photo-1551024709-8f23befc6f87', 900, 72),
    'gallery/gal-dessert.webp' => unsplash('photo-1488477181946-6428a0291777', 900, 72),
    'gallery/gal-team.webp' => unsplash('photo-1577219491135-ce391730fb2c', 900, 72),
    'gallery/gal-event.webp' => unsplash('photo-1510812431401-41d2bd2722f3', 900, 72),
    'gallery/gal-table.webp' => unsplash('photo-1414235077428-338989a2e8c0', 900, 72),
    'gallery/placeholder.webp' => unsplash('photo-1517248135467-4c7edcad34c4', 700, 68),
];

$ok = 0;
$fail = 0;
foreach ($map as $rel => $url) {
    $dest = $root . '/assets/images/' . $rel;
    if (is_file($dest) && filesize($dest) > 2000) {
        echo "SKIP {$rel}\n";
        $ok++;
        continue;
    }
    if (download($url, $dest)) {
        $size = round(filesize($dest) / 1024, 1);
        echo "OK {$rel} ({$size} KB)\n";
        $ok++;
    } else {
        echo "FAIL {$rel}\n";
        $fail++;
    }
}

echo "Done. ok={$ok} fail={$fail}\n";
