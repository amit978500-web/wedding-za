<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';

if (!wz_is_admin()) {
    header(
        'Location: login.php'
    );

    exit;
}

$adminPage = $adminPage ?? 'dashboard';
$adminTitle = $adminTitle ?? 'Wedding Za Admin';
$adminUser = wz_user();

$adminNavigation = [
    'dashboard' => [
        'label' => 'Dashboard',
        'path' => 'index.php',
    ],
    'leads' => [
        'label' => 'Leads',
        'path' => 'leads.php',
        'children' => [
            'leads-all' => [
                'label' => 'All Leads',
                'path' => 'leads.php',
            ],
            'leads-new' => [
                'label' => 'New Leads',
                'path' => 'leads-new.php',
            ],
            'leads-followups' => [
                'label' => 'Follow-ups',
                'path' => 'leads-followups.php',
            ],
            'leads-site-visits' => [
                'label' => 'Site Visits',
                'path' => 'leads-site-visits.php',
            ],
            'leads-lost' => [
                'label' => 'Lost Leads',
                'path' => 'leads-lost.php',
            ],
        ],
    ],
    'functions' => [
        'label' => 'Functions',
        'path' => 'functions.php',
        'children' => [
            'functions-all' => [
                'label' => 'All Functions',
                'path' => 'functions.php',
            ],
            'functions-upcoming' => [
                'label' => 'Upcoming',
                'path' => 'functions-upcoming.php',
            ],
            'functions-calendar' => [
                'label' => 'Calendar',
                'path' => 'functions-calendar.php',
            ],
        ],
    ],
    'bookings' => [
        'label' => 'Bookings',
        'path' => 'bookings.php',
    ],
    'payments' => [
        'label' => 'Payments',
        'path' => 'payments.php',
        'children' => [
            'payments-all' => [
                'label' => 'Payments',
                'path' => 'payments.php',
            ],
            'payments-invoices' => [
                'label' => 'Invoices',
                'path' => 'invoices.php',
            ],
            'payments-refunds' => [
                'label' => 'Refunds',
                'path' => 'refunds.php',
            ],
            'payments-commission' => [
                'label' => 'Commission',
                'path' => 'commission.php',
            ],
        ],
    ],
    'customers' => [
        'label' => 'Customers',
        'path' => 'customers.php',
    ],
    'venues' => [
        'label' => 'Venues',
        'path' => 'venues.php',
        'children' => [
            'venues-all' => [
                'label' => 'All Venues',
                'path' => 'venues.php',
            ],
            'venues-active' => [
                'label' => 'Active',
                'path' => 'venues-active.php',
            ],
            'venues-inactive' => [
                'label' => 'Inactive',
                'path' => 'venues-inactive.php',
            ],
        ],
    ],
    'vendors' => [
        'label' => 'Vendors',
        'path' => 'vendors.php',
        'children' => [
            'vendors-all' => [
                'label' => 'All Vendors',
                'path' => 'vendors.php',
            ],
            'vendors-active' => [
                'label' => 'Active',
                'path' => 'vendors-active.php',
            ],
        ],
    ],
    'reports' => [
        'label' => 'Reports',
        'path' => 'reports.php',
    ],
    'marketplace' => [
        'label' => 'Marketplace',
        'path' => 'reviews.php',
        'children' => [
            'marketplace-reviews' => [
                'label' => 'Review Moderation',
                'path' => 'reviews.php',
            ],
            'marketplace-venue-plans' => [
                'label' => 'Venue Assistance',
                'path' => 'venue-plans.php',
            ],
            'marketplace-subscriptions' => [
                'label' => 'Business Plans',
                'path' => 'subscriptions.php',
            ],
            'marketplace-submissions' => [
                'label' => 'Wedding Submissions',
                'path' => 'wedding-submissions.php',
            ],
        ],
    ],
    'website' => [
        'label' => 'Website',
        'path' => 'website-cities.php',
        'children' => [
            'website-cities' => [
                'label' => 'Cities',
                'path' => 'website-cities.php',
            ],
            'website-categories' => [
                'label' => 'Categories',
                'path' => 'website-categories.php',
            ],
            'website-venues' => [
                'label' => 'Venues',
                'path' => 'website-venues.php',
            ],
            'website-blogs' => [
                'label' => 'Blogs',
                'path' => 'website-blogs.php',
            ],
        ],
    ],
    'team' => [
        'label' => 'Team',
        'path' => 'team.php',
    ],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <meta
        name="robots"
        content="noindex,nofollow"
    >

    <title><?= h($adminTitle) ?> · Wedding Za Admin</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css?v=1.2.0"
    >

    <link
        rel="stylesheet"
        href="../assets/css/admin-premium.css?v=1.0.0"
    >
</head>

<body>
    <div class="admin-shell">
        <button
            class="admin-mobile-toggle"
            type="button"
            aria-label="Open admin navigation"
            aria-expanded="false"
        >
            ☰
        </button>

        <div
            class="admin-sidebar-backdrop"
            aria-hidden="true"
        ></div>

        <aside class="admin-sidebar">
            <a
                class="admin-brand"
                href="index.php"
            >
                Wedding Za

                <span>
                    Admin CRM
                </span>
            </a>

            <nav class="admin-nav">
                <?php foreach ($adminNavigation as $key => $item): ?>
                    <?php
                    $hasChildren = !empty($item['children']);

                    $isParentActive =
                        $adminPage === $key
                        || str_starts_with(
                            $adminPage,
                            $key . '-'
                        );
                    ?>

                    <div
                        class="admin-nav-item <?= $hasChildren ? 'has-children' : '' ?>"
                    >
                        <a
                            class="<?= $isParentActive ? 'active' : '' ?>"
                            href="<?= h((string)$item['path']) ?>"
                        >
                            <?= h((string)$item['label']) ?>
                        </a>

                        <?php if ($hasChildren): ?>
                            <div class="admin-subnav">
                                <?php foreach ($item['children'] as $childKey => $child): ?>
                                    <a
                                        class="<?= $adminPage === $childKey ? 'active' : '' ?>"
                                        href="<?= h((string)$child['path']) ?>"
                                    >
                                        <?= h((string)$child['label']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </nav>

            <div class="admin-user">
                <strong>
                    <?= h((string)$adminUser['name']) ?>
                </strong>

                <small>
                    <?= h((string)$adminUser['email']) ?>
                </small>

                <a href="media.php">
                    Media ↗
                </a>

                <br>

                <a href="audit.php">
                    Audit log ↗
                </a>

                <br>

                <a
                    href="../index.php"
                    target="_blank"
                >
                    Public site ↗
                </a>

                <br>

                <a href="../logout.php">
                    Log out ↗
                </a>
            </div>
        </aside>

        <main class="admin-main">
