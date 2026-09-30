<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components.php';

$id = (int)($_GET['id'] ?? 0);
$pdo = wz_db();
$story = null;

if ($pdo && $id > 0) {
    $statement = $pdo->prepare(
        'SELECT *
         FROM wedding_submissions
         WHERE id = :id
         AND status = "published"
         LIMIT 1'
    );

    $statement->execute([
        'id' => $id,
    ]);

    $story = $statement->fetch() ?: null;
}

if (!$story) {
    http_response_code(404);
    header('Location: real-weddings.php');
    exit;
}

$pageTitle = (string)$story['couple_names'];
$pageDescription = mb_strimwidth(
    (string)$story['story'],
    0,
    160,
    '…'
);
$pageKey = 'real-weddings';
$pageImage = (string)($story['cover_image_url'] ?? '');

$gallery = json_decode(
    (string)($story['gallery_json'] ?? '[]'),
    true
) ?: [];

$vendors = json_decode(
    (string)($story['vendors_json'] ?? '[]'),
    true
) ?: [];

require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="vendor-profile-hero">
        <div class="container">
            <?php
            wz_breadcrumbs([
                ['Real Celebrations', 'real-weddings.php'],
                [(string)$story['couple_names'], null],
            ]);
            ?>

            <div class="vendor-profile-top">
                <div class="vendor-profile-title">
                    <span class="eyebrow">
                        REAL CELEBRATION
                        <?php if (!empty($story['city'])): ?>
                            · <?= h((string)$story['city']) ?>
                        <?php endif; ?>
                    </span>

                    <h1><?= h((string)$story['couple_names']) ?></h1>

                    <?php if (!empty($story['event_date'])): ?>
                        <p><?= h(date('d F Y',strtotime((string)$story['event_date']))) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($story['cover_image_url'])): ?>
                <figure class="vendor-profile-gallery-main" style="margin-top:30px;">
                    <img
                        src="<?= h((string)$story['cover_image_url']) ?>"
                        alt="<?= h((string)$story['couple_names']) ?>"
                    >
                </figure>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="container vendor-profile-layout">
            <article class="profile-block">
                <div class="profile-block-head">
                    <span class="eyebrow">THE STORY</span>
                    <h2>How it came together.</h2>
                </div>

                <p class="profile-lead">
                    <?= nl2br(h((string)$story['story'])) ?>
                </p>

                <?php if ($gallery): ?>
                    <div class="story-gallery vendor-story-gallery">
                        <?php foreach ($gallery as $image): ?>
                            <figure>
                                <img
                                    src="<?= h((string)$image) ?>"
                                    alt="<?= h((string)$story['couple_names']) ?>"
                                    loading="lazy"
                                >
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <aside class="enquiry-card enquiry-card-v2">
                <div class="enquiry-card-head">
                    <small>WEDDING CREDITS</small>
                    <h3><?= h((string)$story['couple_names']) ?></h3>
                </div>

                <div class="marketplace-chip-list">
                    <?php foreach ($vendors as $vendor): ?>
                        <span><?= h((string)$vendor) ?></span>
                    <?php endforeach; ?>

                    <?php if (!$vendors): ?>
                        <span>Credits not supplied</span>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
