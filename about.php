<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $pageTitle='About Wedding Za';
    $pageDescription='A design-led celebration discovery and planning platform built to make every kind of event easier to shape.';
    $pageKey='about';
    require __DIR__.'/includes/header.php';
?>
<main>
    <?php
        wz_page_intro('ABOUT WEDDING ZA','A better way to<br><em>choose beautifully.</em>','Wedding Za is a celebration marketplace built around one idea: every event gets better when discovery feels curated and practical tools live next to the inspiration.','https://images.unsplash.com/photo-1781077127473-343904bb5856?auto=format&fit=crop&w=1900&q=92');
    ?>
    <section class="section">
        <div class="container editorial-intro">
            <div class="editorial-intro-copy">
                <span class="eyebrow">
                WHY WE BUILT IT
                </span>
                <h2>
                Not another endless
                <em>
                directory.
                </em>
                </h2>
                <p>
                Most hosts do not need more profiles. They need the right questions, a manageable shortlist and enough visual confidence to recognise what fits their occasion.
                </p>
                <p>
                That is why Wedding Za combines discovery, inspiration, real celebrations and planning tools in one consistent experience.
                </p>
            </div>
            <div class="editorial-intro-grid">
                <figure class="a">
                    <img src="https://images.unsplash.com/photo-1769500810743-5e5dd4fd5848?auto=format&fit=crop&w=1200&q=92" alt="Indian celebration">
                </figure>
                <figure class="b">
                    <img src="https://images.unsplash.com/photo-1770665567877-72ee8a7c9051?auto=format&fit=crop&w=1200&q=92" alt="Indian event venue">
                </figure>
                <figure class="c">
                    <img src="https://images.unsplash.com/photo-1781077126479-437220427c93?auto=format&fit=crop&w=1200&q=92" alt="Indian celebration detail">
                </figure>
                <div class="editorial-stamp">
                    WZ
                    <small>
                    THE IDEA
                    </small>
                </div>
            </div>
        </div>
    </section>
    <div class="container stats-premium">
        <div>
            <strong>
            01
            </strong>
            <span>
            discover with taste
            </span>
        </div>
        <div>
            <strong>
            02
            </strong>
            <span>
            shortlist with intent
            </span>
        </div>
        <div>
            <strong>
            03
            </strong>
            <span>
            plan in one place
            </span>
        </div>
        <div>
            <strong>
            04
            </strong>
            <span>
            celebrate your way
            </span>
        </div>
    </div>
    <section class="section paper-2">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">
                    THE PRINCIPLES
                    </span>
                    <h2>
                    How Wedding Za
                    <br>
                    should feel.
                    </h2>
                </div>
            </div>
            <div class="facts-grid">
                <div class="fact-card">
                    <span>
                    Discovery
                    </span>
                    <strong>
                    Edited, not overwhelming.
                    </strong>
                </div>
                <div class="fact-card">
                    <span>
                    Design
                    </span>
                    <strong>
                    Premium without becoming precious.
                    </strong>
                </div>
                <div class="fact-card">
                    <span>
                    Planning
                    </span>
                    <strong>
                    Practical enough to be useful.
                    </strong>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
    require __DIR__.'/includes/footer.php';
?>
