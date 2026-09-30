<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/vendors.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$cities = wz_data('cities');
$businesses = wz_public_vendors();

$adminPage = 'website-cities';
$adminTitle = 'Website Cities';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>WEBSITE</small>
        <h1>Cities</h1>

        <p class="admin-section-note">
            Cities currently configured for Wedding Za discovery, with live marketplace counts.
        </p>
    </div>
</div>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Configured cities</h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>City</th>
                    <th>Businesses</th>
                    <th>Venues</th>
                    <th>Public page</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($cities as $city): ?>
                    <?php
                    $cityBusinesses = array_values(
                        array_filter(
                            $businesses,
                            fn (array $business): bool =>
                                strcasecmp(
                                    (string)($business['city'] ?? ''),
                                    (string)$city
                                ) === 0
                        )
                    );

                    $cityVenues = array_values(
                        array_filter(
                            $cityBusinesses,
                            fn (array $business): bool =>
                                strcasecmp(
                                    (string)($business['category'] ?? ''),
                                    'Venues'
                                ) === 0
                        )
                    );
                    ?>

                    <tr>
                        <td>
                            <strong>
                                <?= h((string)$city) ?>
                            </strong>
                        </td>

                        <td>
                            <?= h((string)count($cityBusinesses)) ?>
                        </td>

                        <td>
                            <?= h((string)count($cityVenues)) ?>
                        </td>

                        <td>
                            <a
                                class="admin-button secondary"
                                href="../city.php?city=<?= urlencode((string)$city) ?>"
                                target="_blank"
                            >
                                View ↗
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$cities): ?>
                    <tr>
                        <td colspan="4">
                            No cities are configured in site data.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="admin-notice">
    City names are currently configuration-driven from
    <code>assets/data/site.json</code>.
    This screen reflects the live configured list and marketplace coverage.
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
