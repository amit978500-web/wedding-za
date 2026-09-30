<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$adminPage = 'marketplace-submissions';
$adminTitle = 'Wedding Submissions';
$message = '';
$isSuccess = false;
$pdo = wz_db();

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $status = (string)($_POST['status'] ?? 'submitted');
        $adminNote = trim((string)($_POST['admin_note'] ?? ''));

        if (!in_array(
            $status,
            ['submitted','reviewing','published','rejected'],
            true
        )) {
            $status = 'submitted';
        }

        $statement = $pdo->prepare(
            'UPDATE wedding_submissions
             SET status = :status,
                 admin_note = :admin_note
             WHERE id = :id'
        );

        $statement->execute([
            'status' => $status,
            'admin_note' => $adminNote !== '' ? $adminNote : null,
            'id' => $id,
        ]);

        wz_audit(
            'marketplace.wedding_submission.updated',
            'wedding_submission',
            $id,
            ['status' => $status]
        );

        $message = 'Wedding submission updated.';
        $isSuccess = true;
    }
}

$rows = [];

if ($pdo) {
    $rows = $pdo->query(
        'SELECT *
         FROM wedding_submissions
         ORDER BY
            CASE status
                WHEN "submitted" THEN 0
                WHEN "reviewing" THEN 1
                WHEN "published" THEN 2
                ELSE 3
            END,
            created_at DESC'
    )->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>EDITORIAL</small>
        <h1>Wedding submissions</h1>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Celebration</th>
                    <th>Contact</th>
                    <th>City / date</th>
                    <th>Story</th>
                    <th>Vendors</th>
                    <th>Status</th>
                    <th>Editorial action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <strong><?= h((string)$row['couple_names']) ?></strong>
                            <?php if (!empty($row['cover_image_url'])): ?>
                                <br><a href="<?= h((string)$row['cover_image_url']) ?>" target="_blank">Cover ↗</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= h((string)($row['submitter_name'] ?: '—')) ?>
                            <br><small><?= h((string)($row['submitter_email'] ?: '—')) ?></small>
                        </td>
                        <td>
                            <?= h((string)($row['city'] ?: '—')) ?>
                            <br><small><?= h((string)($row['event_date'] ?: '—')) ?></small>
                        </td>
                        <td style="max-width:280px;"><?= h(mb_strimwidth((string)$row['story'],0,220,'…')) ?></td>
                        <td style="max-width:220px;">
                            <?php
                            $vendors = json_decode((string)($row['vendors_json'] ?? '[]'),true) ?: [];
                            ?>
                            <?= h(implode(', ',array_map('strval',$vendors))) ?>
                        </td>
                        <td><?= h(ucfirst((string)$row['status'])) ?></td>
                        <td>
                            <form method="post" class="admin-form" style="padding:0;min-width:260px;">
                                <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= h((string)$row['id']) ?>">

                                <select name="status">
                                    <?php foreach (['submitted','reviewing','published','rejected'] as $status): ?>
                                        <option value="<?= h($status) ?>" <?= $status===$row['status']?'selected':'' ?>>
                                            <?= h(ucfirst($status)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <textarea name="admin_note" placeholder="Editorial note"><?= h((string)($row['admin_note']??'')) ?></textarea>

                                <button class="admin-button" type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr><td colspan="7">No wedding submissions yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
