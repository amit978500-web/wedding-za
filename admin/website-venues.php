<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/vendors.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$publicVenues = array_values(
    array_filter(
        wz_public_vendors(),
        fn (array $business): bool =>
            strcasecmp(
                (string)($business['category'] ?? ''),
                'Venues'
            ) === 0
    )
);

$adminPage = 'website-venues';
$adminTitle = 'Website Venues';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>WEBSITE</small>
        <h1>Venues</h1>

        <p class="admin-section-note">
            Venue listings currently visible through the public marketplace discovery layer.
        </p>
    </div>

    <div class="admin-actions">
        <a
            class="admin-button secondary"
            href="venues.php"
        >
            Venue moderation
        </a>
    </div>
</div>

<div class="admin-grid">
    <article class="admin-stat">
        <span>Public venue listings</span>
        <strong><?= h((string)count($publicVenues)) ?></strong>
    </article>

    <article class="admin-stat">
        <span>Database venues</span>
        <strong>
            <?= h(
                (string)count(
                    array_filter(
                        $publicVenues,
                        fn (array $venue): bool =>
                            !empty($venue['database_profile_id'])
                    )
                )
            ) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Featured</span>
        <strong>
            <?= h(
                (string)count(
                    array_filter(
                        $publicVenues,
                        fn (array $venue): bool =>
                            !empty($venue['featured'])
                    )
                )
            ) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Configured/static</span>
        <strong>
            <?= h(
                (string)count(
                    array_filter(
                        $publicVenues,
                        fn (array $venue): bool =>
                            empty($venue['database_profile_id'])
                    )
                )
            ) ?>
        </strong>
    </article>
</div>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Public venue listings</h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Venue</th>
                    <th>City</th>
                    <th>Locality</th>
                    <th>Price</th>
                    <th>Source</th>
                    <th>Featured</th>
                    <th>Public profile</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($publicVenues as $venue): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h((string)$venue['name']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= h((string)($venue['city'] ?? '')) ?>
                        </td>

                        <td>
                            <?= h((string)($venue['locality'] ?? '')) ?>
                        </td>

                        <td>
                            <?= h((string)($venue['price'] ?? '')) ?>
                        </td>

                        <td>
                            <?= !empty($venue['database_profile_id'])
                                ? 'CRM'
                                : 'Configured' ?>
                        </td>

                        <td>
                            <?= !empty($venue['featured'])
                                ? 'Yes'
                                : 'No' ?>
                        </td>

                        <td>
                            <a
                                class="admin-button secondary"
                                href="../vendor.php?id=<?= urlencode((string)$venue['id']) ?>"
                                target="_blank"
                            >
                                View ↗
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$publicVenues): ?>
                    <tr>
                        <td colspan="7">
                            No public venues found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
