from pathlib import Path

p = Path(r"c:\xampp\htdocs\Restarant\includes\repository.php")
t = p.read_text(encoding="utf-8")

mapper = '''
function map_dish_public(array $row): array
{
    $row = normalize_dish_row($row);
    if (function_exists('apply_dish_translation')) {
        $row = apply_dish_translation($row);
    }
    if (!empty($row['category_id']) && function_exists('apply_category_translation') && isset($row['category_name'])) {
        $cat = apply_category_translation([
            'id' => (int) $row['category_id'],
            'name' => (string) $row['category_name'],
        ]);
        $row['category_name'] = $cat['name'] ?? $row['category_name'];
    }
    return $row;
}

function map_category_public(array $row): array
{
    if (function_exists('apply_category_translation')) {
        $row = apply_category_translation($row);
    }
    return $row;
}

'''

if "function map_dish_public" not in t:
    anchor = "function cached_active_categories(): array"
    idx = t.find(anchor)
    if idx == -1:
        raise SystemExit("anchor not found")
    t = t[:idx] + mapper + t[idx:]
    print("inserted mappers")
else:
    print("mappers already present")

t = t.replace("array_map('normalize_dish_row'", "array_map('map_dish_public'")
t = t.replace("normalize_dish_row($row)", "map_dish_public($row)")
t = t.replace("normalize_dish_row($dish)", "map_dish_public($dish)")
t = t.replace("normalize_dish_row($item)", "map_dish_public($item)")
t = t.replace(
    "return $row ? normalize_dish_row($row) : null;",
    "return $row ? map_dish_public($row) : null;",
)

# Avoid double-normalize inside map_dish_public if we replaced normalize inside mapper body
# Fix mapper body: first call should remain normalize_dish_row
t = t.replace(
    "function map_dish_public(array $row): array\n{\n    $row = map_dish_public($row);",
    "function map_dish_public(array $row): array\n{\n    $row = normalize_dish_row($row);",
)

# Categories: map after fetch
old_cat = "$cache = $stmt->fetchAll();\n    } catch (Throwable $e) {\n        storage_log('cached_active_categories:"
new_cat = "$cache = array_map('map_category_public', $stmt->fetchAll());\n    } catch (Throwable $e) {\n        storage_log('cached_active_categories:"
if old_cat in t:
    t = t.replace(old_cat, new_cat, 1)
    print("mapped categories query")

t = t.replace(
    "$cache = catalog_data()['categories'];",
    "$cache = array_map('map_category_public', catalog_data()['categories']);",
)

# fetch_page translation
if "apply_page_translation" not in t and "function fetch_page(" in t:
    t = t.replace(
        "return $stmt->fetch() ?: null;",
        "\$row = \$stmt->fetch() ?: null;\n        return function_exists('apply_page_translation') ? apply_page_translation(\$row) : \$row;",
        1,
    )

p.write_text(t, encoding="utf-8", newline="\n")
print("map_dish_public count", t.count("map_dish_public"))
print("php lint will follow")
