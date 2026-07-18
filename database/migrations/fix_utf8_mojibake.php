<?php

declare(strict_types=1);

/**
 * Aggressive UTF-8 mojibake repair (CP1251 double-encoding).
 * Usage from project root: php database/migrations/fix_utf8_mojibake.php
 */

require_once __DIR__ . '/../../includes/db.php';

function fix_mojibake(?string $s): ?string
{
    if ($s === null || $s === '') {
        return $s;
    }

    // Try decode as if UTF-8 text is actually CP1251-encoded view of UTF-8 bytes.
    $bytes = @iconv('UTF-8', 'Windows-1251//IGNORE', $s);
    if ($bytes === false || $bytes === '' || !mb_check_encoding($bytes, 'UTF-8')) {
        return $s;
    }

    // Accept only if we gain Cyrillic and the candidate looks healthier.
    $origCyr = preg_match_all('/[А-Яа-яЁё]/u', $s) ?: 0;
    $newCyr = preg_match_all('/[А-Яа-яЁё]/u', $bytes) ?: 0;
    $origMojibakeMarks = preg_match_all('/Р.|С.|Т.|У.|Ф.|Х.|Ч.|Ш.|Щ.|Ь.|Э.|Ю.|Я./u', $s) ?: 0;
    $newMojibakeMarks = preg_match_all('/Р.|С.|Т.|У.|Ф.|Х.|Ч.|Ш.|Щ.|Ь.|Э.|Ю.|Я./u', $bytes) ?: 0;

    if ($newCyr > $origCyr && $newMojibakeMarks <= $origMojibakeMarks) {
        return $bytes;
    }

    // Also accept when original has no Cyrillic letters but lots of Р/С sequences,
    // and fixed text has Cyrillic.
    if ($origCyr === 0 && $newCyr > 0 && (str_contains($s, 'Р') || str_contains($s, 'С'))) {
        return $bytes;
    }

    return $s;
}

$pdo = db();
$pdo->exec('SET NAMES utf8mb4');

$tables = [
    'categories' => ['name', 'description'],
    'dishes' => ['name', 'short_description', 'description', 'ingredients'],
    'pages' => ['title', 'subtitle', 'content', 'meta_title', 'meta_description'],
    'gallery' => ['title'],
    'settings' => ['setting_value'],
    'opening_hours' => ['day_name'],
    'category_translations' => ['name', 'description'],
    'dish_translations' => ['name', 'short_description', 'description', 'ingredients'],
    'page_translations' => ['title', 'subtitle', 'content', 'meta_title', 'meta_description'],
];

$updated = 0;
foreach ($tables as $table => $cols) {
    try {
        $stmt = $pdo->query('SELECT * FROM `' . $table . '`');
    } catch (Throwable $e) {
        echo $table . ": skip (" . $e->getMessage() . ")\n";
        continue;
    }
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $sets = [];
        $params = [];
        foreach ($cols as $col) {
            if (!isset($row[$col]) || !is_string($row[$col])) {
                continue;
            }
            $fixed = fix_mojibake($row[$col]);
            if ($fixed !== null && $fixed !== $row[$col]) {
                $sets[] = "`$col` = ?";
                $params[] = $fixed;
            }
        }
        if ($sets === []) {
            continue;
        }
        $params[] = $row['id'];
        $pdo->prepare('UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
        $updated++;
    }
    echo $table . ": ok\n";
}

echo "Updated rows: $updated\n";
