<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$pdo = wz_db();
$message = '';

if (
    $pdo
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $status = (string)($_POST['status'] ?? 'new');
        $note = trim((string)($_POST['admin_note'] ?? ''));

        if (!in_array(
            $status,
            ['new', 'contacted', 'closed'],
            true
        )) {
            $status = 'new';
        }

        $statement = $pdo->prepare(
            'UPDATE leads
             SET status = :status,
                 admin_note = :admin_note
             WHERE id = :id'
        );

        $statement->execute([
            'status' => $status,
            'admin_note' => $note,
            'id' => $leadId,
        ]);

        wz_audit(
            'admin.lead.updated',
            'lead',
            $leadId,
            [
                'status' => $status,
            ]
        );

        $message = 'Lead updated.';
    }
}

$statusFilter = (string)($_GET['status'] ?? '');

$sql = 'SELECT *
        FROM leads';

$params = [];

if (in_array(
    $statusFilter,
    ['new', 'contacted', 'closed'],
    true
)) {
    $sql .= ' WHERE status = :status';
    $params['status'] = $statusFilter;
}

$sql .= ' ORDER BY created_at DESC LIMIT 200';

$leads = [];

if ($pdo) {
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $leads = $statement->fetchAll();
}

$adminPage = 'leads';
$adminTitle = 'Leads';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>ENQUIRIES</small>
        <h1>Leads</h1>
    </div>

    <div class="admin-actions">
        <a
            class="admin-button secondary"
            href="leads.php"
        >
            All
        </a>

        <a
            class="admin-button secondary"
            href="leads.php?status=new"
        >
            New
        </a>

        <a
            class="admin-button secondary"
            href="leads.php?status=contacted"
        >
            Contacted
        </a>

        <a
            class="admin-button secondary"
            href="leads.php?status=closed"
        >
            Closed
        </a>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice success">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Contact</th>
                    <th>Context</th>
                    <th>Message</th>
                    <th>Operations</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h((string)$lead['name']) ?>
                            </strong>
                            <br>
                            <small>
                                <?= h((string)$lead['type']) ?>
                                ·
                                <?= h((string)$lead['created_at']) ?>
                            </small>
                        </td>

                        <td>
                            <?= h((string)$lead['email']) ?>
                            <br>
                            <?= h((string)$lead['phone']) ?>
                        </td>

                        <td>
                            <?= h((string)$lead['topic']) ?>
                            <br>
                            <?= h((string)$lead['city']) ?>
                            <br>
                            <?= h((string)$lead['vendor']) ?>
                        </td>

                        <td>
                            <?= nl2br(h((string)$lead['message'])) ?>
                        </td>

                        <td>
                            <form
                                method="post"
                                class="admin-form"
                                style="padding: 0; min-width: 240px;"
                            >
                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= h(wz_csrf_token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="lead_id"
                                    value="<?= h((string)$lead['id']) ?>"
                                >

                                <div class="admin-field">
                                    <label>Status</label>

                                    <select name="status">
                                        <?php foreach (['new', 'contacted', 'closed'] as $status): ?>
                                            <option
                                                value="<?= h($status) ?>"
                                                <?= $status === $lead['status'] ? 'selected' : '' ?>
                                            >
                                                <?= h(ucfirst($status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="admin-field">
                                    <label>Internal note</label>

                                    <textarea name="admin_note"><?= h((string)$lead['admin_note']) ?></textarea>
                                </div>

                                <button
                                    class="admin-button"
                                    type="submit"
                                >
                                    Update lead
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$leads): ?>
                    <tr>
                        <td colspan="5">
                            No leads found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
