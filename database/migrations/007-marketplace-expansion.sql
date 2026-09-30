ALTER TABLE venue_profiles
    ADD COLUMN price_per_plate_veg DECIMAL(12,2) DEFAULT NULL,
    ADD COLUMN price_per_plate_nonveg DECIMAL(12,2) DEFAULT NULL,
    ADD COLUMN rental_price DECIMAL(12,2) DEFAULT NULL,
    ADD COLUMN parking_capacity INT UNSIGNED DEFAULT NULL,
    ADD COLUMN video_url VARCHAR(500) DEFAULT NULL,
    ADD COLUMN spaces_json LONGTEXT DEFAULT NULL,
    ADD COLUMN amenities_json LONGTEXT DEFAULT NULL,
    ADD COLUMN policies_json LONGTEXT DEFAULT NULL;

ALTER TABLE vendor_profiles
    ADD COLUMN service_areas_json LONGTEXT DEFAULT NULL,
    ADD COLUMN packages_json LONGTEXT DEFAULT NULL,
    ADD COLUMN availability_enabled TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE crm_messages
    ADD COLUMN attachment_url VARCHAR(500) DEFAULT NULL AFTER body;

CREATE TABLE marketplace_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reviewer_user_id BIGINT UNSIGNED NOT NULL,
    business_user_id BIGINT UNSIGNED NOT NULL,
    business_type ENUM('vendor','venue') NOT NULL,
    booking_id BIGINT UNSIGNED DEFAULT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    title VARCHAR(180) DEFAULT NULL,
    body TEXT NOT NULL,
    photos_json LONGTEXT DEFAULT NULL,
    is_verified_booking TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('pending','published','rejected') NOT NULL DEFAULT 'pending',
    business_reply TEXT DEFAULT NULL,
    replied_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX marketplace_reviews_business_index (business_user_id, business_type, status),
    INDEX marketplace_reviews_reviewer_index (reviewer_user_id),
    CONSTRAINT marketplace_reviews_reviewer_fk
        FOREIGN KEY (reviewer_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT marketplace_reviews_business_fk
        FOREIGN KEY (business_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT marketplace_reviews_booking_fk
        FOREIGN KEY (booking_id) REFERENCES crm_bookings(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wedding_collaborators (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    collaborator_user_id BIGINT UNSIGNED DEFAULT NULL,
    email VARCHAR(190) NOT NULL,
    name VARCHAR(140) DEFAULT NULL,
    relation_label VARCHAR(80) DEFAULT NULL,
    permission ENUM('view','edit') NOT NULL DEFAULT 'view',
    invite_token VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('invited','accepted','removed') NOT NULL DEFAULT 'invited',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME DEFAULT NULL,
    UNIQUE KEY wedding_collaborator_unique (owner_user_id, email),
    INDEX wedding_collaborator_email_index (email, status),
    CONSTRAINT wedding_collaborator_owner_fk
        FOREIGN KEY (owner_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT wedding_collaborator_user_fk
        FOREIGN KEY (collaborator_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE saved_idea_boards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX saved_idea_boards_user_index (user_id),
    CONSTRAINT saved_idea_boards_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE saved_ideas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    board_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    source_type VARCHAR(50) NOT NULL,
    source_key VARCHAR(180) NOT NULL,
    title VARCHAR(220) NOT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    source_url VARCHAR(500) DEFAULT NULL,
    notes VARCHAR(500) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY saved_idea_unique (user_id, board_id, source_type, source_key),
    INDEX saved_ideas_user_index (user_id, created_at),
    CONSTRAINT saved_ideas_board_fk
        FOREIGN KEY (board_id) REFERENCES saved_idea_boards(id)
        ON DELETE CASCADE,
    CONSTRAINT saved_ideas_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(60) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body VARCHAR(500) DEFAULT NULL,
    action_url VARCHAR(500) DEFAULT NULL,
    read_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX user_notifications_user_index (user_id, read_at, created_at),
    CONSTRAINT user_notifications_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vendor_availability (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_user_id BIGINT UNSIGNED NOT NULL,
    availability_date DATE NOT NULL,
    status ENUM('open','held','booked','blocked') NOT NULL DEFAULT 'open',
    note VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY vendor_availability_unique (vendor_user_id, availability_date),
    INDEX vendor_availability_date_index (availability_date, status),
    CONSTRAINT vendor_availability_user_fk
        FOREIGN KEY (vendor_user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE crm_quotes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enquiry_id BIGINT UNSIGNED DEFAULT NULL,
    booking_id BIGINT UNSIGNED DEFAULT NULL,
    customer_user_id BIGINT UNSIGNED NOT NULL,
    business_user_id BIGINT UNSIGNED NOT NULL,
    business_type ENUM('vendor','venue') NOT NULL,
    quote_number VARCHAR(80) NOT NULL UNIQUE,
    title VARCHAR(220) NOT NULL,
    line_items_json LONGTEXT DEFAULT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('draft','sent','accepted','rejected','expired') NOT NULL DEFAULT 'draft',
    valid_until DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX crm_quotes_customer_index (customer_user_id, status),
    INDEX crm_quotes_business_index (business_user_id, business_type, status),
    CONSTRAINT crm_quotes_enquiry_fk
        FOREIGN KEY (enquiry_id) REFERENCES crm_enquiries(id)
        ON DELETE SET NULL,
    CONSTRAINT crm_quotes_booking_fk
        FOREIGN KEY (booking_id) REFERENCES crm_bookings(id)
        ON DELETE SET NULL,
    CONSTRAINT crm_quotes_customer_fk
        FOREIGN KEY (customer_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT crm_quotes_business_fk
        FOREIGN KEY (business_user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venue_plan_purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_user_id BIGINT UNSIGNED NOT NULL,
    plan_code VARCHAR(60) NOT NULL DEFAULT 'one_wedding_venue_assist',
    amount DECIMAL(12,2) NOT NULL DEFAULT 1000,
    currency CHAR(3) NOT NULL DEFAULT 'INR',
    status ENUM('pending','paid','active','completed','cancelled','refunded') NOT NULL DEFAULT 'pending',
    payment_provider VARCHAR(60) DEFAULT NULL,
    provider_order_id VARCHAR(180) DEFAULT NULL,
    provider_payment_id VARCHAR(180) DEFAULT NULL,
    assigned_admin_user_id BIGINT UNSIGNED DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    activated_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX venue_plan_customer_index (customer_user_id, status),
    INDEX venue_plan_admin_index (assigned_admin_user_id, status),
    CONSTRAINT venue_plan_customer_fk
        FOREIGN KEY (customer_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT venue_plan_admin_fk
        FOREIGN KEY (assigned_admin_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recently_viewed (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    entity_type ENUM('vendor','venue','article','wedding') NOT NULL,
    entity_key VARCHAR(180) NOT NULL,
    viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY recently_viewed_unique (user_id, entity_type, entity_key),
    INDEX recently_viewed_user_index (user_id, viewed_at),
    CONSTRAINT recently_viewed_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE business_analytics_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_user_id BIGINT UNSIGNED NOT NULL,
    business_type ENUM('vendor','venue') NOT NULL,
    customer_user_id BIGINT UNSIGNED DEFAULT NULL,
    event_type ENUM('profile_view','shortlist','enquiry','message','quote','booking') NOT NULL,
    entity_id BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX business_analytics_business_index (business_user_id, business_type, event_type, created_at),
    CONSTRAINT business_analytics_business_fk
        FOREIGN KEY (business_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT business_analytics_customer_fk
        FOREIGN KEY (customer_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wedding_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submitter_user_id BIGINT UNSIGNED DEFAULT NULL,
    submitter_name VARCHAR(140) DEFAULT NULL,
    submitter_email VARCHAR(190) DEFAULT NULL,
    couple_names VARCHAR(180) NOT NULL,
    city VARCHAR(100) DEFAULT NULL,
    event_date DATE DEFAULT NULL,
    story TEXT DEFAULT NULL,
    cover_image_url VARCHAR(500) DEFAULT NULL,
    gallery_json LONGTEXT DEFAULT NULL,
    vendors_json LONGTEXT DEFAULT NULL,
    status ENUM('submitted','reviewing','published','rejected') NOT NULL DEFAULT 'submitted',
    admin_note TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX wedding_submissions_status_index (status, created_at),
    CONSTRAINT wedding_submissions_user_fk
        FOREIGN KEY (submitter_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
