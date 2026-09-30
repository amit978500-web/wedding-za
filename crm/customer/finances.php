<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';

$crmRole = 'host';
$crmPage = 'finances';
$crmTitle = 'Quotes & Payments';
$crmUser = wz_crm_require_role('host');
$userId = (int)$crmUser['id'];
$pdo = wz_db();
$message = '';
$isSuccess = false;

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $quoteId = (int)($_POST['quote_id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');

        if (in_array($status, ['accepted', 'rejected'], true)) {
            $statement = $pdo->prepare(
                'UPDATE crm_quotes
                 SET status = :status
                 WHERE id = :id
                 AND customer_user_id = :customer_user_id
                 AND status = "sent"'
            );

            $statement->execute([
                'status' => $status,
                'id' => $quoteId,
                'customer_user_id' => $userId,
            ]);

            $quote = $pdo->prepare(
                'SELECT business_user_id
                 FROM crm_quotes
                 WHERE id = :id
                 AND customer_user_id = :customer_user_id'
            );

            $quote->execute([
                'id' => $quoteId,
                'customer_user_id' => $userId,
            ]);

            $businessUserId = (int)$quote->fetchColumn();

            if ($businessUserId > 0) {
                wz_marketplace_notify(
                    $businessUserId,
                    'quote',
                    'Customer updated a quote',
                    'Quote #'.$quoteId.' was '.$status.'.',
                    null
                );
            }

            $message = 'Quote '.$status.'.';
            $isSuccess = true;
        }
    }
}

$quotes = wz_marketplace_customer_quotes($userId);
$invoices = wz_marketplace_customer_invoices($userId);
$payments = wz_marketplace_customer_payments($userId);

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">COMMERCIALS</span>
        <h1>Quotes & payments</h1>
        <p>
            Review vendor and venue proposals, accept or reject quotes, and keep invoices visible beside your bookings.
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Quotes</h2>
            <p><?= h((string)count($quotes)) ?> quote(s)</p>
        </div>
    </div>

    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Quote</th>
                    <th>Business</th>
                    <th>Total</th>
                    <th>Valid until</th>
                    <th>Status</th>
                    <th>Decision</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotes as $quote): ?>
                    <tr>
                        <td>
                            <strong><?= h((string)$quote['title']) ?></strong>
                            <br>
                            <small><?= h((string)$quote['quote_number']) ?></small>
                        </td>
                        <td><?= h((string)($quote['business_contact'] ?: 'Business')) ?></td>
                        <td>₹<?= h(number_format((float)$quote['total_amount'])) ?></td>
                        <td><?= h((string)($quote['valid_until'] ?: '—')) ?></td>
                        <td>
                            <span class="crm-badge <?= h((string)$quote['status']) ?>">
                                <?= h(ucfirst((string)$quote['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($quote['status'] === 'sent'): ?>
                                <form method="post" class="crm-inline">
                                    <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                                    <input type="hidden" name="quote_id" value="<?= h((string)$quote['id']) ?>">
                                    <button class="crm-button small" name="status" value="accepted" type="submit">
                                        Accept
                                    </button>
                                    <button class="crm-button secondary small" name="status" value="rejected" type="submit">
                                        Reject
                                    </button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$quotes): ?>
                    <tr>
                        <td colspan="6">No quotes yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Invoices</h2>
            <p><?= h((string)count($invoices)) ?> invoice(s)</p>
        </div>
    </div>

    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Booking</th>
                    <th>Total</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th>Invoice</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><strong><?= h((string)$invoice['invoice_number']) ?></strong></td>
                        <td><?= h((string)($invoice['booking_title'] ?: 'Booking')) ?></td>
                        <td>₹<?= h(number_format((float)$invoice['total_amount'])) ?></td>
                        <td><?= h((string)($invoice['due_date'] ?: '—')) ?></td>
                        <td>
                            <span class="crm-badge <?= h((string)$invoice['status']) ?>">
                                <?= h(ucfirst((string)$invoice['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <a
                                class="crm-button secondary small"
                                href="<?= h(
                                    wz_app_url(
                                        'crm/customer/invoice.php?id='
                                        . urlencode((string)$invoice['id'])
                                    )
                                ) ?>"
                            >
                                View / Print
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$invoices): ?>
                    <tr>
                        <td colspan="6">No invoices yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Payment history</h2>
            <p><?= h((string)count($payments)) ?> payment record(s)</p>
        </div>
    </div>

    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Booking</th>
                    <th>Business</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Status</th>
                    <th>Paid</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= h((string)($payment['booking_title'] ?: 'Booking')) ?></td>
                        <td><?= h((string)($payment['business_contact'] ?: 'Business')) ?></td>
                        <td>₹<?= h(number_format((float)$payment['amount'])) ?></td>
                        <td><?= h(ucfirst(str_replace('_',' ',(string)$payment['method']))) ?></td>
                        <td><?= h((string)($payment['reference'] ?: '—')) ?></td>
                        <td>
                            <span class="crm-badge <?= h((string)$payment['status']) ?>">
                                <?= h(ucfirst((string)$payment['status'])) ?>
                            </span>
                        </td>
                        <td><?= h((string)($payment['paid_at'] ?: $payment['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$payments): ?>
                    <tr>
                        <td colspan="7">No payment records yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
