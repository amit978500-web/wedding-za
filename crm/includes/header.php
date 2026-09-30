<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';

$crmRole = $crmRole ?? 'host';
$crmPage = $crmPage ?? 'dashboard';
$crmTitle = $crmTitle ?? 'CRM';

$crmUser = wz_crm_require_role(
    $crmRole
);

$crmUnreadNotifications = wz_marketplace_unread_notification_count(
    (int)($crmUser['id'] ?? 0)
);

$crmNotificationLabel = 'Notifications'
    . (
        $crmUnreadNotifications > 0
            ? ' (' . $crmUnreadNotifications . ')'
            : ''
    );

$crmNavigation = match ($crmRole) {
    'host' => [
        'requirements' => [
            'label' => 'My Requirements',
            'path' => 'crm/customer/requirements-wedding.php',
            'children' => [
                'requirements-wedding' => [
                    'label' => 'Wedding Details',
                    'path' => 'crm/customer/requirements-wedding.php',
                ],
                'requirements-functions' => [
                    'label' => 'Functions',
                    'path' => 'crm/customer/requirements-functions.php',
                ],
                'requirements-budget' => [
                    'label' => 'Budget',
                    'path' => 'crm/customer/requirements-budget.php',
                ],
            ],
        ],
        'recommendations' => [
            'label' => 'For You',
            'path' => 'crm/customer/recommendations.php',
        ],
        'find-venues' => [
            'label' => 'Find Venues',
            'path' => 'crm/customer/find-venues.php',
        ],
        'shortlist' => [
            'label' => 'My Shortlist',
            'path' => 'crm/customer/shortlist.php',
            'children' => [
                'shortlist-saved' => [
                    'label' => 'Saved Venues',
                    'path' => 'crm/customer/shortlist.php',
                ],
                'recent' => [
                    'label' => 'Recently Viewed',
                    'path' => 'crm/customer/recent.php',
                ],
            ],
        ],
        'enquiries' => [
            'label' => 'My Enquiries',
            'path' => 'crm/customer/enquiries.php',
            'children' => [
                'enquiries-all' => [
                    'label' => 'All Enquiries',
                    'path' => 'crm/customer/enquiries.php',
                ],
                'messages' => [
                    'label' => 'Messages',
                    'path' => 'crm/customer/messages.php',
                ],
            ],
        ],
        'site-visits' => [
            'label' => 'Site Visits',
            'path' => 'crm/customer/site-visits-upcoming.php',
            'children' => [
                'site-visits-upcoming' => [
                    'label' => 'Upcoming',
                    'path' => 'crm/customer/site-visits-upcoming.php',
                ],
                'site-visits-completed' => [
                    'label' => 'Completed',
                    'path' => 'crm/customer/site-visits-completed.php',
                ],
            ],
        ],
        'bookings' => [
            'label' => 'Bookings',
            'path' => 'crm/customer/bookings-upcoming.php',
            'children' => [
                'bookings-upcoming' => [
                    'label' => 'Upcoming',
                    'path' => 'crm/customer/bookings-upcoming.php',
                ],
                'bookings-completed' => [
                    'label' => 'Completed',
                    'path' => 'crm/customer/bookings-completed.php',
                ],
                'finances' => [
                    'label' => 'Quotes & Payments',
                    'path' => 'crm/customer/finances.php',
                ],
            ],
        ],
        'planning' => [
            'label' => 'Planning Tools',
            'path' => 'crm/customer/timeline.php',
            'children' => [
                'planning-timeline' => [
                    'label' => 'Wedding Timeline',
                    'path' => 'crm/customer/timeline.php',
                ],
                'budget-planner' => [
                    'label' => 'Budget Planner',
                    'path' => 'crm/customer/budget-planner.php',
                ],
                'planning-moodboards' => [
                    'label' => 'Moodboards',
                    'path' => 'crm/customer/moodboards.php',
                ],
                'planning-collaborators' => [
                    'label' => 'Collaborators',
                    'path' => 'crm/customer/collaborators.php',
                ],
            ],
        ],
        'notifications' => [
            'label' => $crmNotificationLabel,
            'path' => 'crm/customer/notifications.php',
        ],
        'profile' => [
            'label' => 'Profile',
            'path' => 'crm/customer/profile.php',
        ],
    ],
    'venue' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'path' => 'crm/venue/index.php',
        ],
        'leads' => [
            'label' => 'Leads',
            'path' => 'crm/venue/leads.php',
            'children' => [
                'leads-all' => [
                    'label' => 'All Leads',
                    'path' => 'crm/venue/leads.php',
                ],
                'leads-new' => [
                    'label' => 'New Leads',
                    'path' => 'crm/venue/leads-new.php',
                ],
                'leads-followups' => [
                    'label' => 'Follow-ups',
                    'path' => 'crm/venue/leads-followups.php',
                ],
                'leads-site-visits' => [
                    'label' => 'Site Visits',
                    'path' => 'crm/venue/leads-site-visits.php',
                ],
                'leads-lost' => [
                    'label' => 'Lost Leads',
                    'path' => 'crm/venue/leads-lost.php',
                ],
            ],
        ],
        'functions' => [
            'label' => 'Functions',
            'path' => 'crm/venue/functions.php',
            'children' => [
                'functions-all' => [
                    'label' => 'All Functions',
                    'path' => 'crm/venue/functions.php',
                ],
                'functions-upcoming' => [
                    'label' => 'Upcoming',
                    'path' => 'crm/venue/functions-upcoming.php',
                ],
                'functions-calendar' => [
                    'label' => 'Calendar',
                    'path' => 'crm/venue/functions-calendar.php',
                ],
            ],
        ],
        'bookings' => [
            'label' => 'Bookings',
            'path' => 'crm/venue/bookings.php',
        ],
        'availability' => [
            'label' => 'Availability',
            'path' => 'crm/venue/availability.php',
        ],
        'quotes' => [
            'label' => 'Quotes',
            'path' => 'crm/venue/quotes.php',
        ],
        'payments' => [
            'label' => 'Payments',
            'path' => 'crm/venue/payments.php',
        ],
        'messages' => [
            'label' => 'Messages',
            'path' => 'crm/venue/messages.php',
        ],
        'reviews' => [
            'label' => 'Reviews',
            'path' => 'crm/venue/reviews.php',
        ],
        'analytics' => [
            'label' => 'Analytics',
            'path' => 'crm/venue/analytics.php',
        ],
        'reports' => [
            'label' => 'Reports',
            'path' => 'crm/venue/reports.php',
        ],
        'subscription' => [
            'label' => 'Business Plan',
            'path' => 'crm/venue/subscription.php',
        ],
        'notifications' => [
            'label' => $crmNotificationLabel,
            'path' => 'crm/venue/notifications.php',
        ],
        'profile' => [
            'label' => 'Venue Profile',
            'path' => 'crm/venue/profile.php',
        ],
    ],
    default => [
        'dashboard' => [
            'label' => 'Overview',
            'path' => 'crm/vendor/index.php',
        ],
        'enquiries' => [
            'label' => 'Sales Pipeline',
            'path' => 'crm/vendor/enquiries.php',
        ],
        'bookings' => [
            'label' => 'Bookings',
            'path' => 'crm/vendor/bookings.php',
        ],
        'availability' => [
            'label' => 'Availability',
            'path' => 'crm/vendor/availability.php',
        ],
        'quotes' => [
            'label' => 'Quotes',
            'path' => 'crm/vendor/quotes.php',
        ],
        'messages' => [
            'label' => 'Messages',
            'path' => 'crm/vendor/messages.php',
        ],
        'tasks' => [
            'label' => 'Tasks',
            'path' => 'crm/vendor/tasks.php',
        ],
        'reviews' => [
            'label' => 'Reviews',
            'path' => 'crm/vendor/reviews.php',
        ],
        'analytics' => [
            'label' => 'Analytics',
            'path' => 'crm/vendor/analytics.php',
        ],
        'subscription' => [
            'label' => 'Business Plan',
            'path' => 'crm/vendor/subscription.php',
        ],
        'notifications' => [
            'label' => $crmNotificationLabel,
            'path' => 'crm/vendor/notifications.php',
        ],
        'profile' => [
            'label' => 'Business Profile',
            'path' => 'crm/vendor/profile.php',
        ],
    ],
};
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

    <title><?= h($crmTitle) ?> · Wedding Za CRM</title>

    <link
        rel="stylesheet"
        href="<?= h(wz_app_url('assets/css/crm.css?v=1.2.0')) ?>"
    >

    <link
        rel="stylesheet"
        href="<?= h(wz_app_url('assets/css/crm-premium.css?v=1.1.0')) ?>"
    >
</head>

<body class="crm-role-<?= h($crmRole) ?>">
    <div class="crm-shell">
        <button
            class="crm-mobile-toggle"
            type="button"
            aria-label="Open CRM navigation"
            aria-expanded="false"
        >
            ☰
        </button>

        <div
            class="crm-sidebar-backdrop"
            aria-hidden="true"
        ></div>

        <aside class="crm-sidebar">
            <a
                class="crm-brand"
                href="<?= h(wz_app_url(wz_crm_portal_path($crmRole))) ?>"
            >
                Wedding Za

                <small>
                    <?= h(wz_crm_role_label($crmRole)) ?> CRM
                </small>
            </a>

            <nav class="crm-nav">
                <?php
                $navNumber = 1;
                ?>

                <?php foreach ($crmNavigation as $key => $item): ?>
                    <?php
                    $hasChildren = !empty($item['children']);

                    $isParentActive = $crmPage === $key
                        || str_starts_with(
                            $crmPage,
                            $key . '-'
                        );
                    ?>

                    <div
                        class="crm-nav-item <?= $hasChildren ? 'has-children' : '' ?>"
                    >
                        <a
                            class="<?= $isParentActive ? 'active' : '' ?>"
                            href="<?= h(wz_app_url($item['path'])) ?>"
                        >
                            <?= h($item['label']) ?>

                            <span>
                                <?= str_pad(
                                    (string)$navNumber,
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>
                            </span>
                        </a>

                        <?php if ($hasChildren): ?>
                            <div class="crm-subnav">
                                <?php foreach ($item['children'] as $childKey => $child): ?>
                                    <a
                                        class="<?= $crmPage === $childKey ? 'active' : '' ?>"
                                        href="<?= h(wz_app_url($child['path'])) ?>"
                                    >
                                        <?= h($child['label']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php
                    $navNumber++;
                    ?>
                <?php endforeach; ?>
            </nav>

            <div class="crm-user">
                <strong>
                    <?= h((string)$crmUser['name']) ?>
                </strong>

                <small>
                    <?= h((string)$crmUser['email']) ?>
                </small>

                <?php if ($crmRole === 'venue'): ?>
                    <a href="<?= h(wz_app_url('crm/venue/profile.php')) ?>">
                        Venue profile ↗
                    </a>

                    <br>

                    <a href="<?= h(wz_app_url('crm/venue/messages.php')) ?>">
                        Messages ↗
                    </a>

                    <br>
                <?php endif; ?>

                <a href="<?= h(wz_app_url('index.php')) ?>">
                    Public site ↗
                </a>

                <br>

                <a href="<?= h(wz_app_url('logout.php')) ?>">
                    Log out ↗
                </a>
            </div>
        </aside>

        <main class="crm-main">
