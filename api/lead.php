<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'message' => 'POST required',
    ]);

    exit;
}

if (!empty($_POST['company_website'] ?? '')) {
    echo json_encode([
        'ok' => true,
        'message' => 'Thanks — we received it.',
    ]);

    exit;
}

function clean(string $key, int $max = 1200): string
{
    $value = trim((string)($_POST[$key] ?? ''));

    $value = preg_replace(
        '/[\r\n\t]+/',
        ' ',
        $value
    ) ?? '';

    if (function_exists('mb_substr')) {
        return mb_substr(
            $value,
            0,
            $max
        );
    }

    return substr(
        $value,
        0,
        $max
    );
}

$type = clean('type', 60) ?: 'general';
$name = clean('name', 120);
$email = clean('email', 180);
$phone = clean('phone', 80);
$city = clean('city', 100);
$message = clean('message', 1500);

$requiredNameTypes = [
    'contact',
    'vendor-enquiry',
    'vendor-registration',
    'wedding-submission',
];

if (
    in_array($type, $requiredNameTypes, true)
    && $name === ''
) {
    http_response_code(422);

    echo json_encode([
        'ok' => false,
        'message' => 'Please add your name.',
    ]);

    exit;
}

if (
    $email !== ''
    && !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    http_response_code(422);

    echo json_encode([
        'ok' => false,
        'message' => 'Please enter a valid email address.',
    ]);

    exit;
}

$payload = [
    'user_id' => wz_user()['id'] ?? null,
    'type' => $type,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'city' => $city,
    'vendor' => clean('vendor', 150),
    'business' => clean('business', 150),
    'category' => clean('category', 120),
    'event_date' => clean('event_date', 40),
    'topic' => clean('topic', 120),
    'price' => clean('price', 120),
    'portfolio' => clean('portfolio', 400),
    'vendors' => clean('vendors', 700),
    'message' => $message,
    'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
];

$pdo = wz_db();

if ($pdo) {
    $statement = $pdo->prepare(
        'INSERT INTO leads (
            user_id,
            type,
            name,
            email,
            phone,
            city,
            vendor,
            business,
            category,
            event_date,
            topic,
            price,
            portfolio,
            vendors,
            message,
            ip
        ) VALUES (
            :user_id,
            :type,
            :name,
            :email,
            :phone,
            :city,
            :vendor,
            :business,
            :category,
            :event_date,
            :topic,
            :price,
            :portfolio,
            :vendors,
            :message,
            :ip
        )'
    );

    $statement->execute([
        'user_id' => $payload['user_id'],
        'type' => $payload['type'],
        'name' => $payload['name'],
        'email' => $payload['email'],
        'phone' => $payload['phone'],
        'city' => $payload['city'],
        'vendor' => $payload['vendor'],
        'business' => $payload['business'],
        'category' => $payload['category'],
        'event_date' => $payload['event_date'] !== ''
            ? $payload['event_date']
            : null,
        'topic' => $payload['topic'],
        'price' => $payload['price'],
        'portfolio' => $payload['portfolio'],
        'vendors' => $payload['vendors'],
        'message' => $payload['message'],
        'ip' => $payload['ip'],
    ]);

    if (
        $type === 'vendor-registration'
        && wz_role() === 'vendor'
        && !empty($payload['user_id'])
        && $payload['business'] !== ''
    ) {
        $profile = $pdo->prepare(
            'INSERT INTO vendor_profiles (
                user_id,
                business_name,
                category,
                city,
                events_json,
                starting_price,
                portfolio_url,
                about
            ) VALUES (
                :user_id,
                :business_name,
                :category,
                :city,
                :events_json,
                :starting_price,
                :portfolio_url,
                :about
            )
            ON DUPLICATE KEY UPDATE
                business_name = VALUES(business_name),
                category = VALUES(category),
                city = VALUES(city),
                events_json = VALUES(events_json),
                starting_price = VALUES(starting_price),
                portfolio_url = VALUES(portfolio_url),
                about = VALUES(about)'
        );

        $profile->execute([
            'user_id' => $payload['user_id'],
            'business_name' => $payload['business'],
            'category' => $payload['category'],
            'city' => $payload['city'],
            'events_json' => json_encode([
                $payload['topic'],
            ]),
            'starting_price' => $payload['price'],
            'portfolio_url' => $payload['portfolio'],
            'about' => $payload['message'],
        ]);
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Thanks — your details were received.',
        'storage' => 'database',
    ]);

    exit;
}

$storageDirectory = dirname(__DIR__) . '/storage';

if (!is_dir($storageDirectory)) {
    @mkdir(
        $storageDirectory,
        0775,
        true
    );
}

$file = $storageDirectory . '/leads.csv';

$row = [
    date('c'),
    $payload['type'],
    $payload['name'],
    $payload['email'],
    $payload['phone'],
    $payload['city'],
    $payload['vendor'],
    $payload['business'],
    $payload['category'],
    $payload['event_date'],
    $payload['topic'],
    $payload['price'],
    $payload['portfolio'],
    $payload['vendors'],
    $payload['message'],
    $payload['ip'],
];

$exists = file_exists($file);
$handle = @fopen($file, 'ab');

if (!$handle) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'message' => 'The server could not save this submission. Check storage permissions.',
    ]);

    exit;
}

flock(
    $handle,
    LOCK_EX
);

if (!$exists) {
    fputcsv(
        $handle,
        [
            'created_at',
            'type',
            'name',
            'email',
            'phone',
            'city',
            'vendor',
            'business',
            'category',
            'event_date',
            'topic',
            'price',
            'portfolio',
            'vendors',
            'message',
            'ip',
        ]
    );
}

fputcsv(
    $handle,
    $row
);

flock(
    $handle,
    LOCK_UN
);

fclose($handle);

echo json_encode([
    'ok' => true,
    'message' => 'Thanks — your details were received.',
    'storage' => 'csv',
]);
