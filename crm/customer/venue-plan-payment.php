<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';
require_once dirname(__DIR__, 2) . '/includes/payments.php';

$crmRole = 'host';
$crmPage = 'find-venues';
$crmTitle = 'Venue Plan Payment';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)($crmUser['id'] ?? 0);

$purchaseId = wz_marketplace_create_venue_plan_purchase(
    $userId
);

$purchase = wz_marketplace_venue_plan_purchase(
    $userId
);

$razorpayConfigured = wz_razorpay_is_configured();

$purchaseStatus = (string)(
    $purchase['status']
    ?? 'pending'
);

$isPaid = in_array(
    $purchaseStatus,
    [
        'paid',
        'active',
        'completed',
    ],
    true
);

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">PAYMENT</span>
        <h1>One Wedding plan</h1>
        <p>
            Review your Weddingza venue-assistance service and complete the one-time ₹1,000 payment securely.
        </p>
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

<?php if ($isPaid): ?>
    <div class="crm-notice success">
        Payment received. Your plan status is
        <strong><?= h(ucfirst($purchaseStatus)) ?></strong>.
        Weddingza support can now activate or manage your venue-assistance workflow.
    </div>
<?php endif; ?>

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
                <span>Shortlisted venues</span>
                <strong>Included</strong>
            </div>

            <div class="crm-order-line">
                <span>Venue enquiry support</span>
                <strong>Included</strong>
            </div>

            <div class="crm-order-line">
                <span>Site-visit assistance</span>
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

            <?php if ($isPaid): ?>
                <strong>Payment completed</strong>

                <p>
                    Purchase #<?= h((string)($purchase['id'] ?? $purchaseId ?? '')) ?>
                    is currently
                    <strong><?= h(ucfirst($purchaseStatus)) ?></strong>.
                    You do not need to pay again.
                </p>

                <a
                    class="crm-button"
                    href="<?= h(wz_app_url('crm/customer/find-venues.php')) ?>"
                >
                    Continue venue planning
                </a>
            <?php elseif ($razorpayConfigured): ?>
                <strong>Pay securely with Razorpay</strong>

                <p>
                    Your payment is created server-side and verified before Weddingza marks the plan as paid.
                </p>

                <div
                    class="crm-notice"
                    id="venuePlanPaymentMessage"
                    hidden
                ></div>

                <button
                    class="crm-button"
                    id="venuePlanPayButton"
                    type="button"
                    style="width:100%;"
                >
                    Pay ₹1,000
                </button>

                <small class="crm-kpi-note">
                    Purchase #<?= h((string)($purchase['id'] ?? $purchaseId ?? '')) ?>
                </small>
            <?php else: ?>
                <strong>Payment setup required</strong>

                <p>
                    The checkout flow is ready, but Razorpay keys have not been added to this environment yet.
                </p>

                <button
                    class="crm-payment-disabled"
                    type="button"
                    disabled
                >
                    Pay ₹1,000
                </button>

                <small class="crm-kpi-note">
                    Add Razorpay credentials to <code>config.local.php</code> or the supported environment variables to enable payment.
                </small>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php if (!$isPaid && $razorpayConfigured): ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <script>
    (() => {
        'use strict';

        const button = document.getElementById(
            'venuePlanPayButton'
        );

        const message = document.getElementById(
            'venuePlanPaymentMessage'
        );

        if (!button) {
            return;
        }

        const showMessage = (
            text,
            success = false
        ) => {
            if (!message) {
                return;
            }

            message.hidden = false;
            message.textContent = text;
            message.classList.toggle(
                'success',
                success
            );
        };

        button.addEventListener(
            'click',
            async () => {
                button.disabled = true;
                button.textContent =
                    'Creating secure order…';

                try {
                    const orderForm = new FormData();

                    orderForm.append(
                        'csrf',
                        <?= json_encode(
                            wz_csrf_token(),
                            JSON_UNESCAPED_SLASHES
                        ) ?>
                    );

                    const orderResponse = await fetch(
                        <?= json_encode(
                            wz_app_url(
                                'api/venue-plan-order.php'
                            ),
                            JSON_UNESCAPED_SLASHES
                        ) ?>,
                        {
                            method: 'POST',
                            body: orderForm,
                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                        }
                    );

                    const order =
                        await orderResponse.json();

                    if (
                        !orderResponse.ok
                        || !order.ok
                    ) {
                        throw new Error(
                            order.message
                            || 'Could not create payment order.'
                        );
                    }

                    const checkout =
                        new Razorpay({
                            key: order.key_id,
                            amount: order.amount,
                            currency: order.currency,
                            name: 'Wedding Za',
                            description:
                                'One Wedding Venue Assistance',
                            order_id: order.order_id,
                            prefill: {
                                name:
                                    order.customer?.name
                                    || '',
                                email:
                                    order.customer?.email
                                    || '',
                            },
                            theme: {
                                color: '#7b2942',
                            },
                            handler: async (
                                response
                            ) => {
                                showMessage(
                                    'Verifying payment…'
                                );

                                const verifyForm =
                                    new FormData();

                                verifyForm.append(
                                    'csrf',
                                    <?= json_encode(
                                        wz_csrf_token(),
                                        JSON_UNESCAPED_SLASHES
                                    ) ?>
                                );

                                verifyForm.append(
                                    'razorpay_order_id',
                                    response.razorpay_order_id
                                    || ''
                                );

                                verifyForm.append(
                                    'razorpay_payment_id',
                                    response.razorpay_payment_id
                                    || ''
                                );

                                verifyForm.append(
                                    'razorpay_signature',
                                    response.razorpay_signature
                                    || ''
                                );

                                const verifyResponse =
                                    await fetch(
                                        <?= json_encode(
                                            wz_app_url(
                                                'api/venue-plan-verify.php'
                                            ),
                                            JSON_UNESCAPED_SLASHES
                                        ) ?>,
                                        {
                                            method: 'POST',
                                            body: verifyForm,
                                            headers: {
                                                'X-Requested-With':
                                                    'XMLHttpRequest',
                                            },
                                        }
                                    );

                                const verified =
                                    await verifyResponse.json();

                                if (
                                    !verifyResponse.ok
                                    || !verified.ok
                                ) {
                                    throw new Error(
                                        verified.message
                                        || 'Payment verification failed.'
                                    );
                                }

                                showMessage(
                                    verified.message
                                    || 'Payment verified.',
                                    true
                                );

                                window.setTimeout(
                                    () => {
                                        window.location.reload();
                                    },
                                    900
                                );
                            },
                            modal: {
                                ondismiss: () => {
                                    button.disabled = false;
                                    button.textContent =
                                        'Pay ₹1,000';
                                },
                            },
                        });

                    checkout.on(
                        'payment.failed',
                        (response) => {
                            showMessage(
                                response.error?.description
                                || 'Payment failed. Please try again.'
                            );

                            button.disabled = false;
                            button.textContent =
                                'Pay ₹1,000';
                        }
                    );

                    checkout.open();
                } catch (error) {
                    showMessage(
                        error.message
                        || 'Could not start payment.'
                    );

                    button.disabled = false;
                    button.textContent =
                        'Pay ₹1,000';
                }
            }
        );
    })();
    </script>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
