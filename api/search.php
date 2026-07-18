<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$query = trim((string) ($_GET['q'] ?? ''));
if ($query === '' || mb_strlen($query) > 120) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$lang = current_lang();
$fallback = AROMA_LANG_DEFAULT;
$like = '%' . $query . '%';

try {
    $stmt = db()->prepare(
        'SELECT DISTINCT d.id, d.slug, d.price, d.image, d.is_available, d.is_popular,
                COALESCE(NULLIF(dt.name, ""), NULLIF(dtr.name, ""), d.name) AS name,
                COALESCE(NULLIF(dt.short_description, ""), NULLIF(dtr.short_description, ""), d.short_description) AS short_description
         FROM dishes d
         LEFT JOIN dish_translations dt ON dt.dish_id = d.id AND dt.language_code = ?
         LEFT JOIN dish_translations dtr ON dtr.dish_id = d.id AND dtr.language_code = ?
         WHERE d.is_available = 1
           AND (
                d.name LIKE ? OR d.short_description LIKE ? OR d.ingredients LIKE ?
                OR dt.name LIKE ? OR dt.short_description LIKE ? OR dt.ingredients LIKE ?
                OR dtr.name LIKE ? OR dtr.short_description LIKE ? OR dtr.ingredients LIKE ?
           )
         ORDER BY d.is_popular DESC, name ASC
         LIMIT 12'
    );
    $stmt->execute([
        $lang,
        $fallback,
        $like, $like, $like,
        $like, $like, $like,
        $like, $like, $like,
    ]);
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    storage_log('api/search: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'search_failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$results = array_map(static function (array $row): array {
    return [
        'id' => (int) $row['id'],
        'slug' => (string) $row['slug'],
        'name' => (string) $row['name'],
        'short_description' => (string) ($row['short_description'] ?? ''),
        'price' => (float) $row['price'],
        'image' => dish_image_url($row['image'] ?? null),
        'url' => base_url('dish.php?slug=' . rawurlencode((string) $row['slug'])),
        'is_popular' => (int) ($row['is_popular'] ?? 0) === 1,
    ];
}, $rows);

echo json_encode($results, JSON_UNESCAPED_UNICODE);
