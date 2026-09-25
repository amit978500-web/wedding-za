<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$pdo = wz_db();
$message = '';
$isSuccess = false;

$editing = [
    'id' => 0,
    'title' => '',
    'slug' => '',
    'excerpt' => '',
    'body' => '',
    'image_url' => '',
    'status' => 'draft',
];

if (
    $pdo
    && isset($_GET['edit'])
) {
    $statement = $pdo->prepare(
        'SELECT *
         FROM content_entries
         WHERE id = :id
         LIMIT 1'
    );

    $statement->execute([
        'id' => (int)$_GET['edit'],
    ]);

    $found = $statement->fetch();

    if ($found) {
        $editing = $found;
    }
}

if (
    $pdo
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $contentId = (int)($_POST['content_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $slug = strtolower(
            trim((string)($_POST['slug'] ?? ''))
        );

        $slug = preg_replace(
            '/[^a-z0-9]+/',
            '-',
            $slug
        ) ?? '';

        $slug = trim(
            $slug,
            '-'
        );

        $excerpt = trim(
            (string)($_POST['excerpt'] ?? '')
        );

        $body = trim(
            (string)($_POST['body'] ?? '')
        );

        $imageUrl = trim(
            (string)($_POST['image_url'] ?? '')
        );

        $status = (string)($_POST['status'] ?? 'draft');

        if (!in_array(
            $status,
            ['draft', 'published', 'archived'],
            true
        )) {
            $status = 'draft';
        }

        if (
            $title === ''
            || $slug === ''
        ) {
            $message = 'Title and slug are required.';
        } else {
            $publishedAt = $status === 'published'
                ? date('Y-m-d H:i:s')
                : null;

            if ($contentId > 0) {
                $statement = $pdo->prepare(
                    'UPDATE content_entries
                     SET title = :title,
                         slug = :slug,
                         excerpt = :excerpt,
                         body = :body,
                         image_url = :image_url,
                         status = :status,
                         published_at = CASE
                             WHEN :status_for_date = "published"
                             THEN COALESCE(
                                 published_at,
                                 :published_at
                             )
                             ELSE published_at
                         END
                     WHERE id = :id'
                );

                $statement->execute([
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => $excerpt,
                    'body' => $body,
                    'image_url' => $imageUrl,
                    'status' => $status,
                    'status_for_date' => $status,
                    'published_at' => $publishedAt,
                    'id' => $contentId,
                ]);

                wz_audit(
                    'admin.content.updated',
                    'content_entry',
                    $contentId,
                    [
                        'status' => $status,
                        'slug' => $slug,
                    ]
                );

                $message = 'Article updated.';
                $isSuccess = true;
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO content_entries (
                        content_type,
                        slug,
                        title,
                        excerpt,
                        body,
                        image_url,
                        status,
                        published_at,
                        created_by
                    ) VALUES (
                        :content_type,
                        :slug,
                        :title,
                        :excerpt,
                        :body,
                        :image_url,
                        :status,
                        :published_at,
                        :created_by
                    )'
                );

                $statement->execute([
                    'content_type' => 'article',
                    'slug' => $slug,
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'body' => $body,
                    'image_url' => $imageUrl,
                    'status' => $status,
                    'published_at' => $publishedAt,
                    'created_by' => wz_user()['id'] ?? null,
                ]);

                $newId = (int)$pdo->lastInsertId();

                wz_audit(
                    'admin.content.created',
                    'content_entry',
                    $newId,
                    [
                        'status' => $status,
                        'slug' => $slug,
                    ]
                );

                $message = 'Article created.';
                $isSuccess = true;
            }

            $editing = [
                'id' => 0,
                'title' => '',
                'slug' => '',
                'excerpt' => '',
                'body' => '',
                'image_url' => '',
                'status' => 'draft',
            ];
        }
    }
}

$entries = [];

if ($pdo) {
    $entries = $pdo
        ->query(
            "SELECT
                ce.*,
                u.name AS author_name
             FROM content_entries ce
             LEFT JOIN users u
                ON u.id = ce.created_by
             WHERE ce.content_type = 'article'
             ORDER BY ce.updated_at DESC
             LIMIT 200"
        )
        ->fetchAll();
}

$adminPage = 'content';
$adminTitle = 'Content';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>EDITORIAL</small>
        <h1>Journal CMS</h1>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>
            <?= !empty($editing['id']) ? 'Edit article' : 'New article' ?>
        </h2>
    </div>

    <form
        method="post"
        class="admin-form"
    >
        <input
            type="hidden"
            name="csrf"
            value="<?= h(wz_csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="content_id"
            value="<?= h((string)$editing['id']) ?>"
        >

        <div class="admin-form-grid">
            <div class="admin-field">
                <label for="contentTitle">
                    Title
                </label>

                <input
                    id="contentTitle"
                    name="title"
                    value="<?= h((string)$editing['title']) ?>"
                    required
                >
            </div>

            <div class="admin-field">
                <label for="contentSlug">
                    Slug
                </label>

                <input
                    id="contentSlug"
                    name="slug"
                    value="<?= h((string)$editing['slug']) ?>"
                    placeholder="example-article"
                    required
                >
            </div>

            <div class="admin-field full">
                <label for="contentExcerpt">
                    Excerpt
                </label>

                <textarea
                    id="contentExcerpt"
                    name="excerpt"
                    style="min-height: 90px;"
                ><?= h((string)$editing['excerpt']) ?></textarea>
            </div>

            <div class="admin-field full">
                <label for="contentBody">
                    Article body
                </label>

                <textarea
                    id="contentBody"
                    name="body"
                    placeholder="Separate paragraphs with a blank line."
                ><?= h((string)$editing['body']) ?></textarea>
            </div>

            <div class="admin-field">
                <label for="contentImage">
                    Image URL
                </label>

                <input
                    id="contentImage"
                    name="image_url"
                    value="<?= h((string)$editing['image_url']) ?>"
                    placeholder="uploads/media/example.webp"
                >
            </div>

            <div class="admin-field">
                <label for="contentStatus">
                    Status
                </label>

                <select
                    id="contentStatus"
                    name="status"
                >
                    <?php foreach (['draft', 'published', 'archived'] as $status): ?>
                        <option
                            value="<?= h($status) ?>"
                            <?= $status === $editing['status'] ? 'selected' : '' ?>
                        >
                            <?= h(ucfirst($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button
            class="admin-button"
            type="submit"
        >
            Save article
        </button>
    </form>
</section>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Editorial entries</h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Author</th>
                    <th>Updated</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= h((string)$entry['title']) ?></td>
                        <td><?= h((string)$entry['slug']) ?></td>

                        <td>
                            <span class="admin-badge <?= h((string)$entry['status']) ?>">
                                <?= h((string)$entry['status']) ?>
                            </span>
                        </td>

                        <td><?= h((string)$entry['author_name']) ?></td>
                        <td><?= h((string)$entry['updated_at']) ?></td>

                        <td>
                            <a
                                class="admin-button secondary"
                                href="content.php?edit=<?= urlencode((string)$entry['id']) ?>"
                            >
                                Edit
                            </a>

                            <?php if ($entry['status'] === 'published'): ?>
                                <a
                                    class="admin-button secondary"
                                    href="../article.php?id=<?= urlencode((string)$entry['slug']) ?>"
                                    target="_blank"
                                >
                                    View
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$entries): ?>
                    <tr>
                        <td colspan="6">
                            No CMS articles yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
