CREATE TABLE notification_preferences (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    email_enabled TINYINT(1) NOT NULL DEFAULT 0,
    push_enabled TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT notification_preferences_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_delivery_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED DEFAULT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('email','push') NOT NULL,
    status ENUM('sent','skipped','failed') NOT NULL,
    provider VARCHAR(80) DEFAULT NULL,
    response_code INT DEFAULT NULL,
    error_message VARCHAR(500) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX notification_delivery_user_index (
        user_id,
        channel,
        created_at
    ),
    INDEX notification_delivery_notification_index (
        notification_id
    ),
    CONSTRAINT notification_delivery_notification_fk
        FOREIGN KEY (notification_id) REFERENCES user_notifications(id)
        ON DELETE SET NULL,
    CONSTRAINT notification_delivery_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
