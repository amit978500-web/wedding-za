<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = $crmRole ?? 'host';
$crmPage = $crmPage ?? 'bookings';
$crmUser = wz_crm_require_role($crmRole);
$userId = (int)($crmUser['id'] ?? 0);
$message = '';
$isSuccess = false;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $crmRole !== 'host'
) {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $booking = wz_crm_booking_for_user(
            $bookingId,
            $userId,
            $crmRole
        );

        if (!$booking) {
            $message = 'Booking not found.';
        } else {
            $status = (string)($_POST['status'] ?? 'tentative');
            $paymentStatus = (string)($_POST['payment_status'] ?? 'pending');

            if (!in_array(
                $status,
                [
                    'tentative',
                    'confirmed',
                    'completed',
                    'cancelled',
                ],
                true
            )) {
                $status = 'tentative';
            }

            if (!in_array(
                $paymentStatus,
                [
                    'pending',
                    'partial',
                    'paid',
                    'refunded',
                ],
                true
            )) {
                $paymentStatus = 'pending';
            }

            $amount = trim(
                (string)($_POST['amount'] ?? '')
            );

            $deposit = trim(
                (string)($_POST['deposit_amount'] ?? '')
            );

            $notes = trim(
                (string)($_POST['notes'] ?? '')
            );

            $pdo = wz_db();

            $statement = $pdo->prepare(
                'UPDATE crm_bookings
                 SET status = :status,
                     payment_status = :payment_status,
                     amount = :amount,
                     deposit_amount = :deposit_amount,
                     notes = :notes
                 WHERE id = :id
                 AND business_user_id = :business_user_id
                 AND business_type = :business_type'
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
                'notes' => $notes,
                'id' => $bookingId,
                'business_user_id' => $userId,
                'business_type' => $crmRole,
            ]);

            wz_audit(
                'crm.booking.updated',
                'crm_booking',
                $bookingId,
                [
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                ]
            );

            if (
                $crmRole === 'venue'
                && !empty($booking['event_date'])
                && in_array(
                    $status,
                    [
                        'confirmed',
                        'completed',
                    ],
                    true
                )
            ) {
                $availability = $pdo->prepare(
                    'INSERT INTO venue_availability (
                        venue_user_id,
                        availability_date,
                        status,
                        note
                    ) VALUES (
                        :venue_user_id,
                        :availability_date,
                        :status,
                        :note
                    )
                    ON DUPLICATE KEY UPDATE
                        status = VALUES(status),
                        note = VALUES(note)'
                );

                $availability->execute([
                    'venue_user_id' => $userId,
                    'availability_date' => $booking['event_date'],
                    'status' => 'booked',
                    'note' => 'CRM booking #' . $bookingId,
                ]);
            }

            $message = 'Booking updated.';
            $isSuccess = true;
        }
    }
}

$bookings = wz_crm_bookings_for_user(
    $userId,
    $crmRole
);

$bookingFilter = $bookingFilter ?? 'all';

if ($crmRole === 'host' && $bookingFilter === 'upcoming') {
    $today = date('Y-m-d');

    $bookings = array_values(
        array_filter(
            $bookings,
            fn (array $booking): bool =>
                !in_array(
                    (string)($booking['status'] ?? ''),
                    ['completed', 'cancelled'],
                    true
                )
                && (
                    empty($booking['event_date'])
                    || (string)$booking['event_date'] >= $today
                )
        )
    );
}

if ($crmRole === 'host' && $bookingFilter === 'completed') {
    $bookings = array_values(
        array_filter(
            $bookings,
            fn (array $booking): bool =>
                (string)($booking['status'] ?? '') === 'completed'
        )
    );
}

$crmTitle = $bookingFilter === 'completed'
    ? 'Completed Bookings'
    : ($bookingFilter === 'upcoming'
        ? 'Upcoming Bookings'
        : 'Bookings');

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            BOOKINGS
        </span>

        <h1>
            <?= $bookingFilter === 'completed'
                ? 'Completed bookings'
                : ($bookingFilter === 'upcoming'
                    ? 'Upcoming bookings'
                    : 'Event bookings') ?>
        </h1>

        <p>
            <?= $crmRole === 'host'
                ? 'Your confirmed and tentative event work in one place.'
                : 'Track status, commercial value and payment progress.' ?>
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="crm-panel">
    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Booking</th>
                    <th>
                        <?= $crmRole === 'host' ? 'Business' : 'Customer' ?>
                    </th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment</th>

                    <?php if ($crmRole !== 'host'): ?>
                        <th>Update</th>
                    <?php endif; ?>
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
                                <?= h((string)($booking['event_type'] ?: 'Event')) ?>
                                <?php if (!empty($booking['city'])): ?>
                                    · <?= h((string)$booking['city']) ?>
                                <?php endif; ?>
                            </small>
                        </td>

                        <td>
                            <?= h(
                                (string)(
                                    $crmRole === 'host'
                                        ? ($booking['business_contact'] ?: 'Business')
                                        : ($booking['customer_name'] ?: 'Customer')
                                )
                            ) ?>
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
                            <span class="crm-badge <?= h((string)$booking['status']) ?>">
                                <?= h(ucfirst((string)$booking['status'])) ?>
                            </span>
                        </td>

                        <td>
                            <span class="crm-badge <?= h((string)$booking['payment_status']) ?>">
                                <?= h(ucfirst((string)$booking['payment_status'])) ?>
                            </span>
                        </td>

                        <?php if ($crmRole !== 'host'): ?>
                            <td>
                                <form
                                    method="post"
                                    class="crm-form"
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

                                    <div class="crm-inline">
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

                                    <div class="crm-inline">
                                        <input
                                            type="number"
                                            name="amount"
                                            min="0"
                                            step="1"
                                            value="<?= h((string)$booking['amount']) ?>"
                                            placeholder="Amount"
                                        >

                                        <input
                                            type="number"
                                            name="deposit_amount"
                                            min="0"
                                            step="1"
                                            value="<?= h((string)$booking['deposit_amount']) ?>"
                                            placeholder="Deposit"
                                        >
                                    </div>

                                    <input
                                        type="hidden"
                                        name="notes"
                                        value="<?= h((string)$booking['notes']) ?>"
                                    >

                                    <button
                                        class="crm-button small"
                                        type="submit"
                                    >
                                        Save
                                    </button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$bookings): ?>
                    <tr>
                        <td
                            colspan="<?= $crmRole === 'host' ? '6' : '7' ?>"
                        >
                            No bookings yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
