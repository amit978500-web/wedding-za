<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

function wz_razorpay_config(): array
{
    return wz_config()['payments'] ?? [];
}

function wz_razorpay_is_configured(): bool
{
    $config = wz_razorpay_config();

    return trim((string)($config['razorpay_key_id'] ?? '')) !== ''
        && trim((string)($config['razorpay_key_secret'] ?? '')) !== '';
}

function wz_razorpay_create_order(
    int $amountPaise,
    string $receipt,
    array $notes = []
): array {
    if (!wz_razorpay_is_configured()) {
        return [
            'ok' => false,
            'message' => 'Razorpay is not configured.',
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'message' => 'PHP cURL extension is required.',
        ];
    }

    $config = wz_razorpay_config();

    $payload = json_encode([
        'amount' => $amountPaise,
        'currency' => 'INR',
        'receipt' => $receipt,
        'notes' => $notes,
    ]);

    $curl = curl_init(
        'https://api.razorpay.com/v1/orders'
    );

    curl_setopt_array(
        $curl,
        [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD =>
                (string)$config['razorpay_key_id']
                . ':'
                . (string)$config['razorpay_key_secret'],
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
        ]
    );

    $response = curl_exec($curl);
    $statusCode = (int)curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );

    $curlError = curl_error($curl);

    curl_close($curl);

    if ($response === false || $curlError !== '') {
        return [
            'ok' => false,
            'message' => 'Could not connect to Razorpay.',
        ];
    }

    $data = json_decode(
        (string)$response,
        true
    );

    if (
        $statusCode < 200
        || $statusCode >= 300
        || !is_array($data)
        || empty($data['id'])
    ) {
        return [
            'ok' => false,
            'message' => (string)(
                $data['error']['description']
                ?? 'Could not create payment order.'
            ),
        ];
    }

    return [
        'ok' => true,
        'order' => $data,
    ];
}

function wz_razorpay_verify_payment_signature(
    string $orderId,
    string $paymentId,
    string $signature
): bool {
    $config = wz_razorpay_config();
    $secret = (string)(
        $config['razorpay_key_secret']
        ?? ''
    );

    if (
        $orderId === ''
        || $paymentId === ''
        || $signature === ''
        || $secret === ''
    ) {
        return false;
    }

    $expected = hash_hmac(
        'sha256',
        $orderId . '|' . $paymentId,
        $secret
    );

    return hash_equals(
        $expected,
        $signature
    );
}

function wz_razorpay_verify_webhook(
    string $payload,
    string $signature
): bool {
    $config = wz_razorpay_config();
    $secret = (string)(
        $config['razorpay_webhook_secret']
        ?? ''
    );

    if ($secret === '' || $signature === '') {
        return false;
    }

    $expected = hash_hmac(
        'sha256',
        $payload,
        $secret
    );

    return hash_equals(
        $expected,
        $signature
    );
}
