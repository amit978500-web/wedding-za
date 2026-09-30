<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = 'host';
$crmPage = 'finances';
$crmTitle = 'Invoice';
$crmUser = wz_crm_require_role('host');
$userId = (int)$crmUser['id'];
$invoiceId = (int)($_GET['id'] ?? 0);
$pdo = wz_db();
$invoice = null;

if ($pdo && $invoiceId > 0) {
    $statement = $pdo->prepare(
        'SELECT
            i.*,
            b.title AS booking_title,
            b.event_type,
            b.event_date,
            business.name AS business_name,
            business.email AS business_email,
            customer.name AS customer_name,
            customer.email AS customer_email
         FROM crm_invoices i
         INNER JOIN crm_bookings b
            ON b.id = i.booking_id
         LEFT JOIN users business
            ON business.id = i.business_user_id
         LEFT JOIN users customer
            ON customer.id = i.customer_user_id
         WHERE i.id = :id
         AND i.customer_user_id = :customer_user_id
         LIMIT 1'
    );

    $statement->execute([
        'id' => $invoiceId,
        'customer_user_id' => $userId,
    ]);

    $invoice = $statement->fetch() ?: null;
}

if (!$invoice) {
    http_response_code(404);

    header(
        'Location: ' .
        wz_app_url(
            'crm/customer/finances.php'
        )
    );

    exit;
}

require dirname(__DIR__) . '/includes/header.php';
?>

<style>
@media print {
    .crm-sidebar,
    .crm-mobile-toggle,
    .crm-sidebar-backdrop,
    .crm-actions,
    .crm-user {
        display: none !important;
    }

    .crm-shell {
        display: block !important;
    }

    .crm-main {
        max-width: none !important;
        padding: 0 !important;
    }

    .crm-head,
    .crm-panel {
        box-shadow: none !important;
    }

    body {
        background: #fff !important;
    }
}
</style>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">INVOICE</span>
        <h1><?= h((string)$invoice['invoice_number']) ?></h1>
        <p>
            <?= h((string)($invoice['booking_title'] ?: 'Wedding Za booking')) ?>
        </p>
    </div>

    <div class="crm-actions">
        <button
            class="crm-button"
            type="button"
            onclick="window.print()"
        >
            Print / Save PDF
        </button>

        <a
            class="crm-button secondary"
            href="<?= h(wz_app_url('crm/customer/finances.php')) ?>"
        >
            Back
        </a>
    </div>
</div>

<section class="crm-panel">
    <div class="crm-order-summary">
        <span class="crm-eyebrow">BILLING DETAILS</span>

        <h2>
            <?= h((string)($invoice['business_name'] ?: 'Wedding Za business')) ?>
        </h2>

        <div class="crm-order-line">
            <span>Customer</span>
            <strong><?= h((string)($invoice['customer_name'] ?: $crmUser['name'])) ?></strong>
        </div>

        <div class="crm-order-line">
            <span>Customer email</span>
            <strong><?= h((string)($invoice['customer_email'] ?: $crmUser['email'])) ?></strong>
        </div>

        <div class="crm-order-line">
            <span>Booking</span>
            <strong><?= h((string)$invoice['booking_title']) ?></strong>
        </div>

        <div class="crm-order-line">
            <span>Event</span>
            <strong>
                <?= h((string)($invoice['event_type'] ?: 'Event')) ?>
                <?php if (!empty($invoice['event_date'])): ?>
                    · <?= h((string)$invoice['event_date']) ?>
                <?php endif; ?>
            </strong>
        </div>

        <div class="crm-order-line">
            <span>Subtotal</span>
            <strong>₹<?= h(number_format((float)$invoice['subtotal'])) ?></strong>
        </div>

        <div class="crm-order-line">
            <span>Tax</span>
            <strong>₹<?= h(number_format((float)$invoice['tax_amount'])) ?></strong>
        </div>

        <div class="crm-order-line total">
            <span>Total</span>
            <strong>₹<?= h(number_format((float)$invoice['total_amount'])) ?></strong>
        </div>

        <div class="crm-order-line">
            <span>Status</span>
            <strong><?= h(ucfirst((string)$invoice['status'])) ?></strong>
        </div>

        <div class="crm-order-line">
            <span>Issued</span>
            <strong><?= h((string)($invoice['issued_at'] ?: '—')) ?></strong>
        </div>

        <div class="crm-order-line">
            <span>Due date</span>
            <strong><?= h((string)($invoice['due_date'] ?: '—')) ?></strong>
        </div>

        <?php if (!empty($invoice['notes'])): ?>
            <div class="crm-order-line">
                <span>Notes</span>
                <strong><?= nl2br(h((string)$invoice['notes'])) ?></strong>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
