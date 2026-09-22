<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';

$pageTitle = 'Event E-Invites';
$pageDescription = 'Create modern digital invitations for weddings, birthdays, engagements and private events.';
$pageKey = 'invites';

require __DIR__ . '/includes/header.php';
?>

<main>
    <?php
    wz_page_intro(
        'EVENT E-INVITES',
        'Your first impression<br><em>starts here.</em>',
        'Choose a design, customize the details and export a ready-to-share invitation file.'
    );
    ?>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">
                        THE COLLECTION
                    </span>

                    <h2>
                        Choose a mood.
                    </h2>
                </div>

                <p>
                    Floral, minimal, traditional and evening palettes
                    designed to feel elegant on a phone screen.
                </p>
            </div>

            <div class="invite-grid">
                <?php foreach (wz_data('invites') as $invite): ?>
                    <article class="invite-card reveal">
                        <div
                            class="invite-preview"
                            style="background: <?= h($invite['bg']) ?>; color: <?= h($invite['accent']) ?>;"
                        >
                            <span
                                class="eyebrow"
                                style="color: inherit;"
                            >
                                YOU’RE INVITED
                            </span>

                            <h3>
                                Aanya
                                <br>
                                & Veer
                            </h3>

                            <p>
                                12 December 2026 · Jaipur
                            </p>
                        </div>

                        <div class="invite-card-foot">
                            <div>
                                <h4>
                                    <?= h($invite['name']) ?>
                                </h4>

                                <span>
                                    <?= h($invite['style']) ?>
                                    ·
                                    <?= h($invite['price']) ?>
                                </span>
                            </div>

                            <a
                                class="text-link"
                                href="#builder"
                            >
                                Customize ↗
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section
        class="section paper-2"
        id="builder"
    >
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">
                        LIVE BUILDER
                    </span>

                    <h2>
                        Make it yours.
                    </h2>
                </div>

                <p>
                    The exported invitation opens as a standalone HTML file
                    and can be shared or printed to PDF from any modern browser.
                </p>
            </div>

            <div class="invite-builder">
                <div class="invite-controls">
                    <div class="form-stack">
                        <div class="field">
                            <label for="inviteNames">
                                Host / celebration name
                            </label>

                            <input
                                id="inviteNames"
                                value="Meera · Forty"
                            >
                        </div>

                        <div class="field">
                            <label for="inviteDate">
                                Event date
                            </label>

                            <input
                                id="inviteDate"
                                type="date"
                                value="2026-12-12"
                            >
                        </div>

                        <div class="field">
                            <label for="inviteVenue">
                                Venue / city
                            </label>

                            <input
                                id="inviteVenue"
                                value="Jaipur, Rajasthan"
                            >
                        </div>

                        <div class="field">
                            <label for="inviteTheme">
                                Design
                            </label>

                            <select id="inviteTheme">
                                <?php foreach (wz_data('invites') as $invite): ?>
                                    <option value="<?= h($invite['id']) ?>">
                                        <?= h($invite['name']) ?>
                                        ·
                                        <?= h($invite['style']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button
                            class="pill-btn wine wide"
                            id="downloadInvite"
                            type="button"
                        >
                            Export invitation ↗
                        </button>
                    </div>
                </div>

                <div class="live-invite-wrap">
                    <div
                        class="live-invite"
                        id="liveInvite"
                    >
                        <div>
                            <span
                                class="eyebrow"
                                style="color: inherit;"
                            >
                                YOU’RE INVITED
                            </span>

                            <h3 data-live-names>
                                Meera · Forty
                            </h3>

                            <p data-live-date>
                                12 December 2026
                            </p>

                            <p data-live-venue>
                                Jaipur, Rajasthan
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
