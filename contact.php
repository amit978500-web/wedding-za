<?php
    require __DIR__.'/includes/bootstrap.php';
    require __DIR__.'/includes/components.php';
    $pageTitle='Contact Wedding Za';
    $pageDescription='Talk to Wedding Za about event discovery, vendor partnerships or platform questions.';
    $pageKey='contact';
    require __DIR__.'/includes/header.php';
?>
<main>
    <?php
        wz_page_intro('LET’S TALK','Tell us what<br><em>you’re planning.</em>','Event planning support, vendor partnerships or general questions — send us the useful context and we’ll have a clear starting point.');
    ?>
    <section class="section">
        <div class="container split-form-layout">
            <div class="split-form-copy reveal">
                <span class="eyebrow">
                WEDDING ZA
                </span>
                <h2>
                Good briefs make
                <br>
                better answers.
                </h2>
                <p>
                Include your city, approximate date and what you are trying to solve. This demo saves submissions locally so you can test the workflow before connecting email or a CRM.
                </p>
                <div style="margin-top:28px">
                    <p>
                    <strong>
                    Hosts & families
                    </strong>
                    <br>
                    <span class="muted">
                    Event discovery, planning support, platform help
                    </span>
                    </p>
                    <p>
                    <strong>
                    Businesses
                    </strong>
                    <br>
                    <span class="muted">
                    Listings, partnerships and profile support
                    </span>
                    </p>
                </div>
            </div>
            <form class="form-card form-stack reveal delay-1" data-async action="api/lead.php" method="post">
                <input type="hidden" name="type" value="contact">
                <input class="hp-field" name="company_website" tabindex="-1" autocomplete="off">
                <h3>
                Send a message
                </h3>
                <div class="form-grid">
                    <div class="field">
                        <label>
                            Name
                        </label>
                        <input name="name" required>
                    </div>
                    <div class="field">
                        <label>
                            Phone
                        </label>
                        <input name="phone" inputmode="tel">
                    </div>
                    <div class="field">
                        <label>
                            Email
                        </label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="field">
                        <label>
                            City
                        </label>
                        <input name="city">
                    </div>
                    <div class="field full">
                        <label>
                            What can we help with?
                        </label>
                        <select name="topic">
                            <option>
                            Event planning
                            </option>
                            <option>
                            Wedding planning
                            </option>
                            <option>
                            Birthday / private party
                            </option>
                            <option>
                            Corporate event
                            </option>
                            <option>
                            Vendor discovery
                            </option>
                            <option>
                            Business listing
                            </option>
                            <option>
                            Partnership
                            </option>
                            <option>
                            General question
                            </option>
                        </select>
                    </div>
                    <div class="field full">
                        <label>
                            Message
                        </label>
                        <textarea name="message" required>
                        </textarea>
                    </div>
                </div>
                <button class="pill-btn wine" type="submit">
                Send message ↗
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
