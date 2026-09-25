<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$pdo = wz_db();
$rows = [];

if ($pdo) {
    $rows = $pdo
        ->query(
            'SELECT
                al.*,
                u.name AS user_name,
                u.email AS user_email
             FROM audit_log al
             LEFT JOIN users u
                ON u.id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT 300'
        )
        ->fetchAll();
}

$adminPage = 'audit';
$adminTitle = 'Audit Log';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>SECURITY</small>
        <h1>Audit log</h1>
    </div>
</div>

<section class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>IP</th>
                    <th>Metadata</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= h((string)$row['created_at']) ?></td>

                        <td>
                            <?= h((string)$row['user_name']) ?>
                            <br>
                            <small><?= h((string)$row['user_email']) ?></small>
                        </td>

                        <td><?= h((string)$row['action']) ?></td>

                        <td>
                            <?= h((string)$row['entity_type']) ?>
                            #<?= h((string)$row['entity_id']) ?>
                        </td>

                        <td><?= h((string)$row['ip']) ?></td>

                        <td>
                            <code>
                                <?= h((string)$row['metadata_json']) ?>
                            </code>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="6">
                            No audit events yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
