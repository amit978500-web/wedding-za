<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';

$crmRole = 'host';
$crmPage = 'recommendations';
$crmTitle = 'Recommended For You';
$crmUser = wz_crm_require_role('host');
$userId = (int)$crmUser['id'];
$recommendations = wz_marketplace_recommendations($userId, 12);
$profile = wz_crm_customer_profile($userId) ?: [];

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">PERSONALISED DISCOVERY</span>
        <h1>Recommended for you</h1>
        <p>
            These venues are matched first by your wedding city and guest count, then by Wedding Za featured status.
        </p>
    </div>

    <div class="crm-actions">
        <a class="crm-button secondary" href="<?= h(wz_app_url('crm/customer/requirements-wedding.php')) ?>">
            Edit requirements
        </a>
        <a class="crm-button" href="<?= h(wz_app_url('venues.php')) ?>">
            Browse all venues ↗
        </a>
    </div>
</div>

<div class="crm-grid">
    <article class="crm-stat">
        <span>City</span>
        <strong style="font-size:28px;">
            <?= h((string)($profile['city'] ?: 'Not set')) ?>
        </strong>
    </article>

    <article class="crm-stat">
        <span>Guests</span>
        <strong><?= h((string)($profile['guest_count'] ?: '—')) ?></strong>
    </article>

    <article class="crm-stat">
        <span>Event type</span>
        <strong style="font-size:28px;">
            <?= h((string)($profile['event_type'] ?: 'Not set')) ?>
        </strong>
    </article>

    <article class="crm-stat">
        <span>Matches</span>
        <strong><?= h((string)count($recommendations)) ?></strong>
    </article>
</div>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Venue matches</h2>
            <p>Update your requirements to make these recommendations more precise.</p>
        </div>
    </div>

    <div class="crm-list">
        <?php foreach ($recommendations as $venue): ?>
            <a
                class="crm-list-item"
                href="<?= h(
                    wz_app_url(
                        'vendor.php?id=db-venue-'
                        . urlencode((string)$venue['profile_id'])
                    )
                ) ?>"
            >
                <strong><?= h((string)$venue['name']) ?></strong>
                <p>
                    <?= h((string)($venue['venue_type'] ?: 'Venue')) ?>
                    · <?= h((string)($venue['city'] ?: '')) ?>
                    <?php if (!empty($venue['locality'])): ?>
                        · <?= h((string)$venue['locality']) ?>
                    <?php endif; ?>
                </p>
                <small>
                    <?= h((string)($venue['starting_price'] ?: 'Ask for pricing')) ?>
                    <?php if (!empty($venue['capacity_max'])): ?>
                        · Up to <?= h((string)$venue['capacity_max']) ?> guests
                    <?php endif; ?>
                </small>
            </a>
        <?php endforeach; ?>

        <?php if (!$recommendations): ?>
            <div class="crm-empty">
                Add your city and guest count to receive venue recommendations.
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
