CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('host', 'vendor', 'admin') NOT NULL DEFAULT 'host',
    status ENUM('active', 'pending', 'suspended') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vendor_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    business_name VARCHAR(160) NOT NULL,
    category VARCHAR(120) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    events_json LONGTEXT DEFAULT NULL,
    starting_price VARCHAR(120) DEFAULT NULL,
    portfolio_url VARCHAR(500) DEFAULT NULL,
    about TEXT DEFAULT NULL,
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT vendor_profiles_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    type VARCHAR(60) NOT NULL,
    name VARCHAR(120) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    phone VARCHAR(80) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    vendor VARCHAR(160) DEFAULT NULL,
    business VARCHAR(160) DEFAULT NULL,
    category VARCHAR(120) DEFAULT NULL,
    event_date DATE DEFAULT NULL,
    topic VARCHAR(120) DEFAULT NULL,
    price VARCHAR(120) DEFAULT NULL,
    portfolio VARCHAR(500) DEFAULT NULL,
    vendors TEXT DEFAULT NULL,
    message TEXT DEFAULT NULL,
    ip VARCHAR(64) DEFAULT NULL,
    status ENUM('new', 'contacted', 'closed') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX leads_type_index (type),
    INDEX leads_vendor_index (vendor),
    INDEX leads_email_index (email),
    CONSTRAINT leads_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_workspaces (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    brief_json LONGTEXT DEFAULT NULL,
    tasks_json LONGTEXT DEFAULT NULL,
    budget_json LONGTEXT DEFAULT NULL,
    shortlist_json LONGTEXT DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT event_workspaces_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE content_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(60) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    title VARCHAR(220) NOT NULL,
    excerpt TEXT DEFAULT NULL,
    body LONGTEXT DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    published_at DATETIME DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY content_type_slug_unique (content_type, slug),
    INDEX content_status_index (status),
    CONSTRAINT content_entries_user_fk
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE media_assets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uploaded_by BIGINT UNSIGNED DEFAULT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL UNIQUE,
    relative_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    width INT UNSIGNED DEFAULT NULL,
    height INT UNSIGNED DEFAULT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT media_assets_user_fk
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    ip VARCHAR(64) NOT NULL,
    was_successful TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX login_attempt_lookup (email, ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) DEFAULT NULL,
    entity_id BIGINT UNSIGNED DEFAULT NULL,
    metadata_json LONGTEXT DEFAULT NULL,
    ip VARCHAR(64) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX audit_action_index (action),
    INDEX audit_entity_index (entity_type, entity_id),
    CONSTRAINT audit_log_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE vendor_profiles
    ADD COLUMN featured TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN hero_image_url VARCHAR(500) DEFAULT NULL,
    ADD COLUMN gallery_json LONGTEXT DEFAULT NULL;

ALTER TABLE leads
    ADD COLUMN admin_note TEXT DEFAULT NULL;


ALTER TABLE vendor_profiles
    ADD COLUMN plan ENUM('free', 'featured', 'pro') NOT NULL DEFAULT 'free',
    ADD COLUMN billing_status ENUM('inactive', 'trial', 'active', 'past_due', 'cancelled') NOT NULL DEFAULT 'inactive',
    ADD COLUMN subscription_started_at DATETIME DEFAULT NULL,
    ADD COLUMN subscription_ends_at DATETIME DEFAULT NULL;


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


-- Marketplace expansion
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
