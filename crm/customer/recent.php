<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';
require_once dirname(__DIR__, 2) . '/includes/vendors.php';

$crmRole = 'host';
$crmPage = 'recent';
$crmTitle = 'Recently Viewed';
$crmUser = wz_crm_require_role('host');
$userId = (int)$crmUser['id'];
$rows = wz_marketplace_recently_viewed($userId, 20);

$businessMap = [];

foreach (wz_public_vendors() as $business) {
    $businessMap[(string)$business['id']] = $business;
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">HISTORY</span>
        <h1>Recently viewed</h1>
        <p>
            Return to venue and vendor profiles you opened recently without starting the search again.
        </p>
    </div>
</div>

<section class="crm-panel">
    <div class="crm-list">
        <?php foreach ($rows as $row): ?>
            <?php
            $type = (string)$row['entity_type'];
            $key = (string)$row['entity_key'];
            $business = $businessMap[$key] ?? null;
            ?>

            <?php if ($business): ?>
                <a
                    class="crm-list-item"
                    href="<?= h(wz_app_url('vendor.php?id='.urlencode($key))) ?>"
                >
                    <strong><?= h((string)$business['name']) ?></strong>
                    <p>
                        <?= h((string)$business['category']) ?>
                        · <?= h((string)$business['city']) ?>
                        · <?= h((string)$business['price']) ?>
                    </p>
                    <small>
                        Viewed <?= h((string)$row['viewed_at']) ?>
                    </small>
                </a>
            <?php else: ?>
                <div class="crm-list-item">
                    <strong><?= h(ucfirst($type)) ?></strong>
                    <p><?= h($key) ?></p>
                    <small>Viewed <?= h((string)$row['viewed_at']) ?></small>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (!$rows): ?>
            <div class="crm-empty">
                Open a few venue or vendor profiles and they will appear here.
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
