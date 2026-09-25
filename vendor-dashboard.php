<?php

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';

wz_require_role('vendor');

$user = wz_user();
$pdo = wz_db();

$profile = null;
$enquiries = [];
$gallery = [];
$newEnquiryCount = 0;
$totalEnquiryCount = 0;
$profileCompleteness = 0;
$publicVendorId = null;

if ($pdo && !empty($user['id'])) {
    $profileStatement = $pdo->prepare(
        'SELECT *
         FROM vendor_profiles
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $profileStatement->execute([
        'user_id' => (int)$user['id'],
    ]);

    $profile = $profileStatement->fetch() ?: null;

    if ($profile) {
        $gallery = json_decode(
            (string)($profile['gallery_json'] ?? '[]'),
            true
        );

        if (!is_array($gallery)) {
            $gallery = [];
        }
        $businessName = (string)$profile['business_name'];

        $enquiryStatement = $pdo->prepare(
            'SELECT *
             FROM leads
             WHERE type = :type
             AND vendor = :vendor
             ORDER BY created_at DESC
             LIMIT 20'
        );

        $enquiryStatement->execute([
            'type' => 'vendor-enquiry',
            'vendor' => $businessName,
        ]);

        $enquiries = $enquiryStatement->fetchAll();

        $totalEnquiryCount = count($enquiries);

        $newEnquiryCount = count(
            array_filter(
                $enquiries,
                fn (array $item): bool => ($item['status'] ?? '') === 'new'
            )
        );

        $profileFields = [
            $profile['business_name'] ?? '',
            $profile['category'] ?? '',
            $profile['city'] ?? '',
            $profile['events_json'] ?? '',
            $profile['starting_price'] ?? '',
            $profile['portfolio_url'] ?? '',
            $profile['about'] ?? '',
        ];

        $completedFields = count(
            array_filter(
                $profileFields,
                fn ($value): bool => trim((string)$value) !== ''
            )
        );

        $profileCompleteness = (int)round(
            ($completedFields / count($profileFields)) * 100
        );

        foreach (wz_data('vendors') as $vendor) {
            if (
                strcasecmp(
                    (string)($vendor['name'] ?? ''),
                    $businessName
                ) === 0
            ) {
                $publicVendorId = (string)$vendor['id'];
                break;
            }
        }
    }
}

$publicProfileUrl = $publicVendorId
    ? 'vendor.php?id=' . urlencode($publicVendorId)
    : 'vendors.php';

$pageTitle = 'Vendor Business Workspace';
$pageDescription = 'Manage your Wedding Za business profile and enquiries.';
$pageKey = 'vendor-dashboard';

require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="business-dashboard-v2">
        <aside class="business-sidebar">
            <a
                class="business-sidebar-brand"
                href="index.php"
            >
                Wedding Za
                <br>
                <em>Business</em>
            </a>

            <nav>
                <a
                    class="active"
                    href="#overview"
                >
                    Overview
                </a>

                <a href="#enquiries">
                    Enquiries
                </a>

                <a href="#profile">
                    Profile quality
                </a>

                <a href="<?= h($publicProfileUrl) ?>">
                    Public profile
                </a>

                <a href="register-vendor.php">
                    Edit business details
                </a>
            </nav>

            <div class="business-sidebar-user">
                <span>
                    <?= h((string)$user['name']) ?>
                </span>

                <small>
                    <?= h((string)$user['email']) ?>
                </small>

                <a href="logout.php">
                    Log out ↗
                </a>
            </div>
        </aside>

        <div class="business-dashboard-main">
            <header
                class="business-dashboard-head"
                id="overview"
            >
                <div>
                    <span class="eyebrow">
                        BUSINESS WORKSPACE
                    </span>

                    <h1>
                        Good morning,
                        <br>
                        <em>
                            <?= h(explode(' ', (string)$user['name'])[0]) ?>.
                        </em>
                    </h1>
                </div>

                <a
                    class="pill-btn wine"
                    href="<?= h($publicProfileUrl) ?>"
                >
                    View public profile ↗
                </a>
            </header>

            <?php if (!$pdo): ?>
                <div class="auth-error">
                    MySQL is not configured on this installation.
                    The business workspace is running in local session mode.
                </div>
            <?php endif; ?>

            <?php if ($pdo && !$profile): ?>
                <div class="auth-error">
                    Your business profile has not been submitted yet.
                    Complete the business application to activate this workspace.
                </div>
            <?php endif; ?>

            <div class="business-kpis">
                <div>
                    <span>Total enquiries</span>
                    <strong><?= h((string)$totalEnquiryCount) ?></strong>
                    <small>Latest 20 shown below</small>
                </div>

                <div>
                    <span>New enquiries</span>
                    <strong><?= h((string)$newEnquiryCount) ?></strong>
                    <small>Waiting for a response</small>
                </div>

                <div>
                    <span>Profile quality</span>
                    <strong><?= h((string)$profileCompleteness) ?>%</strong>
                    <small>Based on core profile fields</small>
                </div>

                <div>
                    <span>Listing status</span>
                    <strong>
                        <?= h(
                            ucfirst(
                                (string)($profile['approval_status'] ?? 'setup')
                            )
                        ) ?>
                    </strong>
                    <small>Wedding Za review status</small>
                </div>
            </div>

            <section
                class="business-panel"
                id="enquiries"
            >
                <div class="business-panel-head">
                    <div>
                        <span class="eyebrow">
                            ENQUIRIES
                        </span>

                        <h2>
                            Know the brief
                            <br>
                            <em>before you reply.</em>
                        </h2>
                    </div>
                </div>

                <?php if ($enquiries): ?>
                    <div class="business-enquiry-table">
                        <div class="business-enquiry-row head">
                            <span>Client</span>
                            <span>Event</span>
                            <span>City</span>
                            <span>Date</span>
                            <span>Status</span>
                        </div>

                        <?php foreach ($enquiries as $enquiry): ?>
                            <div class="business-enquiry-row">
                                <strong>
                                    <?= h((string)$enquiry['name']) ?>
                                </strong>

                                <span>
                                    <?= h((string)$enquiry['topic']) ?>
                                </span>

                                <span>
                                    <?= h((string)$enquiry['city']) ?>
                                </span>

                                <span>
                                    <?= h((string)$enquiry['event_date']) ?>
                                </span>

                                <b
                                    class="<?= ($enquiry['status'] ?? '') === 'new' ? 'new' : '' ?>"
                                >
                                    <?= h(
                                        ucfirst(
                                            (string)$enquiry['status']
                                        )
                                    ) ?>
                                </b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No enquiries yet.</h3>

                        <p class="muted">
                            When a host submits an enquiry to your approved
                            profile, it will appear here.
                        </p>
                    </div>
                <?php endif; ?>
            </section>

            <section
                class="business-panel"
                id="profile"
            >
                <div class="business-panel-head">
                    <div>
                        <span class="eyebrow">
                            PROFILE QUALITY
                        </span>

                        <h2>
                            Better context,
                            <br>
                            <em>better leads.</em>
                        </h2>
                    </div>

                    <strong class="profile-score">
                        <?= h((string)$profileCompleteness) ?>%
                    </strong>
                </div>

                <div class="profile-quality-grid">
                    <div class="<?= !empty($profile['business_name']) ? 'done' : '' ?>">
                        <span>01</span>
                        <strong>Business basics</strong>
                        <small>Name, city and category</small>
                    </div>

                    <div class="<?= !empty($profile['portfolio_url']) ? 'done' : '' ?>">
                        <span>02</span>
                        <strong>Portfolio</strong>
                        <small>Website, Instagram or gallery</small>
                    </div>

                    <div class="<?= !empty($profile['events_json']) ? 'done' : '' ?>">
                        <span>03</span>
                        <strong>Event fit</strong>
                        <small>Occasions you work best on</small>
                    </div>

                    <div class="<?= !empty($profile['about']) ? 'done' : '' ?>">
                        <span>04</span>
                        <strong>Business story</strong>
                        <small>What clients should know</small>
                    </div>
                </div>
            </section>

            <section class="business-panel">
                <div class="business-panel-head">
                    <div>
                        <span class="eyebrow">
                            PORTFOLIO MEDIA
                        </span>

                        <h2>
                            Add real work,
                            <br>
                            <em>not stock imagery.</em>
                        </h2>
                    </div>
                </div>

                <?php if ($profile): ?>
                    <form
                        class="form-stack"
                        data-async
                        action="api/media-upload.php"
                        method="post"
                        enctype="multipart/form-data"
                    >
                        <input
                            type="hidden"
                            name="csrf"
                            value="<?= h(wz_csrf_token()) ?>"
                        >

                        <div class="field">
                            <label for="vendorGalleryImage">
                                Portfolio image
                            </label>

                            <input
                                id="vendorGalleryImage"
                                type="file"
                                name="image"
                                accept="image/jpeg,image/png,image/webp"
                                required
                            >
                        </div>

                        <div class="field">
                            <label for="vendorGalleryAlt">
                                Alt text
                            </label>

                            <input
                                id="vendorGalleryAlt"
                                name="alt_text"
                                maxlength="255"
                                placeholder="Describe this event image"
                            >
                        </div>

                        <button
                            class="pill-btn wine"
                            type="submit"
                        >
                            Upload portfolio image ↗
                        </button>

                        <div class="success-box"></div>
                    </form>

                    <?php if ($gallery): ?>
                        <div class="story-gallery vendor-story-gallery">
                            <?php foreach ($gallery as $imagePath): ?>
                                <figure>
                                    <img
                                        src="<?= h((string)$imagePath) ?>"
                                        alt="<?= h((string)$profile['business_name']) ?> portfolio"
                                        loading="lazy"
                                    >
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="business-tech-note">
                        Submit your business profile before uploading portfolio media.
                    </p>
                <?php endif; ?>
            </section>

            <section class="business-panel">
                <div class="business-panel-head">
                    <div>
                        <span class="eyebrow">
                            BUSINESS DETAILS
                        </span>

                        <h2>
                            Keep the profile
                            <br>
                            <em>current.</em>
                        </h2>
                    </div>
                </div>

                <p class="business-tech-note">
                    Update your business application whenever pricing,
                    portfolio, city or event fit changes.
                </p>

                <a
                    class="pill-btn wine"
                    href="register-vendor.php"
                >
                    Edit business details ↗
                </a>
            </section>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
