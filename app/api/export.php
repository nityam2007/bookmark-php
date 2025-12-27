<?php
/**
 * Export API Endpoint
 * Exports bookmarks in various formats
 * 
 * @package BookmarkManager\API
 */

declare(strict_types=1);

// Bootstrap - just load config and autoloader
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
    require_once APP_ROOT . '/app/config/config.php';
    require_once APP_ROOT . '/app/core/Autoloader.php';
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

use App\Helpers\Auth;
use App\Services\ImportExportService;

// Check authentication
if (!Auth::check()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$format = $_GET['format'] ?? 'json';
$userId = Auth::id();

if (!in_array($format, ['json', 'html', 'csv'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unsupported format']);
    exit;
}

$service = new ImportExportService();

try {
    $data = $service->export($format, $userId);
    
    // Set appropriate headers for download
    $filename = 'bookmarks_' . date('Y-m-d') . '.' . $format;
    
    switch ($format) {
        case 'json':
            header('Content-Type: application/json');
            break;
        case 'html':
            header('Content-Type: text/html; charset=utf-8');
            break;
        case 'csv':
            header('Content-Type: text/csv; charset=utf-8');
            break;
    }
    
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($data));
    header('Cache-Control: no-cache, must-revalidate');
    
    echo $data;
} catch (\Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
