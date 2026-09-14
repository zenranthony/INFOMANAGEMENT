ALTER TABLE loans
    ADD COLUMN IF NOT EXISTS loan_product VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS loan_term VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS loan_purpose TEXT NULL,
    ADD COLUMN IF NOT EXISTS contact_number VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS birth_date DATE NULL,
    ADD COLUMN IF NOT EXISTS civil_status VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS occupation VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS employer VARCHAR(160) NULL,
    ADD COLUMN IF NOT EXISTS monthly_income DECIMAL(15,2) NULL,
    ADD COLUMN IF NOT EXISTS id_type VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS id_number VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS id_document_path VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS proof_income_path VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS identity_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS identity_verified_by INT(10) UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS identity_verified_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS identity_notes VARCHAR(500) NULL;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_reset_token (token_hash),
    INDEX idx_password_reset_user (user_id),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deposit_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(40) NOT NULL,
    payment_reference VARCHAR(120) NOT NULL,
    description VARCHAR(255) NOT NULL,
    receipt_path VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_notes VARCHAR(500) DEFAULT NULL,
    reviewed_by INT UNSIGNED DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_deposit_requests_status (status, created_at),
    CONSTRAINT fk_deposit_requests_account FOREIGN KEY (account_id) REFERENCES accounts(id),
    CONSTRAINT fk_deposit_requests_member FOREIGN KEY (member_id) REFERENCES members(id),
    CONSTRAINT fk_deposit_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
