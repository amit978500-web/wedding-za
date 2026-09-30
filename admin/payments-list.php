<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-crm.php';

wz_admin_crm_require_admin();

$paymentFilter = $paymentFilter ?? null;
$adminPage = $adminPage ?? 'payments-all';
$adminTitle = $adminTitle ?? 'Payments';

$payments = wz_admin_crm_payments(
    $paymentFilter
);

$totals = wz_admin_crm_payment_totals();

$isRefundView = $paymentFilter === 'refunded';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>PAYMENTS</small>

        <h1>
            <?= $isRefundView ? 'Refunds' : 'Payments' ?>
        </h1>

        <p class="admin-section-note">
            <?= $isRefundView
                ? 'Refund transactions recorded against bookings.'
                : 'Platform payment transaction ledger across venues and vendors.' ?>
        </p>
    </div>
</div>

<div class="admin-grid">
    <article class="admin-stat">
        <span>Received</span>
        <strong
            class="admin-money-positive"
            style="font-size: 30px;"
        >
            ₹<?= h(number_format((float)$totals['received'])) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Refunded</span>
        <strong style="font-size: 30px;">
            ₹<?= h(number_format((float)$totals['refunded'])) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Pending</span>
        <strong
            class="admin-money-due"
            style="font-size: 30px;"
        >
            ₹<?= h(number_format((float)$totals['pending'])) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Transactions shown</span>
        <strong><?= h((string)count($payments)) ?></strong>
    </article>
</div>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>
            <?= $isRefundView ? 'Refund ledger' : 'Payment ledger' ?>
        </h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Booking</th>
                    <th>Customer</th>
                    <th>Business</th>
                    <th>Type</th>
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
                            <?= h(
                                (string)(
                                    $payment['business_contact']
                                    ?: 'Business'
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= h(ucfirst((string)$payment['business_type'])) ?>
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
                            <span class="admin-badge <?= h((string)$payment['status']) ?>">
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
                        <td colspan="9">
                            No transactions found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
