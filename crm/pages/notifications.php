<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';
require_once dirname(__DIR__, 2) . '/includes/notifications.php';

$crmRole = $crmRole ?? 'vendor';
$crmPage = 'notifications';
$crmTitle = 'Notifications';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)$crmUser['id'];
$preferenceMessage = '';
$preferenceSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $preferenceMessage = 'Session expired. Refresh and try again.';
    } else {
        $preferenceResult = wz_notification_update_preferences(
            $userId,
            !empty($_POST['email_enabled']),
            !empty($_POST['push_enabled'])
        );

        $preferenceMessage = (string)$preferenceResult['message'];
        $preferenceSuccess = (bool)$preferenceResult['ok'];
    }
}

$notificationPreferences = wz_notification_preferences($userId);
$deliveryStatus = wz_notification_delivery_status();
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

<?php if ($preferenceMessage !== ''): ?>
    <div class="crm-notice <?= $preferenceSuccess ? 'success' : '' ?>">
        <?= h($preferenceMessage) ?>
    </div>
<?php endif; ?>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Delivery channels</h2>
            <p>
                In-app alerts are always available. Email and push can be enabled when the production delivery provider is configured.
            </p>
        </div>
    </div>

    <form method="post" class="crm-form">
        <input
            type="hidden"
            name="csrf"
            value="<?= h(wz_csrf_token()) ?>"
        >

        <div class="crm-notification-preferences">
            <label>
                <span>
                    <strong>In-app</strong>
                    <small>Always on inside your Wedding Za CRM.</small>
                </span>
                <input type="checkbox" checked disabled>
            </label>

            <label>
                <span>
                    <strong>Email alerts</strong>
                    <small>
                        <?= $deliveryStatus['email_available']
                            ? 'Provider ready — receive important CRM updates by email.'
                            : 'Ready for production provider connection.' ?>
                    </small>
                </span>
                <input
                    type="checkbox"
                    name="email_enabled"
                    value="1"
                    <?= !empty($notificationPreferences['email_enabled']) ? 'checked' : '' ?>
                >
            </label>

            <label>
                <span>
                    <strong>Push alerts</strong>
                    <small>
                        <?= $deliveryStatus['push_available']
                            ? 'Provider ready — receive supported push alerts.'
                            : 'Ready for OneSignal, FCM or another push provider.' ?>
                    </small>
                </span>
                <input
                    type="checkbox"
                    name="push_enabled"
                    value="1"
                    <?= !empty($notificationPreferences['push_enabled']) ? 'checked' : '' ?>
                >
            </label>
        </div>

        <button class="crm-button" type="submit">
            Save notification preferences
        </button>
    </form>
</section>

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
