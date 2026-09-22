<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/database.php';

header('Content-Type: application/xml; charset=utf-8');

function wz_xml(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_XML1 | ENT_QUOTES,
        'UTF-8'
    );
}

$urls = [
    wz_app_url(''),
    wz_app_url('vendors.php'),
    wz_app_url('inspiration.php'),
    wz_app_url('real-weddings.php'),
    wz_app_url('blog.php'),
    wz_app_url('planner.php'),
    wz_app_url('invites.php'),
    wz_app_url('about.php'),
    wz_app_url('contact.php'),
    wz_app_url('register-vendor.php'),
];

foreach (wz_data('cities') as $city) {
    $urls[] = wz_app_url(
        'city.php?city=' . urlencode($city)
    );
}

foreach (wz_data('event_types') as $event) {
    $urls[] = wz_app_url(
        'event.php?type=' . urlencode(
            (string)$event['name']
        )
    );
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
echo "\n";

foreach (array_unique($urls) as $url) {
    echo '    <url>';
    echo '<loc>';
    echo wz_xml($url);
    echo '</loc>';
    echo '</url>';
    echo "\n";
}

echo '</urlset>';
echo "\n";
