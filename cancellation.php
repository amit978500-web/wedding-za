<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $pageTitle='Cancellation & Refunds';
    $pageDescription='Wedding Za cancellation and refunds information.';
    $pageKey='cancellation';
    require __DIR__.'/includes/header.php';
?>
<main>
    <?php
        wz_page_intro('LEGAL','Cancellation & refunds','A placeholder policy framework for future paid Wedding Za services.');
    ?>
    <section class="section-sm">
        <div class="container legal-copy">
            <h2>
            Marketplace bookings
            </h2>
            <p>
            Vendor cancellations and refunds are governed by the contract agreed between the couple and the vendor unless Wedding Za is the merchant of record for that transaction.
            </p>
            <h2>
            Wedding Za services
            </h2>
            <p>
            If paid concierge, invitations, subscriptions or promotional services are introduced, publish product-specific cancellation windows, refund eligibility and non-refundable work already completed.
            </p>
            <h2>
            Payments
            </h2>
            <p>
            No live payment gateway is connected in this demo package. Do not publish a production refund promise until the payment flow and merchant terms are finalized.
            </p>
        </div>
    </section>
</main>
<?php
    require __DIR__.'/includes/footer.php';
?>
