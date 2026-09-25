ALTER TABLE vendor_profiles
    ADD COLUMN featured TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN hero_image_url VARCHAR(500) DEFAULT NULL,
    ADD COLUMN gallery_json LONGTEXT DEFAULT NULL;

ALTER TABLE leads
    ADD COLUMN admin_note TEXT DEFAULT NULL;

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
