<?php
/**
 * Bookmarks API Endpoint
 * RESTful API for bookmark operations
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

use App\Models\Bookmark;
use App\Models\Tag;
use App\Services\MetaFetcher;
use App\Helpers\Auth;
use App\Helpers\Csrf;
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

$method = $_SERVER['REQUEST_METHOD'];
$id = Sanitizer::int($_GET['id'] ?? null);

// Handle request based on method
match($method) {
    'GET'    => handleGet($id),
    'POST'   => handlePost(),
    'PUT'    => handlePut($id),
    'DELETE' => handleDelete($id),
    default  => View::json(['error' => 'Method not allowed'], 405)
};

/**
 * GET - List or single bookmark
 */
function handleGet(?int $id): never
{
    if ($id) {
        $bookmark = Bookmark::getWithRelations($id);
        
        if (!$bookmark) {
            View::json(['error' => 'Not found'], 404);
        }
        
        View::json(['success' => true, 'data' => $bookmark]);
    }

    // List with pagination
    $page = Sanitizer::int($_GET['page'] ?? '1', 1) ?? 1;
    $perPage = Sanitizer::int($_GET['per_page'] ?? (string)ITEMS_PER_PAGE, 1, 100) ?? ITEMS_PER_PAGE;
    
    $filters = [
        'category_id' => Sanitizer::int($_GET['category'] ?? null),
        'is_favorite' => isset($_GET['favorites']) ? 1 : null,
        'is_archived' => isset($_GET['archived']) ? 1 : null
    ];
    $filters = array_filter($filters, fn($v) => $v !== null);
    
    $bookmarks = Bookmark::paginateWithRelations($page, $perPage, $filters);
    
    View::json(['success' => true, 'data' => $bookmarks]);
}

/**
 * POST - Create bookmark
 */
function handlePost(): never
{
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    // Validate CSRF for non-API requests
    $token = $input['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!Csrf::validate($token)) {
        View::json(['error' => 'Invalid CSRF token'], 403);
    }
    
    $url = Sanitizer::url($input['url'] ?? '');
    
    if (!$url) {
        View::json(['error' => 'Valid URL is required'], 400);
    }
    
    if (Bookmark::urlExists($url)) {
        View::json(['error' => 'URL already exists'], 409);
    }
    
    // Fetch meta if title not provided
    $title = Sanitizer::string($input['title'] ?? '', 255);
    $description = Sanitizer::string($input['description'] ?? '', 1000);
    $metaImage = null;
    $favicon = null;
    
    if (empty($title)) {
        $fetcher = new MetaFetcher();
        $meta = $fetcher->fetch($url);
        
        $title = $meta['title'] ?? parse_url($url, PHP_URL_HOST);
        $description = $description ?: ($meta['description'] ?? null);
        $metaImage = $meta['meta_image'] ?? null;
        $favicon = $meta['favicon'] ?? null;
    }
    
    $bookmarkId = Bookmark::createBookmark([
        'url'             => $url,
        'title'           => $title,
        'description'     => $description,
        'meta_image'      => $metaImage ?? Sanitizer::url($input['meta_image'] ?? ''),
        'favicon'         => $favicon ?? Sanitizer::url($input['favicon'] ?? ''),
        'category_id'     => Sanitizer::int($input['category_id'] ?? null),
        'is_favorite'     => Sanitizer::bool($input['is_favorite'] ?? false) ? 1 : 0,
        'meta_fetched_at' => date('Y-m-d H:i:s')
    ]);
    
    // Handle tags
    if (!empty($input['tags'])) {
        $tagNames = Sanitizer::tags($input['tags']);
        $tags = Tag::findOrCreateMultiple($tagNames);
        Bookmark::syncTags($bookmarkId, array_column($tags, 'id'));
    }
    
    $bookmark = Bookmark::getWithRelations($bookmarkId);
    
    View::json([
        'success' => true,
        'message' => 'Bookmark created',
        'data'    => $bookmark
    ], 201);
}

/**
 * PUT - Update bookmark
 */
function handlePut(?int $id): never
{
    if (!$id) {
        View::json(['error' => 'ID required'], 400);
    }
    
    $bookmark = Bookmark::find($id);
    if (!$bookmark) {
        View::json(['error' => 'Not found'], 404);
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    $token = $input['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!Csrf::validate($token)) {
        View::json(['error' => 'Invalid CSRF token'], 403);
    }
    
    $data = [];
    
    if (isset($input['url'])) {
        $url = Sanitizer::url($input['url']);
        if (!$url) {
            View::json(['error' => 'Invalid URL'], 400);
        }
        if (Bookmark::urlExists($url, $id)) {
            View::json(['error' => 'URL already exists'], 409);
        }
        $data['url'] = $url;
        $data['url_hash'] = Bookmark::hashUrl($url);
    }
    
    if (isset($input['title'])) {
        $data['title'] = Sanitizer::string($input['title'], 255);
    }
    
    if (isset($input['description'])) {
        $data['description'] = Sanitizer::string($input['description'], 1000);
    }
    
    if (isset($input['category_id'])) {
        $data['category_id'] = Sanitizer::int($input['category_id']);
    }
    
    if (isset($input['is_favorite'])) {
        $data['is_favorite'] = Sanitizer::bool($input['is_favorite']) ? 1 : 0;
    }
    
    if (isset($input['is_archived'])) {
        $data['is_archived'] = Sanitizer::bool($input['is_archived']) ? 1 : 0;
    }
    
    if (!empty($data)) {
        Bookmark::update($id, $data);
    }
    
    // Handle tags
    if (isset($input['tags'])) {
        $tagNames = Sanitizer::tags($input['tags']);
        $tags = Tag::findOrCreateMultiple($tagNames);
        Bookmark::syncTags($id, array_column($tags, 'id'));
    }
    
    $updated = Bookmark::getWithRelations($id);
    
    View::json([
        'success' => true,
        'message' => 'Bookmark updated',
        'data'    => $updated
    ]);
}

/**
 * DELETE - Remove bookmark
 */
function handleDelete(?int $id): never
{
    if (!$id) {
        View::json(['error' => 'ID required'], 400);
    }
    
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!Csrf::validate($token)) {
        View::json(['error' => 'Invalid CSRF token'], 403);
    }
    
    $deleted = Bookmark::delete($id);
    
    if (!$deleted) {
        View::json(['error' => 'Not found'], 404);
    }
    
    View::json([
        'success' => true,
        'message' => 'Bookmark deleted'
    ]);
}
