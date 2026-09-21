<?php
declare(strict_types=1);

const WZ_ROOT = __DIR__ . '/..';
$raw = file_get_contents(WZ_ROOT . '/assets/data/site.json');
$WZ = json_decode($raw ?: '{}', true) ?: [];

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function wz_data(string $key, array $fallback = []): array { global $WZ; return is_array($WZ[$key] ?? null) ? $WZ[$key] : $fallback; }
function wz_find(string $collection, string $id): ?array {
    foreach (wz_data($collection) as $item) if ((string)($item['id'] ?? '') === $id) return $item;
    return null;
}
function wz_vendor(string $id): ?array { return wz_find('vendors', $id); }
function wz_wedding(string $id): ?array { return wz_find('weddings', $id); }
function wz_article(string $id): ?array { return wz_find('articles', $id); }
function wz_category_by_name(string $name): ?array {
    foreach (wz_data('categories') as $item) if (strcasecmp((string)($item['name'] ?? ''), $name) === 0) return $item;
    return null;
}
function wz_slug(string $value): string { return strtolower(trim((string)preg_replace('/[^a-z0-9]+/i','-', $value), '-')); }
function wz_active(string $needle): string {
    $path = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'index.php');
    return $path === $needle ? ' aria-current="page" class="is-active"' : '';
}
function wz_money_number(string $price): int {
    preg_match('/[\d,]+/', $price, $m); return isset($m[0]) ? (int)str_replace(',', '', $m[0]) : 0;
}
