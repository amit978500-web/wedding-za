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

$orderId = trim(
    (string)($_POST['razorpay_order_id'] ?? '')
);

$paymentId = trim(
    (string)($_POST['razorpay_payment_id'] ?? '')
);

$signature = trim(
    (string)($_POST['razorpay_signature'] ?? '')
);

$userId = (int)(wz_user()['id'] ?? 0);
$pdo = wz_db();

if (
    !$pdo
    || !wz_razorpay_verify_payment_signature(
        $orderId,
        $paymentId,
        $signature
    )
) {
    http_response_code(422);

    echo json_encode([
        'ok' => false,
        'message' => 'Payment verification failed.',
    ]);

    exit;
}

$statement = $pdo->prepare(
    'UPDATE venue_plan_purchases
     SET status = "paid",
         payment_provider = "razorpay",
         provider_payment_id = :provider_payment_id,
         paid_at = COALESCE(paid_at, NOW())
     WHERE customer_user_id = :customer_user_id
     AND provider_order_id = :provider_order_id'
);

$statement->execute([
    'provider_payment_id' => $paymentId,
    'customer_user_id' => $userId,
    'provider_order_id' => $orderId,
]);

if ($statement->rowCount() < 1) {
    http_response_code(404);

    echo json_encode([
        'ok' => false,
        'message' => 'Payment order was not found.',
    ]);

    exit;
}

$admins = $pdo->query(
    'SELECT id
     FROM users
     WHERE role = "admin"
     AND status = "active"'
)->fetchAll();

foreach ($admins as $admin) {
    wz_marketplace_notify(
        (int)$admin['id'],
        'venue_plan',
        'Paid venue assistance plan',
        (string)(wz_user()['name'] ?? 'Customer')
            .' paid ₹1,000 for One Wedding venue assistance.',
        null
    );
}

echo json_encode([
    'ok' => true,
    'message' => 'Payment verified. Wedding Za support will activate the plan.',
]);
