<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!wz_is_logged_in() || wz_role() !== 'host') {
    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'message' => 'Sign in as a host to sync your planning workspace.',
    ]);

    exit;
}

$pdo = wz_db();

if (!$pdo || empty(wz_user()['id'])) {
    echo json_encode([
        'ok' => true,
        'configured' => false,
        'workspace' => null,
    ]);

    exit;
}

$userId = (int)wz_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $statement = $pdo->prepare(
        'SELECT brief_json, tasks_json, budget_json, shortlist_json
         FROM event_workspaces
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $statement->execute([
        'user_id' => $userId,
    ]);

    $row = $statement->fetch();

    $workspace = [
        'brief' => [],
        'tasks' => [],
        'budget' => [],
        'shortlist' => [],
    ];

    if ($row) {
        $workspace['brief'] = json_decode(
            (string)($row['brief_json'] ?? '[]'),
            true
        ) ?: [];

        $workspace['tasks'] = json_decode(
            (string)($row['tasks_json'] ?? '[]'),
            true
        ) ?: [];

        $workspace['budget'] = json_decode(
            (string)($row['budget_json'] ?? '[]'),
            true
        ) ?: [];

        $workspace['shortlist'] = json_decode(
            (string)($row['shortlist_json'] ?? '[]'),
            true
        ) ?: [];
    }

    echo json_encode([
        'ok' => true,
        'configured' => true,
        'workspace' => $workspace,
    ]);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'message' => 'GET or POST required.',
    ]);

    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '{}', true);

if (!is_array($payload)) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'message' => 'Invalid JSON payload.',
    ]);

    exit;
}

if (!wz_csrf_valid($payload['csrf'] ?? null)) {
    http_response_code(419);

    echo json_encode([
        'ok' => false,
        'message' => 'Session token expired.',
    ]);

    exit;
}

$brief = is_array($payload['brief'] ?? null)
    ? $payload['brief']
    : [];

$tasks = is_array($payload['tasks'] ?? null)
    ? $payload['tasks']
    : [];

$budget = is_array($payload['budget'] ?? null)
    ? $payload['budget']
    : [];

$shortlist = is_array($payload['shortlist'] ?? null)
    ? $payload['shortlist']
    : [];

$statement = $pdo->prepare(
    'INSERT INTO event_workspaces (
        user_id,
        brief_json,
        tasks_json,
        budget_json,
        shortlist_json
    ) VALUES (
        :user_id,
        :brief_json,
        :tasks_json,
        :budget_json,
        :shortlist_json
    )
    ON DUPLICATE KEY UPDATE
        brief_json = VALUES(brief_json),
        tasks_json = VALUES(tasks_json),
        budget_json = VALUES(budget_json),
        shortlist_json = VALUES(shortlist_json)'
);

$statement->execute([
    'user_id' => $userId,
    'brief_json' => json_encode($brief),
    'tasks_json' => json_encode($tasks),
    'budget_json' => json_encode($budget),
    'shortlist_json' => json_encode($shortlist),
]);

echo json_encode([
    'ok' => true,
    'configured' => true,
    'message' => 'Workspace synced.',
]);
