<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/marketplace.php';
require_once dirname(__DIR__) . '/includes/notifications.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$adminPage = 'marketplace-notifications';
$adminTitle = 'Marketplace Notifications';
$adminUser = wz_user() ?? [];
$userId = (int)($adminUser['id'] ?? 0);
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

$notifications = wz_marketplace_notifications(
    $userId,
    100
);

wz_marketplace_mark_notifications_read(
    $userId
);

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>MARKETPLACE UPDATES</small>
        <h1>Notifications</h1>
    </div>
</div>

<?php if ($preferenceMessage !== ''): ?>
    <div class="admin-notice <?= $preferenceSuccess ? 'success' : '' ?>">
        <?= h($preferenceMessage) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-panel-head">
        <div>
            <h2>Delivery channels</h2>
            <p>
                Keep in-app alerts active and optionally route production email/push through the configured delivery provider.
            </p>
        </div>
    </div>

    <form method="post" class="admin-form">
        <input
            type="hidden"
            name="csrf"
            value="<?= h(wz_csrf_token()) ?>"
        >

        <div class="admin-notification-preferences">
            <label>
                <span>
                    <strong>In-app</strong>
                    <small>Always enabled for operational alerts.</small>
                </span>
                <input type="checkbox" checked disabled>
            </label>

            <label>
                <span>
                    <strong>Email alerts</strong>
                    <small>
                        <?= $deliveryStatus['email_available']
                            ? 'Production delivery provider is enabled.'
                            : 'Provider-ready; add production delivery credentials to activate.' ?>
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
                            ? 'Production push routing is enabled.'
                            : 'Provider-ready for OneSignal, FCM or another push service.' ?>
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

        <button class="admin-button" type="submit">
            Save preferences
        </button>
    </form>
</section>

<section class="admin-panel">
    <?php if ($notifications): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Update</th>
                        <th>Type</th>
                        <th>Received</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($notifications as $item): ?>
                        <tr>
                            <td>
                                <strong><?= h((string)$item['title']) ?></strong>

                                <?php if (!empty($item['body'])): ?>
                                    <br>
                                    <small><?= h((string)$item['body']) ?></small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= h(
                                    ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            (string)$item['type']
                                        )
                                    )
                                ) ?>
                            </td>

                            <td><?= h((string)$item['created_at']) ?></td>

                            <td>
                                <?php if (!empty($item['action_url'])): ?>
                                    <a
                                        class="admin-button secondary"
                                        href="<?= h(
                                            wz_app_url(
                                                (string)$item['action_url']
                                            )
                                        ) ?>"
                                    >
                                        Open
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty">
            No marketplace notifications yet.
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
