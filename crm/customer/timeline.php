<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';

$crmRole = 'host';
$crmPage = 'planning-timeline';
$crmTitle = 'Wedding Timeline';
$crmUser = wz_crm_require_role('host');
$userId = (int)$crmUser['id'];
$pdo = wz_db();
$profile = wz_crm_customer_profile($userId) ?: [];
$timeline = wz_marketplace_wedding_timeline(
    (string)($profile['event_date'] ?? '')
);
$message = '';
$isSuccess = false;

if (
    $pdo
    && $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'create-tasks'
) {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO crm_tasks (
                owner_user_id,
                created_by,
                related_type,
                title,
                due_at,
                priority,
                status
            ) VALUES (
                :owner_user_id,
                :created_by,
                "timeline",
                :title,
                :due_at,
                "normal",
                "open"
            )'
        );

        foreach ($timeline as $item) {
            if ($item['title'] === 'Wedding day') {
                continue;
            }

            $exists = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM crm_tasks
                 WHERE owner_user_id = :owner_user_id
                 AND related_type = "timeline"
                 AND title = :title'
            );

            $exists->execute([
                'owner_user_id' => $userId,
                'title' => $item['title'],
            ]);

            if ((int)$exists->fetchColumn() > 0) {
                continue;
            }

            $statement->execute([
                'owner_user_id' => $userId,
                'created_by' => $userId,
                'title' => $item['title'],
                'due_at' => $item['date'] . ' 10:00:00',
            ]);
        }

        $message = 'Timeline tasks added to your planning tasks.';
        $isSuccess = true;
    }
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">PLANNING TIMELINE</span>
        <h1>What happens when</h1>
        <p>
            Milestones are calculated from your main wedding date so the plan moves backwards from the day that matters.
        </p>
    </div>

    <?php if ($timeline): ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
            <input type="hidden" name="action" value="create-tasks">
            <button class="crm-button" type="submit">
                Add timeline to tasks
            </button>
        </form>
    <?php endif; ?>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>
                <?= !empty($profile['event_date'])
                    ? 'Wedding date · '.h((string)$profile['event_date'])
                    : 'Set your wedding date first' ?>
            </h2>
        </div>
    </div>

    <?php if ($timeline): ?>
        <div class="crm-calendar-grid">
            <?php foreach ($timeline as $item): ?>
                <article class="crm-day">
                    <span class="crm-badge <?= $item['is_past']?'done':'open' ?>">
                        <?= $item['is_past']?'Past':'Upcoming' ?>
                    </span>
                    <strong style="margin-top:10px;">
                        <?= h(date('d M Y',strtotime($item['date']))) ?>
                    </strong>
                    <span><?= h($item['title']) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="crm-empty">
            Add your wedding date in My Requirements → Wedding Details to generate a timeline.
        </div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
