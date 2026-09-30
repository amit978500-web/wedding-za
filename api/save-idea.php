<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!wz_is_logged_in() || wz_role() !== 'host') {
    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'message' => 'Sign in as a customer to sync moodboards.',
    ]);

    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !wz_csrf_valid($_POST['csrf'] ?? null)
) {
    http_response_code(419);

    echo json_encode([
        'ok' => false,
        'message' => 'Session expired.',
    ]);

    exit;
}

$pdo = wz_db();
$userId = (int)(wz_user()['id'] ?? 0);

if (!$pdo || $userId <= 0) {
    http_response_code(503);

    echo json_encode([
        'ok' => false,
        'message' => 'Database is not available.',
    ]);

    exit;
}

$sourceKey = trim(
    (string)($_POST['source_key'] ?? '')
);

$action = (string)($_POST['action'] ?? 'save');

if ($sourceKey === '') {
    http_response_code(422);

    echo json_encode([
        'ok' => false,
        'message' => 'Missing idea.',
    ]);

    exit;
}

if ($action === 'remove') {
    $statement = $pdo->prepare(
        'DELETE FROM saved_ideas
         WHERE user_id = :user_id
         AND source_type = "inspiration"
         AND source_key = :source_key'
    );

    $statement->execute([
        'user_id' => $userId,
        'source_key' => $sourceKey,
    ]);

    echo json_encode([
        'ok' => true,
        'message' => 'Idea removed from moodboard.',
    ]);

    exit;
}

$boardStatement = $pdo->prepare(
    'SELECT id
     FROM saved_idea_boards
     WHERE user_id = :user_id
     ORDER BY id ASC
     LIMIT 1'
);

$boardStatement->execute([
    'user_id' => $userId,
]);

$boardId = $boardStatement->fetchColumn();

if (!$boardId) {
    $createBoard = $pdo->prepare(
        'INSERT INTO saved_idea_boards (
            user_id,
            name,
            description
        ) VALUES (
            :user_id,
            "My Wedding Ideas",
            "Ideas saved from Wedding Za inspiration."
        )'
    );

    $createBoard->execute([
        'user_id' => $userId,
    ]);

    $boardId = (int)$pdo->lastInsertId();
}

$statement = $pdo->prepare(
    'INSERT INTO saved_ideas (
        board_id,
        user_id,
        source_type,
        source_key,
        title,
        image_url,
        source_url
    ) VALUES (
        :board_id,
        :user_id,
        "inspiration",
        :source_key,
        :title,
        :image_url,
        :source_url
    )
    ON DUPLICATE KEY UPDATE
        title = VALUES(title),
        image_url = VALUES(image_url),
        source_url = VALUES(source_url)'
);

$statement->execute([
    'board_id' => (int)$boardId,
    'user_id' => $userId,
    'source_key' => $sourceKey,
    'title' => trim((string)($_POST['title'] ?? 'Saved idea')),
    'image_url' => trim((string)($_POST['image_url'] ?? '')) ?: null,
    'source_url' => trim((string)($_POST['source_url'] ?? '')) ?: null,
]);

echo json_encode([
    'ok' => true,
    'message' => 'Idea saved to your Wedding Za moodboard.',
]);
