<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-crm.php';

wz_admin_crm_require_admin();

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
        $action = (string)($_POST['action'] ?? 'create');

        if ($action === 'status') {
            $invoiceId = (int)($_POST['invoice_id'] ?? 0);
            $status = (string)($_POST['status'] ?? 'draft');

            if (!in_array(
                $status,
                [
                    'draft',
                    'issued',
                    'paid',
                    'overdue',
                    'void',
                ],
                true
            )) {
                $status = 'draft';
            }

            $statement = $pdo->prepare(
                'UPDATE crm_invoices
                 SET status = :status,
                     issued_at = CASE
                         WHEN :issued_status = "issued"
                         AND issued_at IS NULL
                         THEN NOW()
                         ELSE issued_at
                     END
                 WHERE id = :id'
            );

            $statement->execute([
                'status' => $status,
                'issued_status' => $status,
                'id' => $invoiceId,
            ]);

            wz_audit(
                'admin.invoice.status_updated',
                'crm_invoice',
                $invoiceId,
                [
                    'status' => $status,
                ]
            );

            $message = 'Invoice status updated.';
            $isSuccess = true;
        } else {
            $result = wz_admin_crm_create_invoice(
                $_POST
            );

            $message = (string)$result['message'];
            $isSuccess = (bool)$result['ok'];
        }
    }
}

$invoices = wz_admin_crm_invoices();
$bookings = wz_admin_crm_functions('all');

$adminPage = 'payments-invoices';
$adminTitle = 'Invoices';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>PAYMENTS</small>
        <h1>Invoices</h1>
        <p class="admin-section-note">
            Issue and track booking invoices across venues and vendors.
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="admin-grid">
    <article class="admin-stat">
        <span>Invoices</span>
        <strong><?= h((string)count($invoices)) ?></strong>
    </article>

    <article class="admin-stat">
        <span>Issued value</span>
        <strong style="font-size: 30px;">
            ₹<?= h(
                number_format(
                    array_sum(
                        array_map(
                            fn (array $invoice): float =>
                                in_array(
                                    $invoice['status'],
                                    [
                                        'issued',
                                        'paid',
                                        'overdue',
                                    ],
                                    true
                                )
                                    ? (float)$invoice['total_amount']
                                    : 0.0,
                            $invoices
                        )
                    )
                )
            ) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Paid</span>
        <strong>
            <?= h(
                (string)count(
                    array_filter(
                        $invoices,
                        fn (array $invoice): bool =>
                            $invoice['status'] === 'paid'
                    )
                )
            ) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Overdue</span>
        <strong>
            <?= h(
                (string)count(
                    array_filter(
                        $invoices,
                        fn (array $invoice): bool =>
                            $invoice['status'] === 'overdue'
                    )
                )
            ) ?>
        </strong>
    </article>
</div>

<div
    style="
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 28px;
        align-items: start;
    "
>
    <section class="admin-panel">
        <div class="admin-panel-head">
            <h2>Invoice register</h2>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Booking</th>
                        <th>Customer</th>
                        <th>Business</th>
                        <th>Total</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= h((string)$invoice['invoice_number']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= h((string)$invoice['booking_title']) ?>

                                <?php if (!empty($invoice['event_date'])): ?>
                                    <br>
                                    <small>
                                        <?= h((string)$invoice['event_date']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $invoice['customer_name']
                                        ?: 'Customer'
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $invoice['business_contact']
                                        ?: 'Business'
                                    )
                                ) ?>
                            </td>

                            <td>
                                ₹<?= h(
                                    number_format(
                                        (float)$invoice['total_amount']
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $invoice['due_date']
                                        ?: '—'
                                    )
                                ) ?>
                            </td>

                            <td>
                                <span class="admin-badge <?= h((string)$invoice['status']) ?>">
                                    <?= h(ucfirst((string)$invoice['status'])) ?>
                                </span>
                            </td>

                            <td>
                                <form
                                    method="post"
                                    class="admin-inline-form"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?= h(wz_csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="status"
                                    >

                                    <input
                                        type="hidden"
                                        name="invoice_id"
                                        value="<?= h((string)$invoice['id']) ?>"
                                    >

                                    <select name="status">
                                        <?php foreach (
                                            [
                                                'draft',
                                                'issued',
                                                'paid',
                                                'overdue',
                                                'void',
                                            ] as $status
                                        ): ?>
                                            <option
                                                value="<?= h($status) ?>"
                                                <?= $status === $invoice['status'] ? 'selected' : '' ?>
                                            >
                                                <?= h(ucfirst($status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button
                                        class="admin-button"
                                        type="submit"
                                    >
                                        Save
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$invoices): ?>
                        <tr>
                            <td colspan="8">
                                No invoices yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside class="admin-panel">
        <div class="admin-panel-head">
            <h2>New invoice</h2>
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

            <input
                type="hidden"
                name="action"
                value="create"
            >

            <div class="admin-field">
                <label>Invoice number</label>

                <input
                    name="invoice_number"
                    value="<?= h(wz_admin_crm_next_invoice_number()) ?>"
                    required
                >
            </div>

            <div class="admin-field">
                <label>Booking</label>

                <select
                    name="booking_id"
                    required
                >
                    <option value="">
                        Choose booking
                    </option>

                    <?php foreach ($bookings as $booking): ?>
                        <option value="<?= h((string)$booking['id']) ?>">
                            <?= h(
                                (string)$booking['title']
                                . ' · '
                                . (string)(
                                    $booking['business_contact']
                                    ?: 'Business'
                                )
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-field">
                <label>Subtotal</label>

                <input
                    type="number"
                    min="0"
                    step="0.01"
                    name="subtotal"
                    required
                >
            </div>

            <div class="admin-field">
                <label>Tax amount</label>

                <input
                    type="number"
                    min="0"
                    step="0.01"
                    name="tax_amount"
                    value="0"
                >
            </div>

            <div class="admin-field">
                <label>Status</label>

                <select name="status">
                    <option value="draft">Draft</option>
                    <option value="issued">Issued</option>
                </select>
            </div>

            <div class="admin-field">
                <label>Due date</label>

                <input
                    type="date"
                    name="due_date"
                >
            </div>

            <div class="admin-field">
                <label>Notes</label>

                <textarea name="notes"></textarea>
            </div>

            <button
                class="admin-button"
                type="submit"
            >
                Create invoice
            </button>
        </form>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
