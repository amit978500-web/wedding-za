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

if (
    $pdo
    && $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $status = (string)($_POST['status'] ?? 'tentative');
        $paymentStatus = (string)($_POST['payment_status'] ?? 'pending');
        $amount = trim((string)($_POST['amount'] ?? ''));
        $deposit = trim((string)($_POST['deposit_amount'] ?? ''));

        if (!in_array(
            $status,
            ['tentative', 'confirmed', 'completed', 'cancelled'],
            true
        )) {
            $status = 'tentative';
        }

        if (!in_array(
            $paymentStatus,
            ['pending', 'partial', 'paid', 'refunded'],
            true
        )) {
            $paymentStatus = 'pending';
        }

        $statement = $pdo->prepare(
            'UPDATE crm_bookings
             SET status = :status,
                 payment_status = :payment_status,
                 amount = :amount,
                 deposit_amount = :deposit_amount
             WHERE id = :id'
        );

        $statement->execute([
            'status' => $status,
            'payment_status' => $paymentStatus,
            'amount' => $amount !== ''
                ? (float)$amount
                : null,
            'deposit_amount' => $deposit !== ''
                ? (float)$deposit
                : null,
            'id' => $bookingId,
        ]);

        wz_audit(
            'admin.crm_booking.updated',
            'crm_booking',
            $bookingId,
            [
                'status' => $status,
                'payment_status' => $paymentStatus,
            ]
        );

        $message = 'Booking updated.';
        $isSuccess = true;
    }
}

$bookings = [];

if ($pdo) {
    $bookings = $pdo
        ->query(
            'SELECT
                cb.*,
                cu.name AS customer_name,
                cu.email AS customer_email,
                bu.name AS business_name
             FROM crm_bookings cb
             LEFT JOIN users cu
                ON cu.id = cb.customer_user_id
             LEFT JOIN users bu
                ON bu.id = cb.business_user_id
             ORDER BY cb.event_date IS NULL,
                      cb.event_date ASC,
                      cb.updated_at DESC
             LIMIT 300'
        )
        ->fetchAll();
}

$adminPage = 'bookings';
$adminTitle = 'Bookings';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>CRM OPERATIONS</small>
        <h1>Bookings</h1>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Booking</th>
                    <th>Customer</th>
                    <th>Business</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h((string)$booking['title']) ?>
                            </strong>

                            <br>

                            <small>
                                <?= h((string)$booking['business_type']) ?>
                                ·
                                <?= h((string)$booking['event_type']) ?>
                            </small>
                        </td>

                        <td>
                            <?= h((string)($booking['customer_name'] ?: 'Guest')) ?>
                            <br>
                            <small>
                                <?= h((string)$booking['customer_email']) ?>
                            </small>
                        </td>

                        <td>
                            <?= h((string)($booking['business_name'] ?: 'Unassigned')) ?>
                        </td>

                        <td>
                            <?= h((string)($booking['event_date'] ?: '—')) ?>
                        </td>

                        <td>
                            <?= !empty($booking['amount'])
                                ? '₹' . number_format((float)$booking['amount'])
                                : '—' ?>
                        </td>

                        <td>
                            <span class="admin-badge <?= h((string)$booking['status']) ?>">
                                <?= h((string)$booking['status']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="admin-badge <?= h((string)$booking['payment_status']) ?>">
                                <?= h((string)$booking['payment_status']) ?>
                            </span>
                        </td>

                        <td>
                            <form
                                method="post"
                                class="admin-form"
                                style="padding: 0; min-width: 250px;"
                            >
                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= h(wz_csrf_token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?= h((string)$booking['id']) ?>"
                                >

                                <div class="admin-inline-form">
                                    <select name="status">
                                        <?php foreach (
                                            [
                                                'tentative',
                                                'confirmed',
                                                'completed',
                                                'cancelled',
                                            ] as $status
                                        ): ?>
                                            <option
                                                value="<?= h($status) ?>"
                                                <?= $status === $booking['status'] ? 'selected' : '' ?>
                                            >
                                                <?= h(ucfirst($status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <select name="payment_status">
                                        <?php foreach (
                                            [
                                                'pending',
                                                'partial',
                                                'paid',
                                                'refunded',
                                            ] as $payment
                                        ): ?>
                                            <option
                                                value="<?= h($payment) ?>"
                                                <?= $payment === $booking['payment_status'] ? 'selected' : '' ?>
                                            >
                                                <?= h(ucfirst($payment)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="admin-inline-form">
                                    <input
                                        type="number"
                                        min="0"
                                        step="1"
                                        name="amount"
                                        value="<?= h((string)$booking['amount']) ?>"
                                        placeholder="Amount"
                                    >

                                    <input
                                        type="number"
                                        min="0"
                                        step="1"
                                        name="deposit_amount"
                                        value="<?= h((string)$booking['deposit_amount']) ?>"
                                        placeholder="Deposit"
                                    >
                                </div>

                                <button
                                    class="admin-button"
                                    type="submit"
                                >
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$bookings): ?>
                    <tr>
                        <td colspan="8">
                            No CRM bookings yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
