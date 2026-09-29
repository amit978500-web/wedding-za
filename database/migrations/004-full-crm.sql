ALTER TABLE users
    MODIFY COLUMN role ENUM('host', 'vendor', 'venue', 'admin') NOT NULL DEFAULT 'host';

CREATE TABLE customer_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    phone VARCHAR(80) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    event_type VARCHAR(120) DEFAULT NULL,
    event_date DATE DEFAULT NULL,
    guest_count INT UNSIGNED DEFAULT NULL,
    budget VARCHAR(120) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT customer_profiles_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venue_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    venue_name VARCHAR(180) NOT NULL,
    city VARCHAR(100) DEFAULT NULL,
    locality VARCHAR(140) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    capacity_min INT UNSIGNED DEFAULT NULL,
    capacity_max INT UNSIGNED DEFAULT NULL,
    rooms INT UNSIGNED DEFAULT NULL,
    venue_type VARCHAR(120) DEFAULT NULL,
    starting_price VARCHAR(120) DEFAULT NULL,
    portfolio_url VARCHAR(500) DEFAULT NULL,
    about TEXT DEFAULT NULL,
    events_json LONGTEXT DEFAULT NULL,
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    plan ENUM('free', 'featured', 'pro') NOT NULL DEFAULT 'free',
    billing_status ENUM('inactive', 'trial', 'active', 'past_due', 'cancelled') NOT NULL DEFAULT 'inactive',
    hero_image_url VARCHAR(500) DEFAULT NULL,
    gallery_json LONGTEXT DEFAULT NULL,
    subscription_started_at DATETIME DEFAULT NULL,
    subscription_ends_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT venue_profiles_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE crm_enquiries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_lead_id BIGINT UNSIGNED DEFAULT NULL UNIQUE,
    customer_user_id BIGINT UNSIGNED DEFAULT NULL,
    business_user_id BIGINT UNSIGNED DEFAULT NULL,
    business_type ENUM('vendor', 'venue') NOT NULL,
    subject VARCHAR(220) DEFAULT NULL,
    event_type VARCHAR(120) DEFAULT NULL,
    event_date DATE DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    guest_count INT UNSIGNED DEFAULT NULL,
    budget VARCHAR(120) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    stage ENUM('new', 'qualified', 'proposal', 'negotiation', 'won', 'lost') NOT NULL DEFAULT 'new',
    value_estimate DECIMAL(12,2) DEFAULT NULL,
    next_follow_up DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX crm_enquiries_customer_index (customer_user_id),
    INDEX crm_enquiries_business_index (business_user_id, business_type),
    INDEX crm_enquiries_stage_index (stage),
    CONSTRAINT crm_enquiries_lead_fk
        FOREIGN KEY (source_lead_id) REFERENCES leads(id)
        ON DELETE SET NULL,
    CONSTRAINT crm_enquiries_customer_fk
        FOREIGN KEY (customer_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT crm_enquiries_business_fk
        FOREIGN KEY (business_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE crm_bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enquiry_id BIGINT UNSIGNED DEFAULT NULL,
    customer_user_id BIGINT UNSIGNED DEFAULT NULL,
    business_user_id BIGINT UNSIGNED DEFAULT NULL,
    business_type ENUM('vendor', 'venue') NOT NULL,
    title VARCHAR(220) NOT NULL,
    event_type VARCHAR(120) DEFAULT NULL,
    event_date DATE DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    amount DECIMAL(12,2) DEFAULT NULL,
    deposit_amount DECIMAL(12,2) DEFAULT NULL,
    status ENUM('tentative', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'tentative',
    payment_status ENUM('pending', 'partial', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX crm_bookings_customer_index (customer_user_id),
    INDEX crm_bookings_business_index (business_user_id, business_type),
    INDEX crm_bookings_event_date_index (event_date),
    CONSTRAINT crm_bookings_enquiry_fk
        FOREIGN KEY (enquiry_id) REFERENCES crm_enquiries(id)
        ON DELETE SET NULL,
    CONSTRAINT crm_bookings_customer_fk
        FOREIGN KEY (customer_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT crm_bookings_business_fk
        FOREIGN KEY (business_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE crm_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    related_type VARCHAR(40) DEFAULT NULL,
    related_id BIGINT UNSIGNED DEFAULT NULL,
    title VARCHAR(220) NOT NULL,
    description TEXT DEFAULT NULL,
    due_at DATETIME DEFAULT NULL,
    priority ENUM('low', 'normal', 'high') NOT NULL DEFAULT 'normal',
    status ENUM('open', 'done') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX crm_tasks_owner_index (owner_user_id, status),
    INDEX crm_tasks_due_index (due_at),
    CONSTRAINT crm_tasks_owner_fk
        FOREIGN KEY (owner_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT crm_tasks_creator_fk
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE crm_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    related_type VARCHAR(40) NOT NULL,
    related_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    visibility ENUM('internal', 'shared') NOT NULL DEFAULT 'internal',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX crm_notes_related_index (related_type, related_id),
    CONSTRAINT crm_notes_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE crm_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_user_id BIGINT UNSIGNED NOT NULL,
    recipient_user_id BIGINT UNSIGNED NOT NULL,
    enquiry_id BIGINT UNSIGNED DEFAULT NULL,
    booking_id BIGINT UNSIGNED DEFAULT NULL,
    body TEXT NOT NULL,
    read_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX crm_messages_sender_index (sender_user_id),
    INDEX crm_messages_recipient_index (recipient_user_id, read_at),
    CONSTRAINT crm_messages_sender_fk
        FOREIGN KEY (sender_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT crm_messages_recipient_fk
        FOREIGN KEY (recipient_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT crm_messages_enquiry_fk
        FOREIGN KEY (enquiry_id) REFERENCES crm_enquiries(id)
        ON DELETE SET NULL,
    CONSTRAINT crm_messages_booking_fk
        FOREIGN KEY (booking_id) REFERENCES crm_bookings(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venue_availability (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venue_user_id BIGINT UNSIGNED NOT NULL,
    availability_date DATE NOT NULL,
    status ENUM('open', 'held', 'booked', 'blocked') NOT NULL DEFAULT 'open',
    note VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY venue_availability_unique (venue_user_id, availability_date),
    INDEX venue_availability_date_index (availability_date),
    CONSTRAINT venue_availability_user_fk
        FOREIGN KEY (venue_user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
