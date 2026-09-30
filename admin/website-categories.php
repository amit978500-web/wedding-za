<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/vendors.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$categories = wz_data('categories');
$businesses = wz_public_vendors();

$adminPage = 'website-categories';
$adminTitle = 'Website Categories';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>WEBSITE</small>
        <h1>Categories</h1>

        <p class="admin-section-note">
            Marketplace categories currently configured on the public website.
        </p>
    </div>
</div>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Configured categories</h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Businesses</th>
                    <th>Image</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($categories as $category): ?>
                    <?php
                    $categoryName = (string)($category['name'] ?? '');

                    $categoryBusinesses = array_values(
                        array_filter(
                            $businesses,
                            fn (array $business): bool =>
                                strcasecmp(
                                    (string)($business['category'] ?? ''),
                                    $categoryName
                                ) === 0
                        )
                    );
                    ?>

                    <tr>
                        <td>
                            <strong>
                                <?= h($categoryName) ?>
                            </strong>
                        </td>

                        <td>
                            <?= h((string)($category['id'] ?? '')) ?>
                        </td>

                        <td>
                            <?= h((string)($category['sub'] ?? '')) ?>
                        </td>

                        <td>
                            <?= h((string)count($categoryBusinesses)) ?>
                        </td>

                        <td>
                            <?php if (!empty($category['image'])): ?>
                                <a
                                    class="admin-button secondary"
                                    href="<?= h((string)$category['image']) ?>"
                                    target="_blank"
                                >
                                    Preview ↗
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$categories): ?>
                    <tr>
                        <td colspan="5">
                            No categories are configured in site data.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="admin-notice">
    Category definitions are currently configuration-driven from
    <code>assets/data/site.json</code>.
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
