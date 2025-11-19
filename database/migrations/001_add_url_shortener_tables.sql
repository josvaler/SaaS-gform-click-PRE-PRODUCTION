-- Migration: Add URL Shortener Tables and Update Users Table
-- Date: 2024

-- Update users table: Add ENTERPRISE plan, profile fields, role, plan_expiration
ALTER TABLE users 
    MODIFY plan ENUM('FREE','PREMIUM','ENTERPRISE') DEFAULT 'FREE',
    ADD COLUMN avatar_url VARCHAR(255) NULL AFTER name,
    ADD COLUMN country VARCHAR(100) NULL AFTER avatar_url,
    ADD COLUMN city VARCHAR(100) NULL AFTER country,
    ADD COLUMN address VARCHAR(255) NULL AFTER city,
    ADD COLUMN postal_code VARCHAR(20) NULL AFTER address,
    ADD COLUMN phone VARCHAR(50) NULL AFTER postal_code,
    ADD COLUMN company VARCHAR(150) NULL AFTER phone,
    ADD COLUMN website VARCHAR(255) NULL AFTER company,
    ADD COLUMN bio TEXT NULL AFTER website,
    ADD COLUMN locale VARCHAR(10) NULL AFTER bio,
    ADD COLUMN role ENUM('USER','ADMIN') DEFAULT 'USER' AFTER locale,
    ADD COLUMN plan_expiration DATETIME NULL AFTER current_period_end;

-- Create short_links table
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

-- Create clicks table
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

-- Create quota_daily table
CREATE TABLE IF NOT EXISTS quota_daily (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    links_created INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create quota_monthly table
CREATE TABLE IF NOT EXISTS quota_monthly (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    year_month INT NOT NULL COMMENT 'Format: YYYYMM',
    links_created INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month (user_id, year_month),
    INDEX idx_year_month (year_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user_login_logs table for IP tracking
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

-- Set default role to USER for existing users
UPDATE users SET role = 'USER' WHERE role IS NULL;

