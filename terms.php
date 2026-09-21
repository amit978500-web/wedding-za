<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $pageTitle='Terms of Use';
    $pageDescription='Wedding Za terms of use demo.';
    $pageKey='terms';
    require __DIR__.'/includes/header.php';
?>
<main>
    <?php
        wz_page_intro('LEGAL','Terms of use','A starter structure for platform terms. Have your final terms reviewed for your operating entity and jurisdiction before launch.');
    ?>
    <section class="section-sm">
        <div class="container legal-copy">
            <h2>
            1. Marketplace information
            </h2>
            <p>
            Wedding Za presents vendor information for discovery and comparison. Final availability, pricing, scope and contractual terms are agreed directly between couples and vendors unless Wedding Za explicitly offers a separate managed service.
            </p>
            <h2>
            2. Accuracy
            </h2>
            <p>
            Vendors are responsible for keeping business information, portfolio rights and pricing context accurate. Couples should verify material details before making payments or signing contracts.
            </p>
            <h2>
            3. Intellectual property
            </h2>
            <p>
            Vendor portfolios, real-wedding photography and editorial content must only be published with the appropriate rights and credits.
            </p>
            <h2>
            4. Accounts and acceptable use
            </h2>
            <p>
            Production accounts should prohibit impersonation, fraudulent listings, abusive behaviour, scraping and attempts to compromise the platform.
            </p>
            <h2>
            5. Final legal review
            </h2>
            <p>
            This page is product copy for the demo package and is not a substitute for legal advice.
            </p>
        </div>
    </section>
</main>
<?php
    require __DIR__.'/includes/footer.php';
?>
