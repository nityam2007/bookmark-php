-- Bookmark Manager Database Schema
-- MySQL 8+ / MariaDB 10.5+
-- Optimized for fast search and low-resource environments

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- CATEGORIES TABLE (Adjacency List Model)
-- ============================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `color` VARCHAR(20) DEFAULT NULL,
    `level` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_slug` (`slug`),
    KEY `idx_parent` (`parent_id`),
    KEY `idx_level_sort` (`level`, `sort_order`),
    CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) 
        REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAGS TABLE (Normalized)
-- ============================================
CREATE TABLE IF NOT EXISTS `tags` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(60) NOT NULL,
    `usage_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_slug` (`slug`),
    KEY `idx_usage` (`usage_count` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BOOKMARKS TABLE (URL as unique key)
-- ============================================
CREATE TABLE IF NOT EXISTS `bookmarks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `url` VARCHAR(2048) NOT NULL,
    `url_hash` CHAR(64) NOT NULL COMMENT 'SHA256 hash for unique constraint',
    `title` VARCHAR(255) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `meta_image` VARCHAR(512) DEFAULT NULL,
    `favicon` VARCHAR(512) DEFAULT NULL,
    `category_id` INT UNSIGNED DEFAULT NULL,
    `is_favorite` TINYINT(1) NOT NULL DEFAULT 0,
    `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
    `visit_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_visited_at` TIMESTAMP NULL DEFAULT NULL,
    `meta_fetched_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_url_hash` (`url_hash`),
    KEY `idx_category` (`category_id`),
    KEY `idx_favorite` (`is_favorite`),
    KEY `idx_archived` (`is_archived`),
    KEY `idx_created` (`created_at` DESC),
    KEY `idx_visited` (`last_visited_at` DESC),
    CONSTRAINT `fk_bookmark_category` FOREIGN KEY (`category_id`) 
        REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FULLTEXT INDEX FOR FAST SEARCH
-- Must include ALL columns used in MATCH() queries
-- ============================================
ALTER TABLE `bookmarks` 
    ADD FULLTEXT INDEX `ft_search` (`title`, `description`, `url`, `meta_title`, `meta_description`, `meta_keywords`);

-- ============================================
-- BOOKMARK_TAGS PIVOT TABLE (Many-to-Many)
-- ============================================
CREATE TABLE IF NOT EXISTS `bookmark_tags` (
    `bookmark_id` INT UNSIGNED NOT NULL,
    `tag_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`bookmark_id`, `tag_id`),
    KEY `idx_tag` (`tag_id`),
    CONSTRAINT `fk_bt_bookmark` FOREIGN KEY (`bookmark_id`) 
        REFERENCES `bookmarks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bt_tag` FOREIGN KEY (`tag_id`) 
        REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USERS TABLE (Simple Auth)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_username` (`username`),
    UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SESSIONS TABLE (Secure Session Storage)
-- ============================================
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` CHAR(64) NOT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `data` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_activity` (`last_activity`),
    CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEARCH CACHE TABLE (JSON Cache)
-- ============================================
CREATE TABLE IF NOT EXISTS `search_cache` (
    `cache_key` CHAR(64) NOT NULL,
    `query` VARCHAR(255) NOT NULL,
    `results` JSON NOT NULL,
    `result_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    PRIMARY KEY (`cache_key`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- GDPR CONSENT LOG
-- ============================================
CREATE TABLE IF NOT EXISTS `gdpr_consent` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `consent_type` VARCHAR(50) NOT NULL,
    `granted` TINYINT(1) NOT NULL DEFAULT 0,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_type` (`user_id`, `consent_type`),
    CONSTRAINT `fk_consent_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DEFAULT ADMIN USER
-- ⚠️  SECURITY WARNING: Change password IMMEDIATELY after first login!
-- Default: admin / admin123
-- Generate new hash: php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT, ['cost' => 12]);"
-- ============================================
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@localhost', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewKyDAXrP5tHne2e', 'admin');

-- ============================================
-- DEFAULT ROOT CATEGORY
-- ============================================
INSERT INTO `categories` (`name`, `slug`, `description`, `level`) VALUES
('Uncategorized', 'uncategorized', 'Default category for bookmarks', 0);

SET FOREIGN_KEY_CHECKS = 1;
