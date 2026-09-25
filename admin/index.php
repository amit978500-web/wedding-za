<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$pdo = wz_db();

$counts = [
    'users' => 0,
    'vendors' => 0,
    'pending_vendors' => 0,
    'leads' => 0,
    'new_leads' => 0,
    'content' => 0,
    'media' => 0,
];

$recentLeads = [];

if ($pdo) {
    $counts['users'] = (int)$pdo
        ->query('SELECT COUNT(*) FROM users')
        ->fetchColumn();

    $counts['vendors'] = (int)$pdo
        ->query('SELECT COUNT(*) FROM vendor_profiles')
        ->fetchColumn();

    $counts['pending_vendors'] = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM vendor_profiles
             WHERE approval_status = 'pending'"
        )
        ->fetchColumn();

    $counts['leads'] = (int)$pdo
        ->query('SELECT COUNT(*) FROM leads')
        ->fetchColumn();

    $counts['new_leads'] = (int)$pdo
        ->query(
            "SELECT COUNT(*)
             FROM leads
             WHERE status = 'new'"
        )
        ->fetchColumn();

    $counts['content'] = (int)$pdo
        ->query('SELECT COUNT(*) FROM content_entries')
        ->fetchColumn();

    $counts['media'] = (int)$pdo
        ->query('SELECT COUNT(*) FROM media_assets')
        ->fetchColumn();

    $recentLeads = $pdo
        ->query(
            'SELECT id, type, name, email, vendor, status, created_at
             FROM leads
             ORDER BY created_at DESC
             LIMIT 8'
        )
        ->fetchAll();
}

$adminPage = 'dashboard';
$adminTitle = 'Dashboard';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>OPERATIONS</small>
        <h1>Dashboard</h1>
    </div>

    <div class="admin-actions">
        <a
            class="admin-button secondary"
            href="../index.php"
            target="_blank"
        >
            View website ↗
        </a>
    </div>
</div>

<?php if (!$pdo): ?>
    <div class="admin-notice">
        MySQL is not connected. Admin operations require the production database.
    </div>
<?php endif; ?>

<div class="admin-grid">
    <article class="admin-stat">
        <span>Users</span>
        <strong><?= h((string)$counts['users']) ?></strong>
    </article>

    <article class="admin-stat">
        <span>Vendors</span>
        <strong><?= h((string)$counts['vendors']) ?></strong>
    </article>

    <article class="admin-stat">
        <span>Pending vendors</span>
        <strong><?= h((string)$counts['pending_vendors']) ?></strong>
    </article>

    <article class="admin-stat">
        <span>New leads</span>
        <strong><?= h((string)$counts['new_leads']) ?></strong>
    </article>
</div>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Recent leads</h2>

        <a
            class="admin-button secondary"
            href="leads.php"
        >
            View all
        </a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Vendor</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($recentLeads as $lead): ?>
                    <tr>
                        <td><?= h((string)$lead['type']) ?></td>
                        <td><?= h((string)$lead['name']) ?></td>
                        <td><?= h((string)$lead['email']) ?></td>
                        <td><?= h((string)$lead['vendor']) ?></td>
                        <td>
                            <span class="admin-badge <?= h((string)$lead['status']) ?>">
                                <?= h((string)$lead['status']) ?>
                            </span>
                        </td>
                        <td><?= h((string)$lead['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$recentLeads): ?>
                    <tr>
                        <td colspan="6">
                            No leads yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Operations snapshot</h2>
    </div>

    <div class="admin-grid" style="margin: 0;">
        <article class="admin-stat">
            <span>Total leads</span>
            <strong><?= h((string)$counts['leads']) ?></strong>
        </article>

        <article class="admin-stat">
            <span>Content entries</span>
            <strong><?= h((string)$counts['content']) ?></strong>
        </article>

        <article class="admin-stat">
            <span>Media assets</span>
            <strong><?= h((string)$counts['media']) ?></strong>
        </article>

        <article class="admin-stat">
            <span>Vendor approval queue</span>
            <strong><?= h((string)$counts['pending_vendors']) ?></strong>
        </article>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
