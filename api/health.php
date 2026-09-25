<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store'
);

$expectedToken = trim(
    (string)(
        wz_config()['operations']['health_token']
        ?? ''
    )
);

$providedToken = trim(
    (string)(
        $_SERVER['HTTP_X_WZ_HEALTH_TOKEN']
        ?? ''
    )
);

if (
    $expectedToken === ''
    || !hash_equals(
        $expectedToken,
        $providedToken
    )
) {
    http_response_code(404);

    echo json_encode([
        'ok' => false,
    ]);

    exit;
}

$databaseReady = wz_database_ready();

if (!$databaseReady) {
    http_response_code(503);
}

echo json_encode([
    'ok' => $databaseReady,
    'database' => $databaseReady
        ? 'ready'
        : 'unavailable',
    'php' => PHP_VERSION,
    'time' => date(DATE_ATOM),
]);
