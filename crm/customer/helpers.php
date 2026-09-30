<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

function wz_customer_workspace(int $userId): array
{
    $workspace = [
        'brief' => [],
        'tasks' => [],
        'budget' => [],
        'shortlist' => [],
    ];

    $pdo = wz_db();

    if (!$pdo) {
        return $workspace;
    }

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

    if (!$row) {
        return $workspace;
    }

    $workspace['brief'] = json_decode(
        (string)($row['brief_json'] ?? '{}'),
        true
    ) ?: [];

    $workspace['tasks'] = json_decode(
        (string)($row['tasks_json'] ?? '[]'),
        true
    ) ?: [];

    $workspace['budget'] = json_decode(
        (string)($row['budget_json'] ?? '{}'),
        true
    ) ?: [];

    $workspace['shortlist'] = json_decode(
        (string)($row['shortlist_json'] ?? '[]'),
        true
    ) ?: [];

    return $workspace;
}

function wz_customer_save_workspace(
    int $userId,
    array $workspace
): void {
    $pdo = wz_db();

    if (!$pdo) {
        return;
    }

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
        'brief_json' => json_encode(
            $workspace['brief'] ?? []
        ),
        'tasks_json' => json_encode(
            $workspace['tasks'] ?? []
        ),
        'budget_json' => json_encode(
            $workspace['budget'] ?? []
        ),
        'shortlist_json' => json_encode(
            $workspace['shortlist'] ?? []
        ),
    ]);
}

function wz_customer_profile_row(int $userId): array
{
    $pdo = wz_db();

    if (!$pdo) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT *
         FROM customer_profiles
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $statement->execute([
        'user_id' => $userId,
    ]);

    return $statement->fetch() ?: [];
}
