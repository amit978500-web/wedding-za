<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';
require_once dirname(__DIR__, 2) . '/includes/marketplace.php';

$crmRole = $crmRole ?? 'vendor';
$crmPage = 'reviews';
$crmTitle = 'Customer Reviews';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)$crmUser['id'];
$pdo = wz_db();
$message = '';
$isSuccess = false;

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        $reply = trim((string)($_POST['business_reply'] ?? ''));

        $statement = $pdo->prepare(
            'UPDATE marketplace_reviews
             SET business_reply = :business_reply,
                 replied_at = CASE
                    WHEN :business_reply_empty = 1 THEN NULL
                    ELSE NOW()
                 END
             WHERE id = :id
             AND business_user_id = :business_user_id
             AND business_type = :business_type'
        );

        $statement->execute([
            'business_reply' => $reply !== '' ? $reply : null,
            'business_reply_empty' => $reply === '' ? 1 : 0,
            'id' => $reviewId,
            'business_user_id' => $userId,
            'business_type' => $crmRole,
        ]);

        $reviewer = $pdo->prepare(
            'SELECT reviewer_user_id
             FROM marketplace_reviews
             WHERE id = :id
             AND business_user_id = :business_user_id'
        );

        $reviewer->execute([
            'id' => $reviewId,
            'business_user_id' => $userId,
        ]);

        $reviewerId = (int)$reviewer->fetchColumn();

        if ($reviewerId > 0 && $reply !== '') {
            wz_marketplace_notify(
                $reviewerId,
                'review',
                'A business replied to your review',
                'Open the business profile to see the reply.',
                null
            );
        }

        $message = 'Review reply saved.';
        $isSuccess = true;
    }
}

$rows = [];

if ($pdo) {
    $statement = $pdo->prepare(
        'SELECT mr.*, u.name AS reviewer_name
         FROM marketplace_reviews mr
         INNER JOIN users u
            ON u.id = mr.reviewer_user_id
         WHERE mr.business_user_id = :business_user_id
         AND mr.business_type = :business_type
         ORDER BY mr.created_at DESC'
    );

    $statement->execute([
        'business_user_id' => $userId,
        'business_type' => $crmRole,
    ]);

    $rows = $statement->fetchAll();
}

$summary = wz_marketplace_review_summary(
    $userId,
    $crmRole
);

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">REPUTATION</span>
        <h1>Reviews</h1>
        <p>
            Published reviews build customer trust. Reply professionally and use repeated feedback to improve the profile and service.
        </p>
    </div>
</div>

<div class="crm-grid">
    <article class="crm-stat">
        <span>Published rating</span>
        <strong><?= h((string)$summary['rating']) ?> ★</strong>
    </article>

    <article class="crm-stat">
        <span>Published reviews</span>
        <strong><?= h((string)$summary['count']) ?></strong>
    </article>

    <article class="crm-stat">
        <span>Total submissions</span>
        <strong><?= h((string)count($rows)) ?></strong>
    </article>

    <article class="crm-stat">
        <span>Replies</span>
        <strong>
            <?= h((string)count(array_filter(
                $rows,
                fn(array $row): bool => !empty($row['business_reply'])
            ))) ?>
        </strong>
    </article>
</div>

<section class="crm-panel">
    <div class="crm-list">
        <?php foreach ($rows as $review): ?>
            <article class="crm-list-item">
                <strong>
                    <?= h((string)$review['reviewer_name']) ?>
                    · <?= h((string)$review['rating']) ?> ★
                </strong>

                <p><?= h((string)$review['body']) ?></p>

                <small>
                    <?= !empty($review['is_verified_booking'])?'✓ Verified booking · ':'' ?>
                    <?= h(ucfirst((string)$review['status'])) ?>
                    · <?= h((string)$review['created_at']) ?>
                </small>

                <form method="post" class="crm-form" style="padding:16px 0 0;">
                    <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                    <input type="hidden" name="review_id" value="<?= h((string)$review['id']) ?>">

                    <div class="crm-field">
                        <label>Business reply</label>
                        <textarea
                            name="business_reply"
                            placeholder="Thank the customer or clarify useful context."
                        ><?= h((string)($review['business_reply'] ?? '')) ?></textarea>
                    </div>

                    <button class="crm-button small" type="submit">
                        Save reply
                    </button>
                </form>
            </article>
        <?php endforeach; ?>

        <?php if (!$rows): ?>
            <div class="crm-empty">No reviews yet.</div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
