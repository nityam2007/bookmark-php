<?php
/**
 * Search API Endpoint
 * Fast JSON search with caching
 * 
 * @package BookmarkManager\API
 */

declare(strict_types=1);

// Set JSON header FIRST to prevent any output before it
header('Content-Type: application/json');

// Suppress errors for output (we'll handle them gracefully)
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Services\SearchService;
use App\Helpers\Auth;
use App\Helpers\Sanitizer;

// Require authentication
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get query
$query = Sanitizer::string($_GET['q'] ?? '', 255);
$page = Sanitizer::int($_GET['page'] ?? '1', 1, 1000) ?? 1;
$perPage = Sanitizer::int($_GET['per_page'] ?? (string)ITEMS_PER_PAGE, 1, 100) ?? ITEMS_PER_PAGE;

// Filters
$options = [
    'page'        => $page,
    'per_page'    => $perPage,
    'category_id' => Sanitizer::int($_GET['category'] ?? null),
    'is_favorite' => isset($_GET['favorites']) ? 1 : null,
    'is_archived' => isset($_GET['archived']) ? 1 : null
];

// Remove null values
$options = array_filter($options, fn($v) => $v !== null);

// Perform search
$searchService = new SearchService();
$results = $searchService->search($query, $options);

// Return JSON - data contains items array directly for frontend compatibility
echo json_encode([
    'success' => true,
    'data'    => $results['items'] ?? [],
    'total'   => $results['total'] ?? 0,
    'page'    => $results['page'] ?? 1,
    'per_page' => $results['per_page'] ?? $perPage,
    'total_pages' => $results['total_pages'] ?? 0,
    'method'  => $results['method'] ?? 'like'
]);
exit;
