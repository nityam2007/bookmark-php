-- Migration: Add separate meta fields for bookmarks
-- Run: mysql -u root -p bookmark_manager < database/migrations/001_add_meta_fields.sql
-- 
-- This adds distinct meta_* fields that are fetched from the webpage,
-- separate from user-editable title/description

SET NAMES utf8mb4;

-- ============================================
-- ADD META FIELDS TO BOOKMARKS TABLE
-- ============================================

-- Meta title (og:title, twitter:title, or <title>)
ALTER TABLE `bookmarks` 
ADD COLUMN `meta_title` VARCHAR(512) DEFAULT NULL AFTER `description`,
ADD COLUMN `meta_description` TEXT DEFAULT NULL AFTER `meta_title`,
ADD COLUMN `meta_site_name` VARCHAR(255) DEFAULT NULL AFTER `meta_description`,
ADD COLUMN `meta_type` VARCHAR(50) DEFAULT NULL AFTER `meta_site_name`,
ADD COLUMN `meta_author` VARCHAR(255) DEFAULT NULL AFTER `meta_type`,
ADD COLUMN `meta_keywords` VARCHAR(500) DEFAULT NULL AFTER `meta_author`,
ADD COLUMN `meta_locale` VARCHAR(20) DEFAULT NULL AFTER `meta_keywords`,
ADD COLUMN `meta_twitter_card` VARCHAR(50) DEFAULT NULL AFTER `meta_locale`,
ADD COLUMN `meta_twitter_site` VARCHAR(100) DEFAULT NULL AFTER `meta_twitter_card`,
ADD COLUMN `http_status` SMALLINT UNSIGNED DEFAULT NULL AFTER `meta_twitter_site`,
ADD COLUMN `content_type` VARCHAR(100) DEFAULT NULL AFTER `http_status`,
ADD COLUMN `meta_fetch_error` VARCHAR(255) DEFAULT NULL AFTER `content_type`,
ADD COLUMN `meta_fetch_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `meta_fetch_error`;

-- Add index for meta refresh queries
ALTER TABLE `bookmarks`
ADD INDEX `idx_meta_fetch` (`meta_fetched_at`, `meta_fetch_error`);

-- Update fulltext index to include meta fields for better search
ALTER TABLE `bookmarks` 
DROP INDEX `ft_search`,
ADD FULLTEXT INDEX `ft_search` (`title`, `description`, `url`, `meta_title`, `meta_description`, `meta_keywords`);

-- ============================================
-- CREATE META IMAGES TABLE (Multiple images per bookmark)
-- ============================================
CREATE TABLE IF NOT EXISTS `bookmark_meta_images` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bookmark_id` INT UNSIGNED NOT NULL,
    `image_url` VARCHAR(2048) NOT NULL,
    `image_type` ENUM('og_image', 'twitter_image', 'favicon', 'apple_touch_icon', 'schema_image') NOT NULL DEFAULT 'og_image',
    `width` SMALLINT UNSIGNED DEFAULT NULL,
    `height` SMALLINT UNSIGNED DEFAULT NULL,
    `alt_text` VARCHAR(255) DEFAULT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_bookmark` (`bookmark_id`),
    KEY `idx_type` (`image_type`),
    KEY `idx_primary` (`bookmark_id`, `is_primary`),
    CONSTRAINT `fk_meta_image_bookmark` FOREIGN KEY (`bookmark_id`) 
        REFERENCES `bookmarks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CREATE META FETCH LOG TABLE (For debugging/analytics)
-- ============================================
CREATE TABLE IF NOT EXISTS `meta_fetch_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bookmark_id` INT UNSIGNED NOT NULL,
    `url` VARCHAR(2048) NOT NULL,
    `http_status` SMALLINT UNSIGNED DEFAULT NULL,
    `fetch_time_ms` INT UNSIGNED DEFAULT NULL,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `error_message` VARCHAR(500) DEFAULT NULL,
    `fetched_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_bookmark` (`bookmark_id`),
    KEY `idx_fetched` (`fetched_at`),
    CONSTRAINT `fk_fetch_log_bookmark` FOREIGN KEY (`bookmark_id`) 
        REFERENCES `bookmarks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SHOW SUCCESS MESSAGE
-- ============================================
SELECT 'Migration completed successfully!' as status;
