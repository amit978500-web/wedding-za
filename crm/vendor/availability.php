<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = 'vendor';
$crmPage = 'availability';
$crmUser = wz_crm_require_role('vendor');
$userId = (int)($crmUser['id'] ?? 0);
$message = '';
$isSuccess = false;
$pdo = wz_db();

if (
    $pdo
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $date = (string)($_POST['availability_date'] ?? '');
        $status = (string)($_POST['status'] ?? 'open');
        $note = trim((string)($_POST['note'] ?? ''));

        if (!in_array(
            $status,
            [
                'open',
                'held',
                'booked',
                'blocked',
            ],
            true
        )) {
            $status = 'open';
        }

        if ($date !== '') {
            $statement = $pdo->prepare(
                'INSERT INTO vendor_availability (
                    vendor_user_id,
                    availability_date,
                    status,
                    note
                ) VALUES (
                    :vendor_user_id,
                    :availability_date,
                    :status,
                    :note
                )
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    note = VALUES(note)'
            );

            $statement->execute([
                'vendor_user_id' => $userId,
                'availability_date' => $date,
                'status' => $status,
                'note' => $note,
            ]);

            wz_audit(
                'crm.vendor_availability.updated',
                'vendor_availability',
                null,
                [
                    'date' => $date,
                    'status' => $status,
                ]
            );

            $message = 'Availability updated.';
            $isSuccess = true;
        }
    }
}

$availabilityRows = [];

if ($pdo) {
    $statement = $pdo->prepare(
        'SELECT *
         FROM vendor_availability
         WHERE vendor_user_id = :vendor_user_id
         AND availability_date >= CURDATE()
         ORDER BY availability_date ASC
         LIMIT 120'
    );

    $statement->execute([
        'vendor_user_id' => $userId,
    ]);

    $availabilityRows = $statement->fetchAll();
}

$crmTitle = 'Availability';

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            VENDOR OPERATIONS
        </span>

        <h1>
            Availability
        </h1>

        <p>
            Mark open, held, booked or blocked dates so customers and your team have a clear calendar source of truth.
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="crm-layout">
    <section class="crm-panel">
        <div class="crm-panel-head">
            <div>
                <h2>Upcoming dates</h2>
            </div>
        </div>

        <?php if ($availabilityRows): ?>
            <div class="crm-calendar-grid">
                <?php foreach ($availabilityRows as $row): ?>
                    <article class="crm-day">
                        <strong>
                            <?= h(date('d M Y',strtotime((string)$row['availability_date']))) ?>
                        </strong>

                        <span class="crm-badge <?= h((string)$row['status']) ?>">
                            <?= h(ucfirst((string)$row['status'])) ?>
                        </span>

                        <?php if (!empty($row['note'])): ?>
                            <span><?= h((string)$row['note']) ?></span>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="crm-empty">
                No availability dates have been added yet.
            </div>
        <?php endif; ?>
    </section>

    <aside>
        <section class="crm-panel">
            <div class="crm-panel-head">
                <div>
                    <h2>Update date</h2>
                </div>
            </div>

            <form method="post" class="crm-form">
                <input type="hidden" name="csrf" value="<?= h(wz_csrf_token()) ?>">

                <div class="crm-field">
                    <label for="availabilityDate">Date</label>
                    <input
                        id="availabilityDate"
                        type="date"
                        name="availability_date"
                        required
                    >
                </div>

                <div class="crm-field">
                    <label for="availabilityStatus">Status</label>
                    <select id="availabilityStatus" name="status">
                        <option value="open">Open</option>
                        <option value="held">Held</option>
                        <option value="booked">Booked</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>

                <div class="crm-field">
                    <label for="availabilityNote">Note</label>
                    <input id="availabilityNote" name="note">
                </div>

                <button class="crm-button" type="submit">
                    Save date
                </button>
            </form>
        </section>
    </aside>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
