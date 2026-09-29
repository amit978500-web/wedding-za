<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (!wz_is_admin()) {
    header('Location: login.php');
    exit;
}

$pdo = wz_db();
$customers = [];

if ($pdo) {
    $customers = $pdo
        ->query(
            "SELECT
                u.id,
                u.name,
                u.email,
                u.status,
                u.created_at,
                cp.phone,
                cp.city,
                cp.event_type,
                cp.event_date,
                cp.guest_count,
                cp.budget,
                (
                    SELECT COUNT(*)
                    FROM crm_enquiries ce
                    WHERE ce.customer_user_id = u.id
                ) AS enquiry_count,
                (
                    SELECT COUNT(*)
                    FROM crm_bookings cb
                    WHERE cb.customer_user_id = u.id
                ) AS booking_count
             FROM users u
             LEFT JOIN customer_profiles cp
                ON cp.user_id = u.id
             WHERE u.role = 'host'
             ORDER BY u.created_at DESC"
        )
        ->fetchAll();
}

$adminPage = 'customers';
$adminTitle = 'Customers';

require __DIR__ . '/includes/header.php';
?>

<div class="admin-head">
    <div>
        <small>CRM</small>
        <h1>Customers</h1>
    </div>
</div>

<section class="admin-panel">
    <div class="admin-panel-head">
        <h2>Customer accounts</h2>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Event</th>
                    <th>Budget</th>
                    <th>Enquiries</th>
                    <th>Bookings</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= h((string)$customer['name']) ?>
                            </strong>

                            <br>

                            <small>
                                Joined <?= h((string)$customer['created_at']) ?>
                            </small>
                        </td>

                        <td>
                            <?= h((string)$customer['email']) ?>

                            <?php if (!empty($customer['phone'])): ?>
                                <br>
                                <?= h((string)$customer['phone']) ?>
                            <?php endif; ?>

                            <?php if (!empty($customer['city'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$customer['city']) ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= h((string)($customer['event_type'] ?: '—')) ?>

                            <?php if (!empty($customer['event_date'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$customer['event_date']) ?>
                                </small>
                            <?php endif; ?>

                            <?php if (!empty($customer['guest_count'])): ?>
                                <br>
                                <small>
                                    <?= h((string)$customer['guest_count']) ?> guests
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= h((string)($customer['budget'] ?: '—')) ?>
                        </td>

                        <td>
                            <?= h((string)$customer['enquiry_count']) ?>
                        </td>

                        <td>
                            <?= h((string)$customer['booking_count']) ?>
                        </td>

                        <td>
                            <span class="admin-badge <?= h((string)$customer['status']) ?>">
                                <?= h((string)$customer['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$customers): ?>
                    <tr>
                        <td colspan="7">
                            No customer accounts yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
