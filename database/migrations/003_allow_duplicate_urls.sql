-- Migration: Remove UNIQUE constraint on url_hash
-- Allows same URL in different categories
-- Run: mysql -u root -p bookmark_manager < database/migrations/003_allow_duplicate_urls.sql

SET NAMES utf8mb4;

-- Drop the unique index and create a regular index
-- This allows the same URL to exist in multiple categories
ALTER TABLE `bookmarks` DROP INDEX `idx_url_hash`;
ALTER TABLE `bookmarks` ADD INDEX `idx_url_hash` (`url_hash`);
