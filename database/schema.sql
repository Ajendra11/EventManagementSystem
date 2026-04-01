-- ============================================================
-- EventHub Database Schema — Sprint 1
-- PHP 8.1+ | MySQL 8.0+ | InnoDB
-- Run: mysql -u root -p < database/schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS eventhub_php_v1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eventhub_php_v1;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS payment_logs;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS email_verifications;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name             VARCHAR(100) NOT NULL,
    email                 VARCHAR(190) NOT NULL UNIQUE,
    password_hash         VARCHAR(255) NOT NULL,
    role                  ENUM('admin','participant') NOT NULL DEFAULT 'participant',
    status                ENUM('active','blocked') NOT NULL DEFAULT 'active',
    email_verified_at     DATETIME NULL DEFAULT NULL,
    failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until          DATETIME NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_email  (email),
    INDEX idx_users_role   (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

CREATE TABLE email_verifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    token      VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ev_token (token)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(190) NOT NULL,
    token      VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at    DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pr_email (email),
    INDEX idx_pr_token (token)
) ENGINE=InnoDB;

CREATE TABLE events (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(150) NOT NULL,
    slug         VARCHAR(180) NOT NULL UNIQUE,
    category     VARCHAR(80)  NOT NULL,
    location     VARCHAR(150) NOT NULL,
    description  TEXT         NOT NULL,
    start_date   DATE         NOT NULL,
    start_time   TIME         NOT NULL,
    capacity     INT UNSIGNED NOT NULL,
    price        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    banner_image VARCHAR(255) NULL,
    status       ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    created_by   INT UNSIGNED NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_events_status_date (status, start_date, start_time),
    INDEX idx_events_category    (category),
    INDEX idx_events_location    (location(50))
) ENGINE=InnoDB;

CREATE TABLE bookings (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NOT NULL,
    event_id       INT UNSIGNED NOT NULL,
    quantity       INT UNSIGNED NOT NULL DEFAULT 1,
    amount         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status         ENUM('Pending','Confirmed','Cancelled') NOT NULL DEFAULT 'Pending',
    khalti_pidx    VARCHAR(255) NULL UNIQUE,
    khalti_ref_id  VARCHAR(255) NULL,
    qr_token       VARCHAR(64)  NULL UNIQUE,
    qr_image_path  VARCHAR(255) NULL,
    checked_in_at  DATETIME NULL,
    checked_in_by  INT UNSIGNED NULL,
    booking_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_at   DATETIME NULL,
    CONSTRAINT fk_bookings_user    FOREIGN KEY (user_id)       REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_bookings_event   FOREIGN KEY (event_id)      REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_checkin FOREIGN KEY (checked_in_by) REFERENCES users(id)  ON DELETE SET NULL,
    UNIQUE KEY uniq_user_event (user_id, event_id),
    INDEX idx_bookings_user   (user_id),
    INDEX idx_bookings_event  (event_id),
    INDEX idx_bookings_status (status)
) ENGINE=InnoDB;

CREATE TABLE reviews (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    event_id   INT UNSIGNED NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    TEXT NULL,
    status     ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_reviews_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_event_review (user_id, event_id),
    INDEX idx_reviews_event  (event_id),
    INDEX idx_reviews_status (status)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT UNSIGNED NULL,
    action      VARCHAR(100) NOT NULL,
    target_type VARCHAR(50)  NULL,
    target_id   INT UNSIGNED NULL,
    details     TEXT NULL,
    ip_address  VARCHAR(45)  NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_admin  (admin_id),
    INDEX idx_audit_action (action)
) ENGINE=InnoDB;

INSERT INTO users (full_name, email, password_hash, role, status, email_verified_at) VALUES
('System Admin', 'admin@eventhub.local', '$2y$12$tfy95b4Xto48bcFJ4/dZf.brqgi3tn3.RpemEwJBbs2XGUsHlZpMu', 'admin', 'active', NOW()),
('Demo Participant', 'participant@eventhub.local', '$2y$12$hYup6LLERoseyne83IVXZu/ot/rMXzsTMJY9qkPpESWLaPKTzkUzu', 'participant', 'active', NOW());