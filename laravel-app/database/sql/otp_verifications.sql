-- Secure OTP verifications table (MySQL / MariaDB)
-- Prefer: php artisan migrate

CREATE TABLE IF NOT EXISTS otp_verifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    email VARCHAR(255) NULL,
    purpose VARCHAR(64) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 5,
    last_sent_at TIMESTAMP NULL,
    verified_at TIMESTAMP NULL,
    invalidated_at TIMESTAMP NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX otp_verifications_email_index (email),
    INDEX otp_verifications_email_purpose_index (email, purpose),
    INDEX otp_verifications_user_id_purpose_index (user_id, purpose),
    INDEX otp_verifications_purpose_expires_at_index (purpose, expires_at),
    CONSTRAINT otp_verifications_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
