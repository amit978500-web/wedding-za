<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/marketplace.php';
require_once dirname(__DIR__) . '/includes/payments.php';

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
        'message' => 'Authentication required.',
    ]);

    exit;
}

$user = wz_user() ?? [];
$userId = (int)($user['id'] ?? 0);
$purchaseId = wz_marketplace_create_venue_plan_purchase(
    $userId
);

if (!$purchaseId) {
    http_response_code(503);

    echo json_encode([
        'ok' => false,
        'message' => 'Could not create venue plan purchase.',
    ]);

    exit;
}

if (!wz_razorpay_is_configured()) {
    echo json_encode([
        'ok' => false,
        'configured' => false,
        'purchase_id' => $purchaseId,
        'message' => 'Razorpay keys are not configured yet.',
    ]);

    exit;
}

$order = wz_razorpay_create_order(
    100000,
    'wz-venue-plan-' . $purchaseId,
    [
        'purchase_id' => (string)$purchaseId,
        'customer_user_id' => (string)$userId,
        'plan' => 'one_wedding_venue_assist',
    ]
);

if (empty($order['ok'])) {
    http_response_code(502);

    echo json_encode($order);

    exit;
}

$orderData = $order['order'];
$pdo = wz_db();

$statement = $pdo->prepare(
    'UPDATE venue_plan_purchases
     SET payment_provider = "razorpay",
         provider_order_id = :provider_order_id
     WHERE id = :id
     AND customer_user_id = :customer_user_id'
);

$statement->execute([
    'provider_order_id' => (string)$orderData['id'],
    'id' => $purchaseId,
    'customer_user_id' => $userId,
]);

$config = wz_razorpay_config();

echo json_encode([
    'ok' => true,
    'configured' => true,
    'purchase_id' => $purchaseId,
    'key_id' => (string)$config['razorpay_key_id'],
    'order_id' => (string)$orderData['id'],
    'amount' => (int)$orderData['amount'],
    'currency' => (string)$orderData['currency'],
    'customer' => [
        'name' => (string)($user['name'] ?? ''),
        'email' => (string)($user['email'] ?? ''),
    ],
]);
