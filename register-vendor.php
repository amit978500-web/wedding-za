<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $pageTitle='List Your Event Business';
    $pageDescription='Apply to list your event venue or vendor business on Wedding Za.';
    $pageKey='register-vendor';
    require __DIR__.'/includes/header.php';
?>
<main>
    <section class="business-apply-hero">
        <div class="container business-apply-grid">
            <div>
                <span class="eyebrow">
                FOR EVENT BUSINESSES
                </span>
                <h1>
                Turn good work into
                <br>
                <em>
                qualified discovery.
                </em>
                </h1>
                <p>
                Wedding Za is designed around event fit, portfolio quality, useful pricing context and enquiries with better briefs.
                </p>
                <a class="text-link" href="login.php?role=vendor">
                Already listed? Sign in ↗
                </a>
            </div>
            <div class="business-apply-proof">
                <div>
                    <span>
                    01
                    </span>
                    <strong>
                    Apply
                    </strong>
                    <small>
                    Business basics + portfolio
                    </small>
                </div>
                <div>
                    <span>
                    02
                    </span>
                    <strong>
                    Build
                    </strong>
                    <small>
                    Profile, event fit + pricing
                    </small>
                </div>
                <div>
                    <span>
                    03
                    </span>
                    <strong>
                    Respond
                    </strong>
                    <small>
                    Manage serious enquiries
                    </small>
                </div>
            </div>
        </div>
    </section>
    <section class="section paper-2">
        <div class="container split-form-layout">
            <div class="split-form-copy">
                <span class="eyebrow">
                BUSINESS ONBOARDING
                </span>
                <h2>
                Show clients
                <br>
                <em>
                where you fit.
                </em>
                </h2>
                <p>
                The strongest profiles make three things clear quickly: what you do, which events you are good at, and what a client should know before they enquire.
                </p>
            </div>
            <form class="form-card form-stack" data-async action="api/lead.php" method="post">
                <input type="hidden" name="type" value="vendor-registration">
                <input class="hp-field" name="company_website" tabindex="-1" autocomplete="off">
                <h3>
                Business application
                </h3>
                <div class="form-grid">
                    <div class="field">
                        <label>
                            Business name
                        </label>
                        <input name="business" required>
                    </div>
                    <div class="field">
                        <label>
                            Contact person
                        </label>
                        <input name="name" required>
                    </div>
                    <div class="field">
                        <label>
                            Email
                        </label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="field">
                        <label>
                            Phone
                        </label>
                        <input name="phone" required>
                    </div>
                    <div class="field">
                        <label>
                            City
                        </label>
                        <select name="city">
                            <?php
                                foreach(wz_data('cities') as $city):
                            ?>
                                <option>
                                <?= h($city) ?>
                                </option>
                            <?php
                                endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>
                            Primary occasion
                        </label>
                        <select name="topic">
                            <option>
                            All celebrations
                            </option>
                            <?php
                                foreach(wz_data('event_types') as $event):
                            ?>
                                <option>
                                <?= h($event['name']) ?>
                                </option>
                            <?php
                                endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>
                            Category
                        </label>
                        <select name="category">
                            <?php
                                foreach(wz_data('categories') as $c):
                            ?>
                                <option>
                                <?= h($c['name']) ?>
                                </option>
                            <?php
                                endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>
                            Starting price
                        </label>
                        <input name="price" placeholder="e.g. ₹75,000 onwards">
                    </div>
                    <div class="field full">
                        <label>
                            Portfolio / Instagram / website
                        </label>
                        <input name="portfolio" placeholder="https://">
                    </div>
                    <div class="field full">
                        <label>
                            Which events do you work best on?
                        </label>
                        <textarea name="vendors" placeholder="Wedding, corporate, engagement, birthday…">
                        </textarea>
                    </div>
                    <div class="field full">
                        <label>
                            Tell us about your work
                        </label>
                        <textarea name="message">
                        </textarea>
                    </div>
                </div>
                <button class="pill-btn wine" type="submit">
                Submit application ↗
                </button>
                <div class="success-box">
                </div>
            </form>
        </div>
    </section>
</main>
<?php
    require __DIR__.'/includes/footer.php';
?>
