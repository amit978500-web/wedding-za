<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';

if (!wz_is_admin()) {
    header(
        'Location: login.php'
    );

    exit;
}

$adminPage = $adminPage ?? '';
$adminTitle = $adminTitle ?? 'Wedding Za Admin';
$adminUser = wz_user();

function wz_admin_active(string $page): string
{
    global $adminPage;

    return $adminPage === $page
        ? ' class="active"'
        : '';
}
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
        href="../assets/css/admin.css?v=1.0.0"
    >
</head>

<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a
                class="admin-brand"
                href="index.php"
            >
                Wedding Za
                <span>Admin</span>
            </a>

            <nav class="admin-nav">
                <a<?= wz_admin_active('dashboard') ?> href="index.php">
                    Dashboard
                </a>

                <a<?= wz_admin_active('vendors') ?> href="vendors.php">
                    Vendors
                </a>

                <a<?= wz_admin_active('leads') ?> href="leads.php">
                    Leads
                </a>

                <a<?= wz_admin_active('users') ?> href="users.php">
                    Users
                </a>

                <a<?= wz_admin_active('content') ?> href="content.php">
                    Content
                </a>

                <a<?= wz_admin_active('media') ?> href="media.php">
                    Media
                </a>

                <a<?= wz_admin_active('audit') ?> href="audit.php">
                    Audit log
                </a>
            </nav>

            <div class="admin-user">
                <strong>
                    <?= h((string)$adminUser['name']) ?>
                </strong>

                <small>
                    <?= h((string)$adminUser['email']) ?>
                </small>

                <a href="../logout.php">
                    Log out ↗
                </a>
            </div>
        </aside>

        <main class="admin-main">
