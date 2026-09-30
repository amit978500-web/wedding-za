<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';

$pageTitle = 'Submit Your Celebration';
$pageDescription = 'Submit your celebration story to Wedding Za for editorial review and publication.';
$pageKey = 'submit-wedding';
$message = '';
$isSuccess = false;
$pdo = wz_db();

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $coupleNames = trim((string)($_POST['couple_names'] ?? ''));
        $submitterName = trim((string)($_POST['submitter_name'] ?? ''));
        $submitterEmail = trim((string)($_POST['submitter_email'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $eventDate = trim((string)($_POST['event_date'] ?? ''));
        $coverImage = trim((string)($_POST['cover_image_url'] ?? ''));
        $story = trim((string)($_POST['story'] ?? ''));
        $galleryText = trim((string)($_POST['gallery_urls'] ?? ''));
        $vendorsText = trim((string)($_POST['vendors'] ?? ''));

        if (
            $coupleNames === ''
            || !filter_var($submitterEmail, FILTER_VALIDATE_EMAIL)
            || $story === ''
        ) {
            $message = 'Add the couple / celebration name, a valid email and the story.';
        } else {
            $splitValues = static function (string $value): array {
                if ($value === '') {
                    return [];
                }

                return array_values(
                    array_filter(
                        array_map(
                            'trim',
                            preg_split(
                                '/[\r\n,]+/',
                                $value
                            ) ?: []
                        )
                    )
                );
            };

            $statement = $pdo->prepare(
                'INSERT INTO wedding_submissions (
                    submitter_user_id,
                    submitter_name,
                    submitter_email,
                    couple_names,
                    city,
                    event_date,
                    story,
                    cover_image_url,
                    gallery_json,
                    vendors_json,
                    status
                ) VALUES (
                    :submitter_user_id,
                    :submitter_name,
                    :submitter_email,
                    :couple_names,
                    :city,
                    :event_date,
                    :story,
                    :cover_image_url,
                    :gallery_json,
                    :vendors_json,
                    "submitted"
                )'
            );

            $statement->execute([
                'submitter_user_id' => wz_is_logged_in()
                    ? (int)(wz_user()['id'] ?? 0) ?: null
                    : null,
                'submitter_name' => $submitterName !== '' ? $submitterName : null,
                'submitter_email' => $submitterEmail,
                'couple_names' => $coupleNames,
                'city' => $city !== '' ? $city : null,
                'event_date' => $eventDate !== '' ? $eventDate : null,
                'story' => $story,
                'cover_image_url' => $coverImage !== '' ? $coverImage : null,
                'gallery_json' => json_encode($splitValues($galleryText)),
                'vendors_json' => json_encode($splitValues($vendorsText)),
            ]);

            $message = 'Your celebration has been submitted for Wedding Za editorial review.';
            $isSuccess = true;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<main>
    <?php
    wz_page_intro(
        'REAL CELEBRATIONS',
        'Your celebration could<br><em>inspire the next one.</em>',
        'Share the story, city, photography and vendor credits. Wedding Za reviews every submission before it appears publicly.'
    );
    ?>

    <section class="section">
        <div class="container split-form-layout">
            <div class="split-form-copy">
                <span class="eyebrow">SUBMISSION NOTES</span>
                <h2>
                    Give credit where
                    <br>
                    the magic happened.
                </h2>
                <p>
                    Include original photography links and the venue, photographer, planner, decor, makeup and other important teams. Published stories keep those credits visible.
                </p>

                <?php if ($message !== ''): ?>
                    <div class="marketplace-review-notice <?= $isSuccess ? 'success' : '' ?>">
                        <?= h($message) ?>
                    </div>
                <?php endif; ?>
            </div>

            <form class="form-card form-stack" method="post">
                <input
                    type="hidden"
                    name="csrf"
                    value="<?= h(wz_csrf_token()) ?>"
                >

                <h3>Tell us about the celebration</h3>

                <div class="form-grid">
                    <div class="field">
                        <label>Your name</label>
                        <input name="submitter_name">
                    </div>

                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="submitter_email" required>
                    </div>

                    <div class="field full">
                        <label>Couple / celebration name</label>
                        <input name="couple_names" required placeholder="Aanya & Veer">
                    </div>

                    <div class="field">
                        <label>Event city</label>
                        <input name="city">
                    </div>

                    <div class="field">
                        <label>Event date</label>
                        <input type="date" name="event_date">
                    </div>

                    <div class="field full">
                        <label>Cover image URL</label>
                        <input name="cover_image_url" type="url" placeholder="Original photograph URL">
                    </div>

                    <div class="field full">
                        <label>Gallery image URLs</label>
                        <textarea
                            name="gallery_urls"
                            placeholder="One image URL per line"
                        ></textarea>
                    </div>

                    <div class="field full">
                        <label>Key vendors</label>
                        <textarea
                            name="vendors"
                            placeholder="Venue, photographer, makeup, planner, decor… one per line"
                        ></textarea>
                    </div>

                    <div class="field full">
                        <label>Your story</label>
                        <textarea name="story" required></textarea>
                    </div>
                </div>

                <button class="pill-btn wine" type="submit">
                    Submit celebration ↗
                </button>
            </form>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
