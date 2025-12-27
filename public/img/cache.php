<?php
/**
 * Image Serve Script
 * Serves cached images from the cache/images directory
 * 
 * URL: /img/cache/{filename}
 * 
 * @package BookmarkManager
 */

declare(strict_types=1);

// Security: Only allow specific file extensions
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];

// Get requested file
$file = $_GET['file'] ?? '';

// Validate filename (prevent path traversal)
if (empty($file) || preg_match('/[\/\\\.]\./', $file) || str_contains($file, '..')) {
    http_response_code(400);
    exit('Invalid request');
}

// Get extension
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExtensions)) {
    http_response_code(403);
    exit('Forbidden');
}

// Build full path
// Go up two directories from /public/img to reach project root, then into cache/images
$cacheDir = dirname(__DIR__, 2) . '/cache/images';
$fullPath = $cacheDir . '/' . $file;

// Check file exists
if (!file_exists($fullPath) || !is_file($fullPath)) {
    http_response_code(404);
    exit('Not found');
}

// Security: Ensure file is within cache directory
$realCacheDir = realpath($cacheDir);
$realPath = realpath($fullPath);
if ($realPath === false || !str_starts_with($realPath, $realCacheDir)) {
    http_response_code(403);
    exit('Forbidden');
}

// Get mime type
$mimeTypes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
];
$mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';

// Set caching headers
$maxAge = 604800; // 7 days
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=' . $maxAge);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($fullPath)) . ' GMT');

// Handle conditional GET (304 Not Modified)
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($ifModifiedSince >= filemtime($fullPath)) {
        http_response_code(304);
        exit;
    }
}

// Output file
readfile($fullPath);
exit;
