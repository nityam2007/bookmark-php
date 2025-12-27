<?php
/**
 * Main Configuration File
 * Loads environment-specific settings and defines constants
 * 
 * @package BookmarkManager
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

// Load JSON config if exists (faster parsing)
$jsonConfigPath = __DIR__ . '/config.json';
$jsonConfig = [];

if (file_exists($jsonConfigPath)) {
    $jsonConfig = json_decode(file_get_contents($jsonConfigPath), true) ?? [];
}

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', $jsonConfig['database']['host'] ?? 'localhost');
define('DB_NAME', $jsonConfig['database']['name'] ?? 'bookmark_manager');
define('DB_USER', $jsonConfig['database']['user'] ?? 'root');
define('DB_PASS', $jsonConfig['database']['pass'] ?? '');
define('DB_CHARSET', $jsonConfig['database']['charset'] ?? 'utf8mb4');
define('DB_PORT', $jsonConfig['database']['port'] ?? 3306);

// ============================================
// APPLICATION SETTINGS
// ============================================
define('APP_NAME', $jsonConfig['app']['name'] ?? 'Bookmark Manager');
define('APP_URL', $jsonConfig['app']['url'] ?? 'http://localhost');
define('APP_DEBUG', $jsonConfig['app']['debug'] ?? false);
define('APP_TIMEZONE', $jsonConfig['app']['timezone'] ?? 'UTC');
define('APP_VERSION', '1.1.0');

// ============================================
// SECURITY SETTINGS
// ============================================
define('SESSION_LIFETIME', $jsonConfig['security']['session_lifetime'] ?? 3600);
define('CSRF_TOKEN_LIFETIME', $jsonConfig['security']['csrf_lifetime'] ?? 1800);
define('PASSWORD_MIN_LENGTH', $jsonConfig['security']['password_min_length'] ?? 8);
define('MAX_LOGIN_ATTEMPTS', $jsonConfig['security']['max_login_attempts'] ?? 5);
define('LOCKOUT_TIME', $jsonConfig['security']['lockout_time'] ?? 900);

// Secret key for CSRF and other security features
define('APP_SECRET', $jsonConfig['security']['secret'] ?? 'CHANGE_THIS_SECRET_KEY_IN_PRODUCTION');

// ============================================
// SEARCH & CACHE SETTINGS
// ============================================
define('SEARCH_CACHE_TTL', $jsonConfig['search']['cache_ttl'] ?? 300);
define('SEARCH_MIN_LENGTH', $jsonConfig['search']['min_length'] ?? 2);
define('SEARCH_MAX_RESULTS', $jsonConfig['search']['max_results'] ?? 100);
define('SEARCH_DEBOUNCE_MS', $jsonConfig['search']['debounce_ms'] ?? 300);

// ============================================
// PAGINATION SETTINGS
// ============================================
define('ITEMS_PER_PAGE', $jsonConfig['pagination']['per_page'] ?? 24);
define('MAX_PAGES_SHOWN', $jsonConfig['pagination']['max_pages'] ?? 5);

// ============================================
// META FETCHER SETTINGS
// ============================================
define('META_FETCH_TIMEOUT', $jsonConfig['meta']['timeout'] ?? 10);
define('META_USER_AGENT', $jsonConfig['meta']['user_agent'] ?? 'BookmarkManager/1.0');
define('META_MAX_RETRIES', $jsonConfig['meta']['max_retries'] ?? 3);

// ============================================
// IMPORT/EXPORT SETTINGS
// ============================================
define('IMPORT_MAX_SIZE', $jsonConfig['import']['max_size'] ?? 10485760); // 10MB
define('EXPORT_CHUNK_SIZE', $jsonConfig['export']['chunk_size'] ?? 500);

// ============================================
// CATEGORY SETTINGS
// ============================================
define('CATEGORY_MAX_DEPTH', $jsonConfig['categories']['max_depth'] ?? 10);

// ============================================
// PATH CONSTANTS
// ============================================
define('CONFIG_PATH', __DIR__);
define('CONTROLLERS_PATH', APP_ROOT . '/app/controllers');
define('MODELS_PATH', APP_ROOT . '/app/models');
define('VIEWS_PATH', APP_ROOT . '/app/views');
define('SERVICES_PATH', APP_ROOT . '/app/services');
define('HELPERS_PATH', APP_ROOT . '/app/helpers');
define('API_PATH', APP_ROOT . '/app/api');
define('CACHE_PATH', APP_ROOT . '/cache');
define('LOGS_PATH', APP_ROOT . '/logs');
define('UPLOADS_PATH', APP_ROOT . '/public/uploads');

// ============================================
// ERROR HANDLING
// ============================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOGS_PATH . '/error.log');
}

// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set(APP_TIMEZONE);

// ============================================
// SESSION CONFIGURATION
// ============================================
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', (string)SESSION_LIFETIME);
ini_set('session.use_strict_mode', '1');
