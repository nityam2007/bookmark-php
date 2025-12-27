<?php
/**
 * Import API Endpoint
 * Handles file uploads and imports bookmarks
 * 
 * @package BookmarkManager\API
 */

declare(strict_types=1);

header('Content-Type: application/json');

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
use App\Helpers\Csrf;
use App\Services\ImportExportService;

// Check authentication
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Verify CSRF
if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Check for file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['file'];
$format = $_POST['format'] ?? '';

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File too large (max 5MB)']);
    exit;
}

// Determine format from extension if not specified
if (!$format) {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $format = match($ext) {
        'json' => 'json',
        'html', 'htm' => 'html',
        'csv' => 'csv',
        default => ''
    };
}

if (!in_array($format, ['json', 'html', 'csv'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unsupported file format']);
    exit;
}

// Read file content
$content = file_get_contents($file['tmp_name']);

if ($content === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to read file']);
    exit;
}

// Import
$service = new ImportExportService();
$userId = Auth::id();

try {
    $result = $service->import($content, $format, $userId);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors']
        ],
        'message' => "Imported {$result['imported']} bookmarks"
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
