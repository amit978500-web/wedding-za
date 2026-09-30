<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';

$crmRole = $crmRole ?? 'vendor';
$crmPage = 'notifications';
$crmTitle = 'Notifications';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)$crmUser['id'];
$notifications = wz_marketplace_notifications($userId);
wz_marketplace_mark_notifications_read($userId);

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">UPDATES</span>
        <h1>Notifications</h1>
        <p>
            Review activity, customer quote decisions and marketplace events appear here.
        </p>
    </div>
</div>

<section class="crm-panel">
    <div class="crm-list">
        <?php foreach ($notifications as $item): ?>
            <div class="crm-list-item">
                <strong><?= h((string)$item['title']) ?></strong>
                <?php if (!empty($item['body'])): ?>
                    <p><?= h((string)$item['body']) ?></p>
                <?php endif; ?>
                <small><?= h((string)$item['created_at']) ?></small>
            </div>
        <?php endforeach; ?>

        <?php if (!$notifications): ?>
            <div class="crm-empty">No notifications yet.</div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
