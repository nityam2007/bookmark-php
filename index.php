<?php
/**
 * Bookmark Manager - Root Entry Point
 * 
 * For shared hosting where the app is installed in the web root (public_html)
 * This file forwards all requests to public/index.php
 * 
 * INSTALLATION OPTIONS:
 * 
 * Option 1 (Recommended): Point your domain to the /public folder
 *   - Set document root to: /home/user/bookmark-manager/public
 *   - This file is not needed
 * 
 * Option 2: Install in public_html directly
 *   - Upload all files to public_html
 *   - This index.php handles routing to /public
 *   - Make sure .htaccess is properly configured
 */

// Check if install.php exists and config doesn't - redirect to installer
if (file_exists(__DIR__ . '/install.php') && !file_exists(__DIR__ . '/app/config/config.json')) {
    header('Location: install.php');
    exit;
}

// Set working directory to public folder
chdir(__DIR__ . '/public');

// Remove /public from REQUEST_URI if present (for proper routing)
if (isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = preg_replace('#^/public#', '', $_SERVER['REQUEST_URI']);
    if (empty($_SERVER['REQUEST_URI'])) {
        $_SERVER['REQUEST_URI'] = '/';
    }
}

// Forward to public/index.php
require __DIR__ . '/public/index.php';
