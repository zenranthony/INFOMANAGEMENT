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

CREATE TABLE IF NOT EXISTS membership_applications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED DEFAULT NULL,

    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    member_name VARCHAR(150) NOT NULL,

    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    address VARCHAR(255) NOT NULL,

    preferred_mode ENUM(
        'face_to_face',
        'online',
        'either'
    ) NOT NULL,

    application_reference VARCHAR(30) NOT NULL UNIQUE,

    preferred_schedule_date DATE DEFAULT NULL,
    pmes_schedule VARCHAR(100) DEFAULT NULL,

    pmes_mode ENUM(
        'face_to_face',
        'online'
    ) DEFAULT NULL,

    pmes_location VARCHAR(255) DEFAULT NULL,
    pmes_link VARCHAR(255) DEFAULT NULL,

    certificate_path VARCHAR(255) DEFAULT NULL,
    valid_id_path VARCHAR(255) DEFAULT NULL,
    proof_address_path VARCHAR(255) DEFAULT NULL,

    document_paths JSON DEFAULT NULL,

    status ENUM(
        'pending_schedule',
        'scheduled',
        'documents_submitted',
        'under_review',
        'approved',
        'rejected'
    ) NOT NULL DEFAULT 'pending_schedule',

    notes TEXT DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_membership_applications_status (status, created_at),
    INDEX idx_membership_applications_email (email),

    CONSTRAINT fk_membership_applications_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS account_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    account_type VARCHAR(120) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT UNSIGNED DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    INDEX idx_account_requests_status (status, requested_at),
    CONSTRAINT fk_account_requests_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    CONSTRAINT fk_account_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    body TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('published','archived') NOT NULL DEFAULT 'published',
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_announcements_status_date (status, published_at),
    CONSTRAINT fk_announcements_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
