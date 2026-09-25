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
