<?php
/**
 * Application Entry Point / Front Controller
 * Routes all requests through the application
 * 
 * @package BookmarkManager
 */

declare(strict_types=1);

// Define root path
define('APP_ROOT', dirname(__DIR__));

// Load configuration
require_once APP_ROOT . '/app/config/config.php';

// Load autoloader
require_once APP_ROOT . '/app/core/Autoloader.php';

// Start session (only if not already started)
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    session_start();
}

use App\Core\Router;
use App\Core\View;
use App\Controllers\AuthController;
use App\Controllers\BookmarkController;
use App\Controllers\CategoryController;
use App\Controllers\ImportExportController;
use App\Controllers\SettingsController;

// Initialize router
$router = new Router();

// ============================================
// AUTH ROUTES
// ============================================
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// ============================================
// DASHBOARD
// ============================================
$router->get('/', [AuthController::class, 'dashboard']);
$router->get('/dashboard', [AuthController::class, 'dashboard']);

// ============================================
// BOOKMARK ROUTES
// ============================================
$router->get('/bookmarks', [BookmarkController::class, 'index']);
$router->get('/bookmarks/create', [BookmarkController::class, 'create']);
$router->post('/bookmarks', [BookmarkController::class, 'store']);
$router->get('/bookmarks/{id}/edit', [BookmarkController::class, 'edit']);
$router->get('/bookmarks/{id}/visit', [BookmarkController::class, 'visit']);
$router->post('/bookmarks/{id}/delete', [BookmarkController::class, 'destroy']);
$router->post('/bookmarks/{id}/favorite', [BookmarkController::class, 'toggleFavorite']);
$router->get('/bookmarks/{id}', [BookmarkController::class, 'show']);
$router->post('/bookmarks/{id}', [BookmarkController::class, 'update']);

// ============================================
// CATEGORY ROUTES
// ============================================
$router->get('/categories', [CategoryController::class, 'index']);
$router->get('/categories/create', [CategoryController::class, 'create']);
$router->post('/categories', [CategoryController::class, 'store']);
$router->get('/categories/{id}', [CategoryController::class, 'show']);
$router->get('/categories/{id}/edit', [CategoryController::class, 'edit']);
$router->post('/categories/{id}', [CategoryController::class, 'update']);
$router->post('/categories/{id}/delete', [CategoryController::class, 'destroy']);
$router->get('/categories/tree', [CategoryController::class, 'tree']);
$router->post('/categories/{id}/move', [CategoryController::class, 'move']);

// ============================================
// IMPORT/EXPORT ROUTES
// ============================================
$router->get('/import', [ImportExportController::class, 'showImport']);
$router->post('/import', [ImportExportController::class, 'import']);
$router->get('/export', [ImportExportController::class, 'export']);

// ============================================
// SEARCH PAGE
// ============================================
$router->get('/search', [BookmarkController::class, 'search']);

// ============================================
// SETTINGS ROUTES
// ============================================
$router->get('/settings', [SettingsController::class, 'index']);
$router->post('/settings/profile', [SettingsController::class, 'updateProfile']);
$router->post('/settings/password', [SettingsController::class, 'updatePassword']);
$router->post('/settings/delete-account', [SettingsController::class, 'deleteAccount']);

// ============================================
// API ROUTES (Handled directly by files in /api/)
// ============================================

// ============================================
// DISPATCH REQUEST
// ============================================
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Handle API routes
if (str_starts_with($uri, '/api/')) {
    // Extract API file name - remove /api/ prefix and query string
    $apiPath = parse_url($uri, PHP_URL_PATH);
    $apiPath = substr($apiPath, 5); // Remove '/api/'
    
    // Security: only allow alphanumeric and hyphens
    if (preg_match('/^[a-zA-Z0-9\-]+$/', $apiPath)) {
        $apiFile = APP_ROOT . '/app/api/' . $apiPath . '.php';
        
        if (file_exists($apiFile)) {
            require $apiFile;
            exit;
        }
    }
    
    // API not found
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// Dispatch to router
try {
    echo $router->dispatch($method, $uri);
} catch (\Exception $e) {
    if (APP_DEBUG) {
        echo '<h1>Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo '<h1>Server Error</h1><p>Something went wrong.</p>';
    }
}
