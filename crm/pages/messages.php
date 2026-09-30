<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';

$crmRole = $crmRole ?? 'host';
$crmPage = 'messages';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)($crmUser['id'] ?? 0);
$message = '';
$isSuccess = false;

$contacts = wz_crm_allowed_contacts(
    $userId,
    $crmRole
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $recipientId = (int)($_POST['recipient_id'] ?? 0);
        $body = trim(
            (string)($_POST['message'] ?? '')
        );

        $attachmentUrl = trim(
            (string)($_POST['attachment_url'] ?? '')
        );

        if (
            $recipientId > 0
            && $body !== ''
            && wz_crm_can_message(
                $userId,
                $crmRole,
                $recipientId
            )
        ) {
            $pdo = wz_db();

            $statement = $pdo->prepare(
                'INSERT INTO crm_messages (
                    sender_user_id,
                    recipient_user_id,
                    body,
                    attachment_url
                ) VALUES (
                    :sender_user_id,
                    :recipient_user_id,
                    :body,
                    :attachment_url
                )'
            );

            $statement->execute([
                'sender_user_id' => $userId,
                'recipient_user_id' => $recipientId,
                'body' => $body,
                'attachment_url' => $attachmentUrl !== ''
                    ? $attachmentUrl
                    : null,
            ]);

            wz_marketplace_notify(
                $recipientId,
                'message',
                'New CRM message',
                mb_substr($body, 0, 180),
                $crmRole === 'host'
                    ? 'crm/customer/messages.php'
                    : (
                        $crmRole === 'venue'
                            ? 'crm/venue/messages.php'
                            : 'crm/vendor/messages.php'
                    )
            );

            if ($crmRole !== 'host') {
                wz_marketplace_record_business_event(
                    $userId,
                    $crmRole,
                    'message',
                    $recipientId
                );
            } else {
                $recipient = null;

                foreach ($contacts as $contact) {
                    if ((int)$contact['id'] === $recipientId) {
                        $recipient = $contact;
                        break;
                    }
                }

                if (
                    $recipient
                    && in_array(
                        (string)$recipient['role'],
                        ['vendor', 'venue'],
                        true
                    )
                ) {
                    wz_marketplace_record_business_event(
                        $recipientId,
                        (string)$recipient['role'],
                        'message',
                        $userId
                    );
                }
            }

            $message = 'Message sent.';
            $isSuccess = true;
        } else {
            $message = 'Choose a valid CRM contact and enter a message.';
        }
    }
}

$pdo = wz_db();

if ($pdo) {
    $read = $pdo->prepare(
        'UPDATE crm_messages
         SET read_at = NOW()
         WHERE recipient_user_id = :recipient_user_id
         AND read_at IS NULL'
    );

    $read->execute([
        'recipient_user_id' => $userId,
    ]);
}

$messages = wz_crm_messages_for_user(
    $userId
);

$crmTitle = 'Messages';

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            COMMUNICATION
        </span>

        <h1>
            Messages
        </h1>

        <p>
            Keep customer and business communication attached to your Wedding Za CRM.
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="crm-layout">
    <section class="crm-panel">
        <div class="crm-panel-head">
            <div>
                <h2>
                    Conversation history
                </h2>
            </div>
        </div>

        <div style="padding-top: 20px;">
            <?php foreach (array_reverse($messages) as $item): ?>
                <div
                    class="crm-message <?= (int)$item['sender_user_id'] === $userId ? 'mine' : '' ?>"
                >
                    <strong>
                        <?= h(
                            (string)(
                                (int)$item['sender_user_id'] === $userId
                                    ? 'You'
                                    : $item['sender_name']
                            )
                        ) ?>
                    </strong>

                    <p>
                        <?= nl2br(h((string)$item['body'])) ?>
                    </p>

                    <?php if (!empty($item['attachment_url'])): ?>
                        <p>
                            <a
                                href="<?= h((string)$item['attachment_url']) ?>"
                                target="_blank"
                                rel="noopener"
                            >
                                Open attachment ↗
                            </a>
                        </p>
                    <?php endif; ?>

                    <small>
                        <?= h((string)$item['created_at']) ?>
                    </small>
                </div>
            <?php endforeach; ?>

            <?php if (!$messages): ?>
                <div class="crm-empty">
                    No CRM messages yet.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <aside>
        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>
                        New message
                    </h2>
                </div>
            </div>

            <?php if ($contacts): ?>
                <form
                    method="post"
                    class="crm-form"
                >
                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= h(wz_csrf_token()) ?>"
                    >

                    <div class="crm-field">
                        <label for="messageRecipient">
                            Contact
                        </label>

                        <select
                            id="messageRecipient"
                            name="recipient_id"
                            required
                        >
                            <option value="">
                                Choose contact
                            </option>

                            <?php foreach ($contacts as $contact): ?>
                                <option value="<?= h((string)$contact['id']) ?>">
                                    <?= h((string)$contact['name']) ?>
                                    ·
                                    <?= h(wz_crm_role_label((string)$contact['role'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="crm-field">
                        <label for="newMessage">
                            Message
                        </label>

                        <textarea
                            id="newMessage"
                            name="message"
                            required
                        ></textarea>
                    </div>

                    <div class="crm-field">
                        <label for="messageAttachment">
                            Attachment URL (optional)
                        </label>

                        <input
                            id="messageAttachment"
                            type="url"
                            name="attachment_url"
                            placeholder="Drive, Dropbox or file link"
                        >
                    </div>

                    <button
                        class="crm-button"
                        type="submit"
                    >
                        Send message
                    </button>
                </form>
            <?php else: ?>
                <div class="crm-empty">
                    Contacts appear after an enquiry or booking connects you with another account.
                </div>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
