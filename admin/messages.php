<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$pdo = wz_db();
$message = '';
$isSuccess = false;

if (
    $pdo
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $recipientId = (int)($_POST['recipient_user_id'] ?? 0);
        $body = trim((string)($_POST['message'] ?? ''));

        if (
            $recipientId > 0
            && $body !== ''
        ) {
            $statement = $pdo->prepare(
                'INSERT INTO crm_messages (
                    sender_user_id,
                    recipient_user_id,
                    body
                ) VALUES (
                    :sender_user_id,
                    :recipient_user_id,
                    :body
                )'
            );

            $statement->execute([
                'sender_user_id' => (int)wz_user()['id'],
                'recipient_user_id' => $recipientId,
                'body' => $body,
            ]);

            $message = 'Message sent.';
            $isSuccess = true;
        }
    }
}

$users = [];

if ($pdo) {
    $users = $pdo
        ->query(
            "SELECT id, name, email, role
             FROM users
             WHERE status = 'active'
             AND role <> 'admin'
             ORDER BY role, name"
        )
        ->fetchAll();
}

$messages = [];

if ($pdo) {
    $messages = $pdo
        ->query(
            'SELECT
                cm.*,
                su.name AS sender_name,
                su.role AS sender_role,
                ru.name AS recipient_name,
                ru.role AS recipient_role
             FROM crm_messages cm
             INNER JOIN users su
                ON su.id = cm.sender_user_id
             INNER JOIN users ru
                ON ru.id = cm.recipient_user_id
             ORDER BY cm.created_at DESC
             LIMIT 300'
        )
        ->fetchAll();
}

$adminPage = 'messages';
$adminTitle = 'CRM Messages';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>COMMUNICATION</small>
        <h1>Messages</h1>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Send message</h2>
    </div>

    <form
        method="post"
        class="admin-form"
    >
        <input
            type="hidden"
            name="csrf"
            value="<?= h(wz_csrf_token()) ?>"
        >

        <div class="admin-form-grid">
            <div class="admin-field">
                <label for="messageRecipient">
                    Recipient
                </label>

                <select
                    id="messageRecipient"
                    name="recipient_user_id"
                    required
                >
                    <option value="">
                        Choose user
                    </option>

                    <?php foreach ($users as $user): ?>
                        <option value="<?= h((string)$user['id']) ?>">
                            <?= h(
                                ucfirst((string)$user['role'])
                                . ' · '
                                . (string)$user['name']
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-field full">
                <label for="adminMessage">
                    Message
                </label>

                <textarea
                    id="adminMessage"
                    name="message"
                    required
                ></textarea>
            </div>
        </div>

        <button
            class="admin-button"
            type="submit"
        >
            Send message
        </button>
    </form>
</section>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Message history</h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>To</th>
                    <th>Message</th>
                    <th>Sent</th>
                    <th>Read</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($messages as $item): ?>
                    <tr>
                        <td>
                            <?= h((string)$item['sender_name']) ?>
                            <br>
                            <small>
                                <?= h((string)$item['sender_role']) ?>
                            </small>
                        </td>

                        <td>
                            <?= h((string)$item['recipient_name']) ?>
                            <br>
                            <small>
                                <?= h((string)$item['recipient_role']) ?>
                            </small>
                        </td>

                        <td>
                            <?= nl2br(h((string)$item['body'])) ?>
                        </td>

                        <td>
                            <?= h((string)$item['created_at']) ?>
                        </td>

                        <td>
                            <?= h((string)($item['read_at'] ?: 'Unread')) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$messages): ?>
                    <tr>
                        <td colspan="5">
                            No CRM messages yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
