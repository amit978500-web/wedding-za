CREATE TABLE crm_invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(80) NOT NULL UNIQUE,
    booking_id BIGINT UNSIGNED NOT NULL,
    customer_user_id BIGINT UNSIGNED DEFAULT NULL,
    business_user_id BIGINT UNSIGNED DEFAULT NULL,
    business_type ENUM('vendor', 'venue') NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM(
        'draft',
        'issued',
        'paid',
        'overdue',
        'void'
    ) NOT NULL DEFAULT 'draft',
    issued_at DATETIME DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    INDEX crm_invoices_booking_index (booking_id),
    INDEX crm_invoices_status_index (status, due_date),
    CONSTRAINT crm_invoices_booking_fk
        FOREIGN KEY (booking_id) REFERENCES crm_bookings(id)
        ON DELETE CASCADE,
    CONSTRAINT crm_invoices_customer_fk
        FOREIGN KEY (customer_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT crm_invoices_business_fk
        FOREIGN KEY (business_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT crm_invoices_creator_fk
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE crm_commissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL UNIQUE,
    business_user_id BIGINT UNSIGNED DEFAULT NULL,
    business_type ENUM('vendor', 'venue') NOT NULL,
    gross_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    commission_rate DECIMAL(6,3) NOT NULL DEFAULT 0,
    commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM(
        'pending',
        'invoiced',
        'paid',
        'waived'
    ) NOT NULL DEFAULT 'pending',
    due_date DATE DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    INDEX crm_commissions_status_index (status, due_date),
    CONSTRAINT crm_commissions_booking_fk
        FOREIGN KEY (booking_id) REFERENCES crm_bookings(id)
        ON DELETE CASCADE,
    CONSTRAINT crm_commissions_business_fk
        FOREIGN KEY (business_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT crm_commissions_creator_fk
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_team_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    job_title VARCHAR(120) DEFAULT NULL,
    phone VARCHAR(80) DEFAULT NULL,
    permissions_json LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT admin_team_profiles_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
