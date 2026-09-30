<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm.php';

$crmRole = 'venue';
$crmPage = 'bookings';
$crmTitle = 'Bookings';

$crmUser = wz_crm_require_role('venue');
$userId = (int)($crmUser['id'] ?? 0);
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

        $booking = wz_crm_booking_for_user(
            $bookingId,
            $userId,
            'venue'
        );

        if (!$booking) {
            $message = 'Booking not found.';
        } else {
            $status = (string)($_POST['status'] ?? 'tentative');

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

            $amount = trim(
                (string)($_POST['amount'] ?? '')
            );

            $eventDate = trim(
                (string)($_POST['event_date'] ?? '')
            );

            $statement = $pdo->prepare(
                'UPDATE crm_bookings
                 SET status = :status,
                     amount = :amount,
                     event_date = :event_date
                 WHERE id = :id
                 AND business_user_id = :business_user_id
                 AND business_type = "venue"'
            );

            $statement->execute([
                'status' => $status,
                'amount' => $amount !== ''
                    ? (float)$amount
                    : null,
                'event_date' => $eventDate !== ''
                    ? $eventDate
                    : null,
                'id' => $bookingId,
                'business_user_id' => $userId,
            ]);

            $oldEventDate = (string)(
                $booking['event_date']
                ?? ''
            );

            $availabilityNote =
                'CRM booking #' . $bookingId;

            if (
                $oldEventDate !== ''
                && (
                    $oldEventDate !== $eventDate
                    || !in_array(
                        $status,
                        [
                            'confirmed',
                            'completed',
                        ],
                        true
                    )
                )
            ) {
                $releaseAvailability = $pdo->prepare(
                    'DELETE FROM venue_availability
                     WHERE venue_user_id = :venue_user_id
                     AND availability_date = :availability_date
                     AND note = :note'
                );

                $releaseAvailability->execute([
                    'venue_user_id' => $userId,
                    'availability_date' => $oldEventDate,
                    'note' => $availabilityNote,
                ]);
            }

            if (
                $eventDate !== ''
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
                    'availability_date' => $eventDate,
                    'status' => 'booked',
                    'note' => $availabilityNote,
                ]);
            }

            wz_crm_sync_booking_payment_status(
                $bookingId
            );

            wz_audit(
                'crm.venue_booking.updated',
                'crm_booking',
                $bookingId,
                [
                    'status' => $status,
                    'amount' => $amount,
                    'event_date' => $eventDate,
                ]
            );

            $message = 'Booking updated.';
            $isSuccess = true;
        }
    }
}

$bookings = wz_venue_functions(
    $userId,
    'all'
);

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="crm-head">
    <div>
        <span class="crm-eyebrow">
            COMMERCIAL
        </span>

        <h1>
            Bookings
        </h1>

        <p>
            Confirm function dates, booking value and operational booking status.
        </p>
    </div>

    <div class="crm-actions">
        <a
            class="crm-button secondary"
            href="<?= h(wz_app_url('crm/venue/payments.php')) ?>"
        >
            Payments
        </a>

        <a
            class="crm-button secondary"
            href="<?= h(wz_app_url('crm/venue/functions-upcoming.php')) ?>"
        >
            Upcoming functions
        </a>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="crm-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<section class="crm-panel">
    <div class="crm-panel-head">
        <div>
            <h2>Venue bookings</h2>
            <p>Booking confirmation and value management.</p>
        </div>
    </div>

    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Booking</th>
                    <th>Customer</th>
                    <th>Function date</th>
                    <th>Amount</th>
                    <th>Received</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Update</th>
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
                                <?= h(
                                    (string)(
                                        $booking['event_type']
                                        ?: 'Event'
                                    )
                                ) ?>
                            </small>
                        </td>

                        <td>
                            <?= h(
                                (string)(
                                    $booking['customer_name']
                                    ?: 'Customer'
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= h(
                                (string)(
                                    $booking['event_date']
                                    ?: 'Not set'
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= !empty($booking['amount'])
                                ? '₹' . number_format(
                                    (float)$booking['amount']
                                )
                                : '—' ?>
                        </td>

                        <td>
                            <?= !empty($booking['deposit_amount'])
                                ? '₹' . number_format(
                                    (float)$booking['deposit_amount']
                                )
                                : '₹0' ?>
                        </td>

                        <td>
                            <span class="crm-badge <?= h((string)$booking['payment_status']) ?>">
                                <?= h(ucfirst((string)$booking['payment_status'])) ?>
                            </span>
                        </td>

                        <td>
                            <span class="crm-badge <?= h((string)$booking['status']) ?>">
                                <?= h(ucfirst((string)$booking['status'])) ?>
                            </span>
                        </td>

                        <td>
                            <details>
                                <summary
                                    style="cursor: pointer; font-size: 9px;"
                                >
                                    Edit
                                </summary>

                                <form
                                    method="post"
                                    class="crm-form"
                                    style="padding: 10px 0 0; min-width: 220px;"
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

                                    <div class="crm-field">
                                        <label>Status</label>

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
                                    </div>

                                    <div class="crm-field">
                                        <label>Function date</label>

                                        <input
                                            type="date"
                                            name="event_date"
                                            value="<?= h((string)$booking['event_date']) ?>"
                                        >
                                    </div>

                                    <div class="crm-field">
                                        <label>Booking value</label>

                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            name="amount"
                                            value="<?= h((string)$booking['amount']) ?>"
                                        >
                                    </div>

                                    <button
                                        class="crm-button small"
                                        type="submit"
                                    >
                                        Save booking
                                    </button>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$bookings): ?>
                    <tr>
                        <td colspan="8">
                            No bookings yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
