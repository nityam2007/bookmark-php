-- API Keys Table for External Access
-- Migration: 002_add_api_keys.sql

CREATE TABLE IF NOT EXISTS `api_keys` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT 'User-friendly name for the API key',
    `key_hash` CHAR(64) NOT NULL COMMENT 'SHA256 hash of the API key',
    `key_prefix` CHAR(8) NOT NULL COMMENT 'First 8 chars for identification',
    `permissions` JSON DEFAULT NULL COMMENT 'Optional: specific permissions',
    `last_used_at` TIMESTAMP NULL DEFAULT NULL,
    `expires_at` TIMESTAMP NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_key_hash` (`key_hash`),
    KEY `idx_user` (`user_id`),
    KEY `idx_prefix` (`key_prefix`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_apikey_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
