-- FitForFaith Fundraiser Platform — Database Schema
-- Run this once via install.php or manually via phpMyAdmin

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ============================================================
-- Churches
-- ============================================================
CREATE TABLE IF NOT EXISTS churches (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    slug        VARCHAR(120) NOT NULL,
    logo_url    VARCHAR(255) DEFAULT NULL,
    city        VARCHAR(80)  DEFAULT NULL,
    description TEXT         DEFAULT NULL,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    church_id            INT UNSIGNED DEFAULT NULL,
    first_name           VARCHAR(60)  NOT NULL,
    last_name            VARCHAR(60)  NOT NULL,
    email                VARCHAR(180) NOT NULL,
    password_hash        VARCHAR(255) NOT NULL,
    role                 ENUM('user','admin') NOT NULL DEFAULT 'user',
    phone                VARCHAR(20)  DEFAULT NULL,
    is_active            TINYINT(1)   NOT NULL DEFAULT 1,
    is_paid              TINYINT(1)   NOT NULL DEFAULT 0,
    email_verified_at    DATETIME     DEFAULT NULL,
    email_token          VARCHAR(64)  DEFAULT NULL,
    pw_reset_token       VARCHAR(64)  DEFAULT NULL,
    pw_reset_expires     DATETIME     DEFAULT NULL,
    registration_ref     VARCHAR(64)  DEFAULT NULL,
    strava_athlete_id    BIGINT UNSIGNED DEFAULT NULL,
    strava_access_token  VARCHAR(500) DEFAULT NULL,
    strava_refresh_token VARCHAR(500) DEFAULT NULL,
    strava_token_expires INT UNSIGNED DEFAULT NULL,
    strava_connected_at  DATETIME     DEFAULT NULL,
    fitness_platform     VARCHAR(30)  DEFAULT NULL,
    language             VARCHAR(5)   NOT NULL DEFAULT 'en',
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at           DATETIME     DEFAULT NULL,
    UNIQUE KEY uk_email (email),
    UNIQUE KEY uk_strava (strava_athlete_id),
    FOREIGN KEY fk_user_church (church_id) REFERENCES churches(id) ON DELETE SET NULL,
    INDEX idx_church (church_id),
    INDEX idx_paid (is_paid),
    INDEX idx_active (is_active, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Strava Activities
-- ============================================================
CREATE TABLE IF NOT EXISTS strava_activities (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    church_id       INT UNSIGNED DEFAULT NULL,
    strava_id       BIGINT UNSIGNED NOT NULL,
    activity_type   VARCHAR(40)  NOT NULL,
    name            VARCHAR(255) DEFAULT NULL,
    distance_meters FLOAT        NOT NULL DEFAULT 0,
    moving_time_sec INT UNSIGNED NOT NULL DEFAULT 0,
    start_date      DATETIME     NOT NULL,
    points_awarded  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_flagged      TINYINT(1)   NOT NULL DEFAULT 0,
    raw_payload     MEDIUMTEXT   DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_strava_id (strava_id),
    FOREIGN KEY fk_activity_user (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY fk_activity_church (church_id) REFERENCES churches(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_church (church_id),
    INDEX idx_start (start_date),
    INDEX idx_type (activity_type),
    INDEX idx_flagged (is_flagged)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Points Cache (rebuilt by cron every 5 minutes)
-- ============================================================
CREATE TABLE IF NOT EXISTS points_cache (
    user_id         INT UNSIGNED NOT NULL PRIMARY KEY,
    total_points    INT UNSIGNED NOT NULL DEFAULT 0,
    run_points      INT UNSIGNED NOT NULL DEFAULT 0,
    walk_points     INT UNSIGNED NOT NULL DEFAULT 0,
    ride_points     INT UNSIGNED NOT NULL DEFAULT 0,
    activity_count  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    rank_individual INT UNSIGNED DEFAULT NULL,
    last_rebuilt    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY fk_cache_user (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_total (total_points),
    INDEX idx_rank  (rank_individual)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Church Points Cache
-- ============================================================
CREATE TABLE IF NOT EXISTS church_points_cache (
    church_id       INT UNSIGNED NOT NULL PRIMARY KEY,
    total_points    INT UNSIGNED NOT NULL DEFAULT 0,
    member_count    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    avg_points      FLOAT        NOT NULL DEFAULT 0,
    church_rank     SMALLINT UNSIGNED DEFAULT NULL,
    last_rebuilt    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY fk_ccache_church (church_id) REFERENCES churches(id) ON DELETE CASCADE,
    INDEX idx_total (total_points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Donations / Registration Payments
-- ============================================================
CREATE TABLE IF NOT EXISTS donations (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED DEFAULT NULL,
    church_id       INT UNSIGNED DEFAULT NULL,
    amount_cents    INT UNSIGNED NOT NULL,
    currency        CHAR(3)      NOT NULL DEFAULT 'ZAR',
    pf_payment_id   VARCHAR(40)  DEFAULT NULL,
    pf_item_name    VARCHAR(100) DEFAULT NULL,
    status          ENUM('pending','complete','cancelled','failed') NOT NULL DEFAULT 'pending',
    itn_verified    TINYINT(1)   NOT NULL DEFAULT 0,
    itn_received_at DATETIME     DEFAULT NULL,
    itn_payload     TEXT         DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_pf_payment (pf_payment_id),
    FOREIGN KEY fk_donation_user   (user_id)   REFERENCES users(id)    ON DELETE SET NULL,
    FOREIGN KEY fk_donation_church (church_id) REFERENCES churches(id) ON DELETE SET NULL,
    INDEX idx_user   (user_id),
    INDEX idx_status (status),
    INDEX idx_pf     (pf_payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Strava Webhook Event Queue
-- ============================================================
CREATE TABLE IF NOT EXISTS strava_webhook_events (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type    VARCHAR(40)  NOT NULL,
    aspect_type   VARCHAR(20)  NOT NULL,
    object_id     BIGINT UNSIGNED NOT NULL,
    owner_id      BIGINT UNSIGNED NOT NULL,
    payload       TEXT         NOT NULL,
    status        ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
    attempts      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    error_message TEXT         DEFAULT NULL,
    received_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at  DATETIME     DEFAULT NULL,
    INDEX idx_status (status, received_at),
    INDEX idx_owner  (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- QR Codes Registry
-- ============================================================
CREATE TABLE IF NOT EXISTS qr_codes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token       VARCHAR(32)  NOT NULL,
    label       VARCHAR(120) DEFAULT NULL,
    church_id   INT UNSIGNED DEFAULT NULL,
    scans       INT UNSIGNED NOT NULL DEFAULT 0,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_by  INT UNSIGNED DEFAULT NULL,
    expires_at  DATETIME     DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_token (token),
    FOREIGN KEY fk_qr_church  (church_id)  REFERENCES churches(id) ON DELETE SET NULL,
    FOREIGN KEY fk_qr_creator (created_by) REFERENCES users(id)    ON DELETE SET NULL,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- App Settings (key-value)
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    `key`      VARCHAR(80) NOT NULL PRIMARY KEY,
    `value`    TEXT        DEFAULT NULL,
    updated_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
('points_per_km_run',   '10'),
('points_per_km_walk',  '5'),
('points_per_km_ride',  '3'),
('max_points_per_day',  '200'),
('event_start_date',    '2026-01-01'),
('event_end_date',      '2026-12-31'),
('registration_open',   '1'),
('registration_fee',    '15000'),
('site_name',           'FitForFaith'),
('site_tagline',        'Move Together. Raise Together.');

-- ============================================================
-- Platform OAuth Tokens (Fitbit, Garmin, Polar, Wahoo, Suunto)
-- ============================================================
CREATE TABLE IF NOT EXISTS user_platform_tokens (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL,
    platform         VARCHAR(50)  NOT NULL,
    access_token     VARCHAR(2000) NOT NULL,
    refresh_token    VARCHAR(2000) DEFAULT NULL,
    token_expires    INT UNSIGNED  DEFAULT NULL,
    platform_user_id VARCHAR(150)  DEFAULT NULL,
    connected_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_platform (user_id, platform),
    FOREIGN KEY fk_pt_user (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_platform_user (platform, platform_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Platform Activities (all non-Strava synced activities)
-- ============================================================
CREATE TABLE IF NOT EXISTS platform_activities (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    church_id       INT UNSIGNED DEFAULT NULL,
    platform        VARCHAR(50)  NOT NULL,
    external_id     VARCHAR(150) NOT NULL,
    activity_type   ENUM('Run','Walk','Ride') NOT NULL,
    name            VARCHAR(200) DEFAULT NULL,
    distance_meters FLOAT        NOT NULL DEFAULT 0,
    moving_time_sec INT UNSIGNED NOT NULL DEFAULT 0,
    start_date      DATETIME     NOT NULL,
    points_awarded  INT UNSIGNED NOT NULL DEFAULT 0,
    is_flagged      TINYINT(1)   NOT NULL DEFAULT 0,
    raw_payload     MEDIUMTEXT   DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_platform_ext (platform, external_id),
    FOREIGN KEY fk_pa_user   (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY fk_pa_church (church_id) REFERENCES churches(id) ON DELETE SET NULL,
    INDEX idx_pa_user    (user_id),
    INDEX idx_pa_start   (start_date),
    INDEX idx_pa_flagged (is_flagged)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Platform Webhook / Backfill Queue
-- ============================================================
CREATE TABLE IF NOT EXISTS platform_webhook_queue (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    platform     VARCHAR(50)  NOT NULL,
    platform_uid VARCHAR(150) DEFAULT NULL,
    external_id  VARCHAR(150) DEFAULT NULL,
    event_type   VARCHAR(50)  NOT NULL DEFAULT 'create',
    payload      MEDIUMTEXT   DEFAULT NULL,
    status       ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
    attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    error_msg    VARCHAR(500) DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME     DEFAULT NULL,
    INDEX idx_pwq_status  (status, created_at),
    INDEX idx_pwq_platform (platform, platform_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Manual Activities (non-Strava platform submissions)
-- ============================================================
CREATE TABLE IF NOT EXISTS manual_activities (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    church_id       INT UNSIGNED DEFAULT NULL,
    activity_type   ENUM('Run','Walk','Ride') NOT NULL,
    platform        VARCHAR(50)  NOT NULL DEFAULT 'other',
    name            VARCHAR(200) DEFAULT NULL,
    distance_meters FLOAT        NOT NULL DEFAULT 0,
    moving_time_sec INT UNSIGNED NOT NULL DEFAULT 0,
    start_date      DATE         NOT NULL,
    notes           TEXT         DEFAULT NULL,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by     INT UNSIGNED DEFAULT NULL,
    reviewed_at     DATETIME     DEFAULT NULL,
    reject_reason   VARCHAR(255) DEFAULT NULL,
    points_awarded  INT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user   (user_id),
    INDEX idx_status (status),
    INDEX idx_date   (start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ============================================================
-- Migrations (safe to re-run — ADD COLUMN IF NOT EXISTS)
-- ============================================================
ALTER TABLE users ADD COLUMN language VARCHAR(5) NOT NULL DEFAULT 'en';
