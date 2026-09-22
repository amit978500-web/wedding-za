<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/components.php';

$user = wz_user();

$defaultName = $user['name'] ?? '';
$defaultEmail = $user['email'] ?? '';

$pageTitle = 'List Your Event Business';
$pageDescription = 'Apply to list your event venue or vendor business on Wedding Za.';
$pageKey = 'register-vendor';

require __DIR__ . '/includes/header.php';
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
                    <em>qualified discovery.</em>
                </h1>

                <p>
                    Wedding Za is designed around event fit,
                    portfolio quality, useful pricing context
                    and enquiries with better briefs.
                </p>

                <?php if (wz_role() === 'vendor'): ?>
                    <a
                        class="text-link"
                        href="vendor-dashboard.php"
                    >
                        Open business workspace ↗
                    </a>
                <?php else: ?>
                    <a
                        class="text-link"
                        href="register.php?role=vendor"
                    >
                        Create a business account ↗
                    </a>
                <?php endif; ?>
            </div>

            <div class="business-apply-proof">
                <div>
                    <span>01</span>
                    <strong>Apply</strong>
                    <small>Business basics + portfolio</small>
                </div>

                <div>
                    <span>02</span>
                    <strong>Build</strong>
                    <small>Profile, event fit + pricing</small>
                </div>

                <div>
                    <span>03</span>
                    <strong>Respond</strong>
                    <small>Manage serious enquiries</small>
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
                    <em>where you fit.</em>
                </h2>

                <p>
                    The strongest profiles make three things clear quickly:
                    what you do, which events you are good at,
                    and what a client should know before they enquire.
                </p>

                <?php if (wz_role() === 'vendor' && wz_database_ready()): ?>
                    <p>
                        Because you are signed in as a business,
                        submitting this form updates your persistent
                        Wedding Za business profile.
                    </p>
                <?php endif; ?>
            </div>

            <form
                class="form-card form-stack"
                data-async
                action="api/lead.php"
                method="post"
            >
                <input
                    type="hidden"
                    name="type"
                    value="vendor-registration"
                >

                <input
                    class="hp-field"
                    name="company_website"
                    tabindex="-1"
                    autocomplete="off"
                >

                <h3>
                    Business application
                </h3>

                <div class="form-grid">
                    <div class="field">
                        <label for="businessName">
                            Business name
                        </label>

                        <input
                            id="businessName"
                            name="business"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="businessContact">
                            Contact person
                        </label>

                        <input
                            id="businessContact"
                            name="name"
                            value="<?= h((string)$defaultName) ?>"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="businessEmail">
                            Email
                        </label>

                        <input
                            id="businessEmail"
                            type="email"
                            name="email"
                            value="<?= h((string)$defaultEmail) ?>"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="businessPhone">
                            Phone
                        </label>

                        <input
                            id="businessPhone"
                            name="phone"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="businessCity">
                            City
                        </label>

                        <select
                            id="businessCity"
                            name="city"
                        >
                            <?php foreach (wz_data('cities') as $city): ?>
                                <option>
                                    <?= h($city) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="businessEvent">
                            Primary occasion
                        </label>

                        <select
                            id="businessEvent"
                            name="topic"
                        >
                            <option>
                                All celebrations
                            </option>

                            <?php foreach (wz_data('event_types') as $event): ?>
                                <option>
                                    <?= h($event['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="businessCategory">
                            Category
                        </label>

                        <select
                            id="businessCategory"
                            name="category"
                        >
                            <?php foreach (wz_data('categories') as $category): ?>
                                <option>
                                    <?= h($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="businessPrice">
                            Starting price
                        </label>

                        <input
                            id="businessPrice"
                            name="price"
                            placeholder="e.g. ₹75,000 onwards"
                        >
                    </div>

                    <div class="field full">
                        <label for="businessPortfolio">
                            Portfolio / Instagram / website
                        </label>

                        <input
                            id="businessPortfolio"
                            name="portfolio"
                            placeholder="https://"
                        >
                    </div>

                    <div class="field full">
                        <label for="businessEvents">
                            Which events do you work best on?
                        </label>

                        <textarea
                            id="businessEvents"
                            name="vendors"
                            placeholder="Wedding, corporate, engagement, birthday…"
                        ></textarea>
                    </div>

                    <div class="field full">
                        <label for="businessAbout">
                            Tell us about your work
                        </label>

                        <textarea
                            id="businessAbout"
                            name="message"
                        ></textarea>
                    </div>
                </div>

                <button
                    class="pill-btn wine"
                    type="submit"
                >
                    Submit business profile ↗
                </button>

                <div class="success-box"></div>
            </form>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
