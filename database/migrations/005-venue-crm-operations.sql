ALTER TABLE crm_enquiries
    ADD COLUMN site_visit_at DATETIME DEFAULT NULL AFTER next_follow_up,
    ADD COLUMN site_visit_status ENUM(
        'not_scheduled',
        'scheduled',
        'completed',
        'cancelled'
    ) NOT NULL DEFAULT 'not_scheduled' AFTER site_visit_at,
    ADD INDEX crm_enquiries_site_visit_index (
        business_user_id,
        business_type,
        site_visit_at
    );

CREATE TABLE crm_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    customer_user_id BIGINT UNSIGNED DEFAULT NULL,
    business_user_id BIGINT UNSIGNED NOT NULL,
    business_type ENUM('vendor', 'venue') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method ENUM(
        'cash',
        'bank_transfer',
        'upi',
        'card',
        'cheque',
        'other'
    ) NOT NULL DEFAULT 'bank_transfer',
    status ENUM(
        'pending',
        'received',
        'refunded',
        'failed'
    ) NOT NULL DEFAULT 'received',
    reference VARCHAR(180) DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    INDEX crm_payments_booking_index (booking_id),
    INDEX crm_payments_business_index (
        business_user_id,
        business_type,
        paid_at
    ),
    INDEX crm_payments_customer_index (customer_user_id),
    CONSTRAINT crm_payments_booking_fk
        FOREIGN KEY (booking_id) REFERENCES crm_bookings(id)
        ON DELETE CASCADE,
    CONSTRAINT crm_payments_customer_fk
        FOREIGN KEY (customer_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT crm_payments_business_fk
        FOREIGN KEY (business_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT crm_payments_creator_fk
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
