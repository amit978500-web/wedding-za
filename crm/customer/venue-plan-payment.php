<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$crmRole = 'host';
$crmPage = 'find-venues';
$crmTitle = 'Venue Plan Payment';
$crmUser = wz_crm_require_role($crmRole);

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">PAYMENT</span>
        <h1>One Wedding plan</h1>
        <p>Review the service before payment. The live payment gateway is not connected yet, so no charge will be attempted from this screen.</p>
    </div>

    <div class="crm-actions">
        <a
            class="crm-button secondary"
            href="<?= h(wz_app_url('crm/customer/find-venues.php')) ?>"
        >
            Back to Find Venues
        </a>
    </div>
</div>

<div class="crm-payment-layout">
    <section class="crm-panel">
        <div class="crm-order-summary">
            <span class="crm-eyebrow">ORDER SUMMARY</span>

            <h2>Find Your Perfect Wedding Venue</h2>

            <div class="crm-order-line">
                <span>Plan</span>
                <strong>One Wedding</strong>
            </div>

            <div class="crm-order-line">
                <span>Venue search assistance</span>
                <strong>Included</strong>
            </div>

            <div class="crm-order-line">
                <span>Curated recommendations</span>
                <strong>Included</strong>
            </div>

            <div class="crm-order-line">
                <span>Enquiry + site-visit support</span>
                <strong>Included</strong>
            </div>

            <div class="crm-order-line">
                <span>Dedicated Weddingza support</span>
                <strong>Included</strong>
            </div>

            <div class="crm-order-line total">
                <span>Total</span>
                <strong>₹1,000</strong>
            </div>
        </div>
    </section>

    <aside class="crm-panel">
        <div class="crm-payment-ready">
            <span class="crm-eyebrow">SECURE CHECKOUT</span>

            <strong>Payment gateway ready point</strong>

            <p>
                The CRM now has the full plan and checkout UI. A payment provider such as Razorpay still needs to be connected before real transactions can be accepted.
            </p>

            <button
                class="crm-payment-disabled"
                type="button"
                disabled
            >
                Pay ₹1,000
            </button>
        </div>
    </aside>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
