<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-crm.php';

wz_admin_crm_require_admin();

$message = '';
$isSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wz_csrf_valid($_POST['csrf'] ?? null)) {
        $message = 'Session expired. Refresh and try again.';
    } else {
        $result = wz_admin_crm_save_commission(
            $_POST
        );

        $message = (string)$result['message'];
        $isSuccess = (bool)$result['ok'];
    }
}

$commissions = wz_admin_crm_commissions();
$bookings = wz_admin_crm_functions('all');

$commissionValue = array_sum(
    array_map(
        fn (array $item): float =>
            $item['status'] !== 'waived'
                ? (float)$item['commission_amount']
                : 0.0,
        $commissions
    )
);

$commissionPaid = array_sum(
    array_map(
        fn (array $item): float =>
            $item['status'] === 'paid'
                ? (float)$item['commission_amount']
                : 0.0,
        $commissions
    )
);

$adminPage = 'payments-commission';
$adminTitle = 'Commission';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>PAYMENTS</small>
        <h1>Commission</h1>

        <p class="admin-section-note">
            Track Wedding Za commission against converted venue and vendor bookings.
        </p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?= $isSuccess ? 'success' : '' ?>">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<div class="admin-grid">
    <article class="admin-stat">
        <span>Commission records</span>
        <strong><?= h((string)count($commissions)) ?></strong>
    </article>

    <article class="admin-stat">
        <span>Total commission</span>
        <strong style="font-size: 30px;">
            ₹<?= h(number_format($commissionValue)) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Paid</span>
        <strong
            class="admin-money-positive"
            style="font-size: 30px;"
        >
            ₹<?= h(number_format($commissionPaid)) ?>
        </strong>
    </article>

    <article class="admin-stat">
        <span>Outstanding</span>
        <strong
            class="admin-money-due"
            style="font-size: 30px;"
        >
            ₹<?= h(
                number_format(
                    max(
                        0,
                        $commissionValue - $commissionPaid
                    )
                )
            ) ?>
        </strong>
    </article>
</div>

<div
    style="
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 28px;
        align-items: start;
    "
>
    <section class="admin-panel">
        <div class="admin-panel-head">
            <h2>Commission register</h2>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Booking</th>
                        <th>Business</th>
                        <th>Type</th>
                        <th>Gross</th>
                        <th>Rate</th>
                        <th>Commission</th>
                        <th>Due</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($commissions as $item): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= h((string)$item['booking_title']) ?>
                                </strong>

                                <?php if (!empty($item['event_date'])): ?>
                                    <br>
                                    <small>
                                        <?= h((string)$item['event_date']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $item['business_contact']
                                        ?: 'Business'
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= h(ucfirst((string)$item['business_type'])) ?>
                            </td>

                            <td>
                                ₹<?= h(
                                    number_format(
                                        (float)$item['gross_amount']
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= h(
                                    number_format(
                                        (float)$item['commission_rate'],
                                        2
                                    )
                                ) ?>%
                            </td>

                            <td>
                                <strong>
                                    ₹<?= h(
                                        number_format(
                                            (float)$item['commission_amount']
                                        )
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= h(
                                    (string)(
                                        $item['due_date']
                                        ?: '—'
                                    )
                                ) ?>
                            </td>

                            <td>
                                <span class="admin-badge <?= h((string)$item['status']) ?>">
                                    <?= h(ucfirst((string)$item['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$commissions): ?>
                        <tr>
                            <td colspan="8">
                                No commission records yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside class="admin-panel">
        <div class="admin-panel-head">
            <h2>Set commission</h2>
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

            <div class="admin-field">
                <label>Booking</label>

                <select
                    name="booking_id"
                    required
                >
                    <option value="">
                        Choose booking
                    </option>

                    <?php foreach ($bookings as $booking): ?>
                        <option value="<?= h((string)$booking['id']) ?>">
                            <?= h(
                                (string)$booking['title']
                                . ' · '
                                . (string)(
                                    $booking['business_contact']
                                    ?: 'Business'
                                )
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-field">
                <label>Gross amount</label>

                <input
                    type="number"
                    min="0"
                    step="0.01"
                    name="gross_amount"
                    placeholder="Uses booking value if blank"
                >
            </div>

            <div class="admin-field">
                <label>Commission rate (%)</label>

                <input
                    type="number"
                    min="0"
                    step="0.001"
                    name="commission_rate"
                    required
                >
            </div>

            <div class="admin-field">
                <label>Status</label>

                <select name="status">
                    <option value="pending">Pending</option>
                    <option value="invoiced">Invoiced</option>
                    <option value="paid">Paid</option>
                    <option value="waived">Waived</option>
                </select>
            </div>

            <div class="admin-field">
                <label>Due date</label>

                <input
                    type="date"
                    name="due_date"
                >
            </div>

            <div class="admin-field">
                <label>Notes</label>

                <textarea name="notes"></textarea>
            </div>

            <button
                class="admin-button"
                type="submit"
            >
                Save commission
            </button>
        </form>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
