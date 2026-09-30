<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/marketplace.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !wz_is_logged_in()
    || wz_role() !== 'host'
    || !wz_csrf_valid($_POST['csrf'] ?? null)
) {
    http_response_code(401);

    echo json_encode([
        'ok' => false,
    ]);

    exit;
}

$businessUserId = (int)($_POST['business_user_id'] ?? 0);
$businessType = (string)($_POST['business_type'] ?? '');
$eventType = (string)($_POST['event_type'] ?? '');

wz_marketplace_record_business_event(
    $businessUserId,
    $businessType,
    $eventType,
    (int)(wz_user()['id'] ?? 0)
);

echo json_encode([
    'ok' => true,
]);
