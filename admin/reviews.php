<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/marketplace.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$adminPage = 'marketplace-reviews';
$adminTitle = 'Review Moderation';
$message = '';
$isSuccess = false;
$pdo = wz_db();

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        $status = (string)($_POST['status'] ?? 'pending');

        if (!in_array($status, ['pending', 'published', 'rejected'], true)) {
            $status = 'pending';
        }

        $statement = $pdo->prepare(
            'UPDATE marketplace_reviews
             SET status = :status
             WHERE id = :id'
        );

        $statement->execute([
            'status' => $status,
            'id' => $reviewId,
        ]);

        $review = $pdo->prepare(
            'SELECT reviewer_user_id
             FROM marketplace_reviews
             WHERE id = :id'
        );

        $review->execute([
            'id' => $reviewId,
        ]);

        $reviewerId = (int)$review->fetchColumn();

        if ($reviewerId > 0) {
            wz_marketplace_notify(
                $reviewerId,
                'review',
                'Review moderation updated',
                'Your review is now '.$status.'.',
                null
            );
        }

        wz_audit(
            'marketplace.review.moderated',
            'marketplace_review',
            $reviewId,
            ['status' => $status]
        );

        $message = 'Review status updated.';
        $isSuccess = true;
    }
}

$rows = [];

if ($pdo) {
    $rows = $pdo->query(
        'SELECT
            mr.*,
            reviewer.name AS reviewer_name,
            reviewer.email AS reviewer_email,
            business.name AS business_account_name
         FROM marketplace_reviews mr
         INNER JOIN users reviewer
            ON reviewer.id = mr.reviewer_user_id
         INNER JOIN users business
            ON business.id = mr.business_user_id
         ORDER BY
            CASE mr.status
                WHEN "pending" THEN 0
                WHEN "published" THEN 1
                ELSE 2
            END,
            mr.created_at DESC'
    )->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>MARKETPLACE TRUST</small>
        <h1>Review moderation</h1>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-panel-head">
        <div>
            <h2>Customer reviews</h2>
            <p class="admin-section-note">
                Publish useful authentic reviews; reject spam or policy violations.
            </p>
        </div>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Reviewer</th>
                    <th>Business</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Verified</th>
                    <th>Status</th>
                    <th>Moderate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <strong><?= h((string)$row['reviewer_name']) ?></strong>
                            <br>
                            <small><?= h((string)$row['reviewer_email']) ?></small>
                        </td>
                        <td>
                            <?= h((string)$row['business_account_name']) ?>
                            <br>
                            <small><?= h(ucfirst((string)$row['business_type'])) ?></small>
                        </td>
                        <td><?= h((string)$row['rating']) ?> ★</td>
                        <td>
                            <?php if (!empty($row['title'])): ?>
                                <strong><?= h((string)$row['title']) ?></strong><br>
                            <?php endif; ?>
                            <?= h((string)$row['body']) ?>
                        </td>
                        <td><?= !empty($row['is_verified_booking'])?'Yes':'No' ?></td>
                        <td>
                            <span class="admin-badge <?= h((string)$row['status']) ?>">
                                <?= h(ucfirst((string)$row['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" class="admin-inline-form">
                                <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">
                                <input type="hidden" name="review_id" value="<?= h((string)$row['id']) ?>">
                                <select name="status">
                                    <?php foreach (['pending','published','rejected'] as $status): ?>
                                        <option
                                            value="<?= h($status) ?>"
                                            <?= $status===$row['status']?'selected':'' ?>
                                        >
                                            <?= h(ucfirst($status)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="admin-button" type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr><td colspan="7">No reviews submitted yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
