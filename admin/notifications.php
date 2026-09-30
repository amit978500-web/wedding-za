<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/marketplace.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$adminPage = 'marketplace-notifications';
$adminTitle = 'Marketplace Notifications';
$adminUser = wz_user() ?? [];
$userId = (int)($adminUser['id'] ?? 0);

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
