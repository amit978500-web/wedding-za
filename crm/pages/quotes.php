<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';

$crmRole = $crmRole ?? 'vendor';
$crmPage = 'quotes';
$crmTitle = 'Quotes';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)$crmUser['id'];
$pdo = wz_db();
$message = '';
$isSuccess = false;

$enquiries = wz_crm_enquiries_for_user(
    $userId,
    $crmRole
);

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $enquiryId = (int)($_POST['enquiry_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $subtotal = max(0, (float)($_POST['subtotal'] ?? 0));
        $tax = max(0, (float)($_POST['tax_amount'] ?? 0));
        $validUntil = trim((string)($_POST['valid_until'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));

        $enquiry = null;

        foreach ($enquiries as $candidate) {
            if ((int)$candidate['id'] === $enquiryId) {
                $enquiry = $candidate;
                break;
            }
        }

        if (!$enquiry || empty($enquiry['customer_user_id'])) {
            $message = 'Choose an enquiry linked to a customer account.';
        } elseif ($title === '') {
            $message = 'Enter a quote title.';
        } else {
            $quoteNumber =
                'WZQ-'
                . date('Ym')
                . '-'
                . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

            $statement = $pdo->prepare(
                'INSERT INTO crm_quotes (
                    enquiry_id,
                    customer_user_id,
                    business_user_id,
                    business_type,
                    quote_number,
                    title,
                    subtotal,
                    tax_amount,
                    total_amount,
                    status,
                    valid_until,
                    notes
                ) VALUES (
                    :enquiry_id,
                    :customer_user_id,
                    :business_user_id,
                    :business_type,
                    :quote_number,
                    :title,
                    :subtotal,
                    :tax_amount,
                    :total_amount,
                    "sent",
                    :valid_until,
                    :notes
                )'
            );

            $statement->execute([
                'enquiry_id' => $enquiryId,
                'customer_user_id' => (int)$enquiry['customer_user_id'],
                'business_user_id' => $userId,
                'business_type' => $crmRole,
                'quote_number' => $quoteNumber,
                'title' => $title,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $subtotal + $tax,
                'valid_until' => $validUntil !== '' ? $validUntil : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            $quoteId = (int)$pdo->lastInsertId();

            wz_marketplace_notify(
                (int)$enquiry['customer_user_id'],
                'quote',
                'New quote received',
                $title.' · ₹'.number_format($subtotal + $tax),
                'crm/customer/finances.php'
            );

            wz_marketplace_record_business_event(
                $userId,
                $crmRole,
                'quote',
                (int)$enquiry['customer_user_id'],
                $quoteId
            );

            $message = 'Quote sent to the customer.';
            $isSuccess = true;
        }
    }
}

$rows = [];

if ($pdo) {
    $statement = $pdo->prepare(
        'SELECT q.*, u.name AS customer_name
         FROM crm_quotes q
         INNER JOIN users u
            ON u.id = q.customer_user_id
         WHERE q.business_user_id = :business_user_id
         AND q.business_type = :business_type
         ORDER BY q.created_at DESC'
    );

    $statement->execute([
        'business_user_id' => $userId,
        'business_type' => $crmRole,
    ]);

    $rows = $statement->fetchAll();
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">PROPOSALS</span>
        <h1>Quotes</h1>
        <p>
            Turn qualified enquiries into clear commercial proposals that customers can accept or reject from their CRM.
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
                <h2>Sent quotes</h2>
                <p><?= h((string)count($rows)) ?> quote(s)</p>
            </div>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>Quote</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Valid until</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $quote): ?>
                        <tr>
                            <td>
                                <strong><?= h((string)$quote['title']) ?></strong>
                                <br>
                                <small><?= h((string)$quote['quote_number']) ?></small>
                            </td>
                            <td><?= h((string)$quote['customer_name']) ?></td>
                            <td>₹<?= h(number_format((float)$quote['total_amount'])) ?></td>
                            <td><?= h((string)($quote['valid_until'] ?: '—')) ?></td>
                            <td>
                                <span class="crm-badge <?= h((string)$quote['status']) ?>">
                                    <?= h(ucfirst((string)$quote['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="5">No quotes created yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside>
        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>Create quote</h2>
                </div>
            </div>

            <form method="post" class="crm-form">
                <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">

                <div class="crm-field">
                    <label for="quoteEnquiry">Enquiry</label>
                    <select id="quoteEnquiry" name="enquiry_id" required>
                        <option value="">Choose enquiry</option>
                        <?php foreach ($enquiries as $enquiry): ?>
                            <?php if (!empty($enquiry['customer_user_id'])): ?>
                                <option value="<?= h((string)$enquiry['id']) ?>">
                                    #<?= h((string)$enquiry['id']) ?>
                                    · <?= h((string)($enquiry['customer_name'] ?: 'Customer')) ?>
                                    · <?= h((string)($enquiry['event_type'] ?: 'Event')) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="crm-field">
                    <label for="quoteTitle">Title</label>
                    <input id="quoteTitle" name="title" required placeholder="Wedding photography package">
                </div>

                <div class="crm-field">
                    <label for="quoteSubtotal">Subtotal</label>
                    <input id="quoteSubtotal" type="number" min="0" step="1" name="subtotal" required>
                </div>

                <div class="crm-field">
                    <label for="quoteTax">Tax</label>
                    <input id="quoteTax" type="number" min="0" step="1" name="tax_amount" value="0">
                </div>

                <div class="crm-field">
                    <label for="quoteValid">Valid until</label>
                    <input id="quoteValid" type="date" name="valid_until">
                </div>

                <div class="crm-field">
                    <label for="quoteNotes">Notes</label>
                    <textarea id="quoteNotes" name="notes"></textarea>
                </div>

                <button class="crm-button" type="submit">
                    Send quote
                </button>
            </form>
        </section>
    </aside>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
