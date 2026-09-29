<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = $crmRole ?? 'host';
$crmPage = $crmPage ?? 'dashboard';
$crmTitle = $crmTitle ?? 'CRM';

$crmUser = wz_crm_require_role(
    $crmRole
);

$crmNavigation = match ($crmRole) {
    'host' => [
        'dashboard' => [
            'label' => 'Overview',
            'path' => 'crm/customer/index.php',
        ],
        'enquiries' => [
            'label' => 'My enquiries',
            'path' => 'crm/customer/enquiries.php',
        ],
        'bookings' => [
            'label' => 'Bookings',
            'path' => 'crm/customer/bookings.php',
        ],
        'tasks' => [
            'label' => 'Tasks',
            'path' => 'crm/customer/tasks.php',
        ],
        'messages' => [
            'label' => 'Messages',
            'path' => 'crm/customer/messages.php',
        ],
        'planning' => [
            'label' => 'Planning Studio',
            'path' => 'planner.php',
        ],
        'shortlist' => [
            'label' => 'Shortlist',
            'path' => 'shortlist.php',
        ],
        'profile' => [
            'label' => 'Profile',
            'path' => 'crm/customer/profile.php',
        ],
    ],
    'venue' => [
        'dashboard' => [
            'label' => 'Overview',
            'path' => 'crm/venue/index.php',
        ],
        'enquiries' => [
            'label' => 'Sales pipeline',
            'path' => 'crm/venue/enquiries.php',
        ],
        'bookings' => [
            'label' => 'Bookings',
            'path' => 'crm/venue/bookings.php',
        ],
        'availability' => [
            'label' => 'Availability',
            'path' => 'crm/venue/availability.php',
        ],
        'tasks' => [
            'label' => 'Tasks',
            'path' => 'crm/venue/tasks.php',
        ],
        'messages' => [
            'label' => 'Messages',
            'path' => 'crm/venue/messages.php',
        ],
        'profile' => [
            'label' => 'Venue profile',
            'path' => 'crm/venue/profile.php',
        ],
    ],
    default => [
        'dashboard' => [
            'label' => 'Overview',
            'path' => 'crm/vendor/index.php',
        ],
        'enquiries' => [
            'label' => 'Sales pipeline',
            'path' => 'crm/vendor/enquiries.php',
        ],
        'bookings' => [
            'label' => 'Bookings',
            'path' => 'crm/vendor/bookings.php',
        ],
        'tasks' => [
            'label' => 'Tasks',
            'path' => 'crm/vendor/tasks.php',
        ],
        'messages' => [
            'label' => 'Messages',
            'path' => 'crm/vendor/messages.php',
        ],
        'profile' => [
            'label' => 'Business profile',
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
        href="<?= h(wz_app_url('assets/css/crm.css?v=1.0.0')) ?>"
    >
</head>

<body>
    <div class="crm-shell">
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
                    <a
                        class="<?= $crmPage === $key ? 'active' : '' ?>"
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
