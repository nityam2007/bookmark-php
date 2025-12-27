<?php
/**
 * External API Endpoint
 * API key authenticated endpoint for external apps (Chrome extensions, mobile apps, etc.)
 * 
 * Endpoints:
 *   POST /api/external.php - Add a bookmark
 *   GET  /api/external.php - List bookmarks / Get single bookmark
 * 
 * Note: DELETE is disabled via API for safety. Use the web interface to delete bookmarks.
 * 
 * Authentication:
 *   - Header: Authorization: Bearer <api_key>
 *   - Header: X-API-Key: <api_key>
 *   - Query:  ?api_key=<api_key> (not recommended)
 * 
 * @package BookmarkManager\API
 */

declare(strict_types=1);

// Bootstrap
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
    require_once APP_ROOT . '/app/config/config.php';
    require_once APP_ROOT . '/app/core/Autoloader.php';
}

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Tag;
use App\Services\MetaFetcher;
use App\Helpers\ApiAuth;
use App\Helpers\Sanitizer;
use App\Core\View;

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, X-API-Key, Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Require API key authentication
ApiAuth::require();

$method = $_SERVER['REQUEST_METHOD'];
$id = Sanitizer::int($_GET['id'] ?? null);

// Route request (DELETE disabled for safety - use web interface)
match($method) {
    'GET'    => handleGet($id),
    'POST'   => handlePost(),
    default  => View::json(['success' => false, 'error' => 'Method not allowed. API supports GET and POST only.'], 405)
};

/**
 * GET - List bookmarks or get single bookmark
 */
function handleGet(?int $id): never
{
    if ($id) {
        $bookmark = Bookmark::getWithRelations($id);
        
        if (!$bookmark) {
            View::json(['success' => false, 'error' => 'Bookmark not found'], 404);
        }
        
        View::json([
            'success' => true,
            'data'    => $bookmark
        ]);
    }

    // List bookmarks
    $page = Sanitizer::int($_GET['page'] ?? '1', 1) ?? 1;
    $perPage = Sanitizer::int($_GET['per_page'] ?? '20', 1, 100) ?? 20;
    
    $filters = [];
    if (!empty($_GET['category'])) {
        $filters['category_id'] = Sanitizer::int($_GET['category']);
    }
    if (isset($_GET['favorites'])) {
        $filters['is_favorite'] = 1;
    }
    
    $result = Bookmark::paginateWithRelations($page, $perPage, $filters);
    
    View::json([
        'success' => true,
        'data'    => $result['items'],
        'meta'    => [
            'page'       => $result['page'],
            'per_page'   => $result['per_page'],
            'total'      => $result['total'],
            'total_pages'=> $result['total_pages']
        ]
    ]);
}

/**
 * POST - Create a new bookmark
 * 
 * Request body (JSON):
 * {
 *   "url": "https://example.com",          // Required
 *   "title": "Example Site",               // Optional (auto-fetched if empty)
 *   "description": "Description here",     // Optional (auto-fetched if empty)
 *   "category": "Category Name",           // Optional (name or ID)
 *   "category_id": 1,                      // Optional (ID takes precedence)
 *   "tags": ["tag1", "tag2"],              // Optional
 *   "is_favorite": true,                   // Optional
 *   "fetch_meta": true                     // Optional, default true
 * }
 */
function handlePost(): never
{
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        View::json(['success' => false, 'error' => 'Invalid JSON body'], 400);
    }
    
    // Validate URL
    $url = Sanitizer::url($input['url'] ?? '');
    
    if (!$url) {
        View::json(['success' => false, 'error' => 'Valid URL is required'], 400);
    }
    
    // Check for duplicate
    $existing = Bookmark::findByUrl($url);
    
    if ($existing) {
        View::json([
            'success'  => false,
            'error'    => 'Bookmark already exists',
            'bookmark' => $existing
        ], 409);
    }
    
    // Prepare bookmark data
    $title = Sanitizer::string($input['title'] ?? '', 255);
    $description = Sanitizer::string($input['description'] ?? '', 5000);
    $isFavorite = !empty($input['is_favorite']);
    $fetchMeta = $input['fetch_meta'] ?? true;
    
    // Handle category
    $categoryId = null;
    if (!empty($input['category_id'])) {
        $categoryId = Sanitizer::int($input['category_id']);
    } elseif (!empty($input['category'])) {
        // Find or create category by name
        $categoryName = Sanitizer::string($input['category'], 100);
        $category = Category::findByName($categoryName);
        if ($category) {
            $categoryId = $category['id'];
        } else {
            // Create new category
            $categoryId = Category::create([
                'name' => $categoryName,
                'slug' => Category::generateSlug($categoryName)
            ]);
        }
    }
    
    // Fetch metadata if enabled and title/description not provided
    $metaImage = null;
    $favicon = null;
    
    if ($fetchMeta && (empty($title) || empty($description))) {
        try {
            $fetcher = new MetaFetcher();
            $meta = $fetcher->fetch($url);
            if (empty($title)) {
                $title = $meta['title'] ?? parse_url($url, PHP_URL_HOST);
            }
            if (empty($description)) {
                $description = $meta['description'] ?? '';
            }
            $metaImage = $meta['image'] ?? null;
            $favicon = $meta['favicon'] ?? null;
        } catch (\Exception $e) {
            // Fallback to URL as title
            if (empty($title)) {
                $title = parse_url($url, PHP_URL_HOST);
            }
        }
    }
    
    // Create bookmark (createBookmark auto-generates url_hash)
    $bookmarkId = Bookmark::createBookmark([
        'url'              => $url,
        'title'            => $title ?: parse_url($url, PHP_URL_HOST),
        'description'      => $description,
        'meta_image'       => $metaImage,
        'favicon'          => $favicon,
        'category_id'      => $categoryId,
        'is_favorite'      => $isFavorite ? 1 : 0,
        'meta_fetched_at'  => $fetchMeta ? date('Y-m-d H:i:s') : null
    ]);
    
    // Handle tags
    if (!empty($input['tags']) && is_array($input['tags'])) {
        $tagIds = [];
        foreach ($input['tags'] as $tagName) {
            $tagName = Sanitizer::string($tagName, 50);
            if ($tagName) {
                $tagIds[] = Tag::findOrCreate($tagName);
            }
        }
        if ($tagIds) {
            Bookmark::syncTags($bookmarkId, $tagIds);
        }
    }
    
    // Return created bookmark
    $bookmark = Bookmark::getWithRelations($bookmarkId);
    
    View::json([
        'success' => true,
        'message' => 'Bookmark created successfully',
        'data'    => $bookmark
    ], 201);
}
