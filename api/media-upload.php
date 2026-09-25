<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/media.php';

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store'
);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'message' => 'POST required.',
    ]);

    exit;
}

if (
    !wz_is_logged_in()
    || !in_array(
        wz_role(),
        ['vendor', 'admin'],
        true
    )
) {
    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'message' => 'Sign in before uploading media.',
    ]);

    exit;
}

if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
    http_response_code(419);

    echo json_encode([
        'ok' => false,
        'message' => 'Session token expired.',
    ]);

    exit;
}

$result = wz_media_upload(
    $_FILES['image'] ?? [],
    (string)($_POST['alt_text'] ?? '')
);

if (!$result['ok']) {
    http_response_code(422);

    echo json_encode($result);

    exit;
}

if (
    wz_role() === 'vendor'
    && !empty(wz_user()['id'])
) {
    wz_vendor_append_media(
        (int)wz_user()['id'],
        (string)$result['path']
    );
}

echo json_encode($result);
