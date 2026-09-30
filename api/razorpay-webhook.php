<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/payments.php';

$payload = file_get_contents('php://input') ?: '';
$signature = (string)(
    $_SERVER['HTTP_X_RAZORPAY_SIGNATURE']
    ?? ''
);

if (!wz_razorpay_verify_webhook(
    $payload,
    $signature
)) {
    http_response_code(401);
    echo 'Invalid signature';
    exit;
}

$event = json_decode(
    $payload,
    true
);

if (!is_array($event)) {
    http_response_code(400);
    echo 'Invalid payload';
    exit;
}

$eventName = (string)($event['event'] ?? '');
$payment = $event['payload']['payment']['entity'] ?? null;

if (
    $eventName === 'payment.captured'
    && is_array($payment)
) {
    $orderId = (string)($payment['order_id'] ?? '');
    $paymentId = (string)($payment['id'] ?? '');

    if ($orderId !== '' && $paymentId !== '') {
        $pdo = wz_db();

        if ($pdo) {
            $statement = $pdo->prepare(
                'UPDATE venue_plan_purchases
                 SET status = CASE
                        WHEN status = "pending" THEN "paid"
                        ELSE status
                     END,
                     payment_provider = "razorpay",
                     provider_payment_id = :provider_payment_id,
                     paid_at = COALESCE(paid_at, NOW())
                 WHERE provider_order_id = :provider_order_id'
            );

            $statement->execute([
                'provider_payment_id' => $paymentId,
                'provider_order_id' => $orderId,
            ]);
        }
    }
}

http_response_code(200);
echo 'ok';
