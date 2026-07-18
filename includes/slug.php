<?php

declare(strict_types=1);

function slugify(string $text): string
{
    $text = trim(mb_strtolower($text, 'UTF-8'));

    $map = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
        'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];

    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?? '';
    $text = trim($text, '-');
    $text = preg_replace('/-+/', '-', $text) ?? '';

    return $text !== '' ? $text : 'item';
}

function unique_slug(string $table, string $slug, ?int $excludeId = null): string
{
    $allowed = ['categories', 'dishes'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Invalid table for slug.');
    }

    $base = slugify($slug);
    $candidate = $base;
    $i = 2;

    $sql = "SELECT id FROM {$table} WHERE slug = ?";
    if ($excludeId !== null) {
        $sql .= ' AND id <> ?';
    }
    $sql .= ' LIMIT 1';

    while (true) {
        $stmt = db()->prepare($sql);
        $params = [$candidate];
        if ($excludeId !== null) {
            $params[] = $excludeId;
        }
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $base . '-' . $i;
        $i++;
        if ($i > 500) {
            return $base . '-' . bin2hex(random_bytes(3));
        }
    }
}
