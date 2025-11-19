CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(64) UNIQUE,
    email VARCHAR(120),
    name VARCHAR(120),
    avatar_url VARCHAR(255) NULL,
    country VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    address VARCHAR(255) NULL,
    postal_code VARCHAR(20) NULL,
    phone VARCHAR(50) NULL,
    company VARCHAR(150) NULL,
    website VARCHAR(255) NULL,
    bio TEXT NULL,
    locale VARCHAR(10) NULL,
    role ENUM('USER','ADMIN') DEFAULT 'USER',
    plan ENUM('FREE','PREMIUM','ENTERPRISE') DEFAULT 'FREE',
    lifetime_ops INT DEFAULT 0,
    stripe_customer_id VARCHAR(120) NULL,
    stripe_subscription_id VARCHAR(120) NULL,
    cancel_at_period_end TINYINT(1) DEFAULT 0,
    cancel_at DATETIME NULL,
    current_period_end DATETIME NULL,
    plan_expiration DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    file_size INT,
    status ENUM('SUCCESS','FAILED') NOT NULL,
    operation_type ENUM('bg_removal', 'transform') DEFAULT 'bg_removal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_operations_user_date ON operations (user_id, date);
CREATE INDEX idx_operations_user_status ON operations (user_id, status);
CREATE UNIQUE INDEX idx_users_stripe_customer_id ON users (stripe_customer_id);

-- URL Shortener Tables

CREATE TABLE IF NOT EXISTS short_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    original_url VARCHAR(2048) NOT NULL,
    short_code VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    is_active TINYINT(1) DEFAULT 1,
    has_preview_page TINYINT(1) DEFAULT 0,
    qr_code_path VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_short_code (short_code),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    short_link_id INT NOT NULL,
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    device_type VARCHAR(50) NULL,
    country VARCHAR(100) NULL,
    referrer VARCHAR(500) NULL,
    FOREIGN KEY (short_link_id) REFERENCES short_links(id) ON DELETE CASCADE,
    INDEX idx_short_link_id (short_link_id),
    INDEX idx_clicked_at (clicked_at),
    INDEX idx_ip_address (ip_address),
    INDEX idx_device_type (device_type),
    INDEX idx_country (country)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quota_daily (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    links_created INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quota_monthly (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    `year_month` INT NOT NULL,
    links_created INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month (user_id, `year_month`),
    INDEX idx_year_month (`year_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    google_id VARCHAR(64) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    logged_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    country VARCHAR(100) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_google_id (google_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_logged_in_at (logged_in_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

