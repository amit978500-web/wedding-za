<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/media.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$pdo = wz_db();
$message = '';
$isSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $result = wz_media_upload(
            $_FILES['image'] ?? [],
            (string)($_POST['alt_text'] ?? '')
        );

        $message = (string)$result['message'];
        $isSuccess = (bool)$result['ok'];
    }
}

$media = [];

if ($pdo) {
    $media = $pdo
        ->query(
            'SELECT
                ma.*,
                u.name AS uploader_name
             FROM media_assets ma
             LEFT JOIN users u
                ON u.id = ma.uploaded_by
             ORDER BY ma.created_at DESC
             LIMIT 120'
        )
        ->fetchAll();
}

$adminPage = 'media';
$adminTitle = 'Media';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>ASSETS</small>
        <h1>Media library</h1>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Upload image</h2>
    </div>

    <form
        method="post"
        enctype="multipart/form-data"
        class="admin-form"
    >
        <input
            type="hidden"
            name="csrf"
            value="<?= h(wz_csrf_token()) ?>"
        >

        <div class="admin-form-grid">
            <div class="admin-field">
                <label for="mediaImage">
                    Image
                </label>

                <input
                    id="mediaImage"
                    type="file"
                    name="image"
                    accept="image/jpeg,image/png,image/webp"
                    required
                >
            </div>

            <div class="admin-field">
                <label for="mediaAlt">
                    Alt text
                </label>

                <input
                    id="mediaAlt"
                    name="alt_text"
                    maxlength="255"
                    placeholder="Describe the image"
                >
            </div>
        </div>

        <button
            class="admin-button"
            type="submit"
        >
            Upload media
        </button>
    </form>
</section>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Recent assets</h2>
    </div>

    <div class="admin-media-grid">
        <?php foreach ($media as $asset): ?>
            <article class="admin-media-card">
                <img
                    src="../<?= h((string)$asset['relative_path']) ?>"
                    alt="<?= h((string)$asset['alt_text']) ?>"
                >

                <div>
                    <strong>
                        <?= h((string)$asset['original_name']) ?>
                    </strong>

                    <small>
                        <?= h((string)$asset['width']) ?>
                        ×
                        <?= h((string)$asset['height']) ?>
                        ·
                        <?= h((string)$asset['uploader_name']) ?>
                    </small>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (!$media): ?>
            <p>No uploaded media yet.</p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
