<?php

return [
    'app_url' => 'https://your-domain.com',

    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'wedding_za',
        'user' => 'wedding_za_user',
        'password' => 'change-me',
        'charset' => 'utf8mb4',
    ],

    'analytics' => [
        'measurement_id' => '',
    ],

    'payments' => [
        'razorpay_key_id' => '',
        'razorpay_key_secret' => '',
        'razorpay_webhook_secret' => '',
    ],

    'seo' => [
        'organization_name' => 'Wedding Za',
        'default_og_image' => '',
        'contact_email' => '',
        'contact_phone' => '',
    ],

    'notifications' => [
        // Optional external delivery webhook. It receives a JSON payload
        // and can route email through Resend/SendGrid and push through
        // OneSignal/FCM or another provider.
        'delivery_webhook_url' => '',
        'delivery_webhook_token' => '',
        'email_enabled' => false,
        'push_enabled' => false,
        'from_email' => '',
        'from_name' => 'Wedding Za',
    ],

    'operations' => [
        'health_token' => '',
    ],
];
