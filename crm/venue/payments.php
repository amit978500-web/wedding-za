<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = 'venue';
$crmPage = 'payments';
$crmTitle = 'Payments';

$crmUser = wz_crm_require_role('venue');
$userId = (int)($crmUser['id'] ?? 0);

$message = '';
$isSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $result = wz_venue_add_payment(
            $userId,
            (int)($_POST['booking_id'] ?? 0),
            $_POST
        );

        $message = (string)$result['message'];
        $isSuccess = (bool)$result['ok'];
    }
}

$bookings = wz_venue_functions(
    $userId,
    'all'
);

$payments = wz_venue_payments(
    $userId
);

$totals = wz_venue_payment_totals(
    $userId
);

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            FINANCE
        </span>

        <h1>
            Payments
        </h1>

        <p>
            Record receipts and refunds against venue bookings and track the outstanding balance.
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="crm-grid">
    <article class="crm-stat">
        <span>Booked value</span>

        <strong style="font-size: 30px;">
            ₹<?= h(number_format((float)$totals['booked_value'])) ?>
        </strong>

        <small>Non-cancelled bookings</small>
    </article>

    <article class="crm-stat">
        <span>Received</span>

        <strong
            class="crm-money-positive"
            style="font-size: 30px;"
        >
            ₹<?= h(number_format((float)$totals['received'])) ?>
        </strong>

        <small>Net received after refunds</small>
    </article>

    <article class="crm-stat">
        <span>Balance due</span>

        <strong
            class="crm-money-due"
            style="font-size: 30px;"
        >
            ₹<?= h(number_format((float)$totals['balance'])) ?>
        </strong>

        <small>Against booked value</small>
    </article>

    <article class="crm-stat">
        <span>Refunded</span>

        <strong style="font-size: 30px;">
            ₹<?= h(number_format((float)$totals['refunded'])) ?>
        </strong>

        <small>Total refund entries</small>
    </article>
</div>

<div class="crm-layout">
    <section class="crm-panel">
        <div class="crm-panel-head">
            <div>
                <h2>Payment ledger</h2>
                <p>Every payment transaction recorded by the venue team.</p>
            </div>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Booking</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Reference</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>
                                <?= h(
                                    (string)(
                                        $payment['paid_at']
                                        ?: $payment['created_at']
                                    )
                                ) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= h((string)$payment['booking_title']) ?>
                                </strong>

                                <?php if (!empty($payment['event_date'])): ?>
                                    <br>
                                    <small>
                                        <?= h((string)$payment['event_date']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $payment['customer_name']
                                        ?: 'Customer'
                                    )
                                ) ?>
                            </td>

                            <td>
                                <strong>
                                    ₹<?= h(
                                        number_format(
                                            (float)$payment['amount']
                                        )
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= h(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            (string)$payment['method']
                                        )
                                    )
                                ) ?>
                            </td>

                            <td>
                                <span class="crm-badge <?= h((string)$payment['status']) ?>">
                                    <?= h(ucfirst((string)$payment['status'])) ?>
                                </span>
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $payment['reference']
                                        ?: '—'
                                    )
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$payments): ?>
                        <tr>
                            <td colspan="7">
                                No payments recorded yet.
                            </td>
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
                    <h2>Record payment</h2>
                    <p>Add a receipt, pending payment or refund.</p>
                </div>
            </div>

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
                    <label for="paymentBooking">
                        Booking
                    </label>

                    <select
                        id="paymentBooking"
                        name="booking_id"
                        required
                    >
                        <option value="">
                            Choose booking
                        </option>

                        <?php foreach ($bookings as $booking): ?>
                            <?php if ($booking['status'] === 'cancelled'): ?>
                                <?php continue; ?>
                            <?php endif; ?>

                            <option value="<?= h((string)$booking['id']) ?>">
                                <?= h(
                                    (string)$booking['title']
                                    . ' · '
                                    . (string)($booking['customer_name'] ?: 'Customer')
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="crm-field">
                    <label for="paymentAmount">
                        Amount
                    </label>

                    <input
                        id="paymentAmount"
                        type="number"
                        min="1"
                        step="0.01"
                        name="amount"
                        required
                    >
                </div>

                <div class="crm-field">
                    <label for="paymentMethod">
                        Method
                    </label>

                    <select
                        id="paymentMethod"
                        name="method"
                    >
                        <option value="bank_transfer">
                            Bank transfer
                        </option>

                        <option value="upi">
                            UPI
                        </option>

                        <option value="cash">
                            Cash
                        </option>

                        <option value="card">
                            Card
                        </option>

                        <option value="cheque">
                            Cheque
                        </option>

                        <option value="other">
                            Other
                        </option>
                    </select>
                </div>

                <div class="crm-field">
                    <label for="paymentStatus">
                        Status
                    </label>

                    <select
                        id="paymentStatus"
                        name="status"
                    >
                        <option value="received">
                            Received
                        </option>

                        <option value="pending">
                            Pending
                        </option>

                        <option value="refunded">
                            Refunded
                        </option>

                        <option value="failed">
                            Failed
                        </option>
                    </select>
                </div>

                <div class="crm-field">
                    <label for="paymentDate">
                        Payment date/time
                    </label>

                    <input
                        id="paymentDate"
                        type="datetime-local"
                        name="paid_at"
                    >
                </div>

                <div class="crm-field">
                    <label for="paymentReference">
                        Reference
                    </label>

                    <input
                        id="paymentReference"
                        name="reference"
                        placeholder="UTR / cheque / receipt"
                    >
                </div>

                <div class="crm-field">
                    <label for="paymentNotes">
                        Notes
                    </label>

                    <textarea
                        id="paymentNotes"
                        name="notes"
                    ></textarea>
                </div>

                <button
                    class="crm-button"
                    type="submit"
                >
                    Record payment
                </button>
            </form>
        </section>
    </aside>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
