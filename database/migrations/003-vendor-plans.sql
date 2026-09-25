ALTER TABLE vendor_profiles
    ADD COLUMN plan ENUM('free', 'featured', 'pro') NOT NULL DEFAULT 'free',
    ADD COLUMN billing_status ENUM('inactive', 'trial', 'active', 'past_due', 'cancelled') NOT NULL DEFAULT 'inactive',
    ADD COLUMN subscription_started_at DATETIME DEFAULT NULL,
    ADD COLUMN subscription_ends_at DATETIME DEFAULT NULL;
