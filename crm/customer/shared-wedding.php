<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = 'host';
$crmPage = 'planning-collaborators';
$crmTitle = 'Shared Wedding';
$crmUser = wz_crm_require_role('host');
$userId = (int)$crmUser['id'];
$ownerId = (int)($_GET['owner_id'] ?? 0);
$pdo = wz_db();
$access = null;
$profile = [];
$workspace = [];

if ($pdo && $ownerId > 0) {
    $statement = $pdo->prepare(
        'SELECT wc.*, u.name AS owner_name
         FROM wedding_collaborators wc
         INNER JOIN users u
            ON u.id = wc.owner_user_id
         WHERE wc.owner_user_id = :owner_user_id
         AND wc.collaborator_user_id = :collaborator_user_id
         AND wc.status = "accepted"
         LIMIT 1'
    );

    $statement->execute([
        'owner_user_id' => $ownerId,
        'collaborator_user_id' => $userId,
    ]);

    $access = $statement->fetch() ?: null;

    if ($access) {
        $profileStatement = $pdo->prepare(
            'SELECT *
             FROM customer_profiles
             WHERE user_id = :user_id
             LIMIT 1'
        );

        $profileStatement->execute([
            'user_id' => $ownerId,
        ]);

        $profile = $profileStatement->fetch() ?: [];

        $workspaceStatement = $pdo->prepare(
            'SELECT *
             FROM event_workspaces
             WHERE user_id = :user_id
             LIMIT 1'
        );

        $workspaceStatement->execute([
            'user_id' => $ownerId,
        ]);

        $workspaceRow = $workspaceStatement->fetch() ?: [];

        $workspace = [
            'brief' => json_decode(
                (string)($workspaceRow['brief_json'] ?? '{}'),
                true
            ) ?: [],
            'budget' => json_decode(
                (string)($workspaceRow['budget_json'] ?? '{}'),
                true
            ) ?: [],
            'shortlist' => json_decode(
                (string)($workspaceRow['shortlist_json'] ?? '[]'),
                true
            ) ?: [],
        ];
    }
}

$functions = is_array($workspace['brief']['functions'] ?? null)
    ? $workspace['brief']['functions']
    : [];

$planned = 0;
$spent = 0;

foreach (($workspace['budget'] ?? []) as $key => $value) {
    if (str_ends_with((string)$key, ':planned')) {
        $planned += (float)$value;
    }

    if (str_ends_with((string)$key, ':spent')) {
        $spent += (float)$value;
    }
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">SHARED WEDDING</span>
        <h1>
            <?= $access
                ? h((string)$access['owner_name']).'’s wedding'
                : 'Shared access unavailable' ?>
        </h1>
        <p>
            <?= $access
                ? 'You have '.h((string)$access['permission']).' access to this shared planning summary.'
                : 'This workspace is available only to accepted collaborators.' ?>
        </p>
    </div>
</div>

<?php if (!$access): ?>
    <section class="crm-panel">
        <div class="crm-empty">
            You do not currently have access to this wedding workspace.
        </div>
    </section>
<?php else: ?>
    <div class="crm-grid">
        <article class="crm-stat">
            <span>Event type</span>
            <strong style="font-size:28px;">
                <?= h((string)($profile['event_type'] ?: 'Not set')) ?>
            </strong>
        </article>

        <article class="crm-stat">
            <span>Wedding date</span>
            <strong style="font-size:28px;">
                <?= h((string)($profile['event_date'] ?: 'Not set')) ?>
            </strong>
        </article>

        <article class="crm-stat">
            <span>Guests</span>
            <strong><?= h((string)($profile['guest_count'] ?: '—')) ?></strong>
        </article>

        <article class="crm-stat">
            <span>Saved options</span>
            <strong><?= h((string)count($workspace['shortlist'] ?? [])) ?></strong>
        </article>
    </div>

    <section class="crm-panel">
        <div class="crm-panel-head">
            <div>
                <h2>Functions</h2>
                <p><?= h((string)count($functions)) ?> planned function(s)</p>
            </div>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>Function</th>
                        <th>Date</th>
                        <th>Guests</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($functions as $function): ?>
                        <tr>
                            <td><?= h((string)($function['name'] ?? 'Function')) ?></td>
                            <td><?= h((string)($function['date'] ?? '—')) ?></td>
                            <td><?= h((string)($function['guests'] ?? '—')) ?></td>
                            <td><?= h((string)($function['notes'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$functions): ?>
                        <tr>
                            <td colspan="4">No functions added yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="crm-grid">
        <article class="crm-stat">
            <span>Budget range</span>
            <strong style="font-size:24px;">
                <?= h((string)($profile['budget'] ?: 'Not set')) ?>
            </strong>
        </article>

        <article class="crm-stat">
            <span>Planned</span>
            <strong style="font-size:28px;">₹<?= h(number_format($planned)) ?></strong>
        </article>

        <article class="crm-stat">
            <span>Spent</span>
            <strong style="font-size:28px;">₹<?= h(number_format($spent)) ?></strong>
        </article>

        <article class="crm-stat">
            <span>Remaining</span>
            <strong style="font-size:28px;">₹<?= h(number_format($planned-$spent)) ?></strong>
        </article>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
