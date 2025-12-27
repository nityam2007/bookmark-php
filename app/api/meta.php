<?php
/**
 * Meta Fetch API Endpoint
 * Fetch URL metadata via AJAX
 * 
 * @package BookmarkManager\API
 */

declare(strict_types=1);

// Bootstrap - just load config and autoloader (not the router)
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
    require_once APP_ROOT . '/app/config/config.php';
    require_once APP_ROOT . '/app/core/Autoloader.php';
    
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

use App\Services\MetaFetcher;
use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Core\View;

// Set JSON header
header('Content-Type: application/json');

// Require authentication
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get URL
$url = Sanitizer::url($_GET['url'] ?? '');

if (!$url) {
    View::json([
        'success' => false,
        'error'   => 'Invalid URL'
    ], 400);
}

// Fetch metadata
$fetcher = new MetaFetcher();
$meta = $fetcher->fetch($url);

View::json($meta);
