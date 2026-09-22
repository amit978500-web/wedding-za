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
