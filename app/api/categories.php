<?php
/**
 * Categories API Endpoint
 * API key authenticated endpoint for fetching categories
 * 
 * Endpoints:
 *   GET /api/categories.php - List all categories
 *   GET /api/categories.php?id=X - Get single category
 *   POST /api/categories.php - Create a category
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

use App\Models\Category;
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

// Route request
match($method) {
    'GET'    => handleGet($id),
    'POST'   => handlePost(),
    default  => View::json(['success' => false, 'error' => 'Method not allowed'], 405)
};

/**
 * GET - List categories or get single category
 */
function handleGet(?int $id): never
{
    // Single category
    if ($id) {
        $category = Category::find($id);
        
        if (!$category) {
            View::json(['success' => false, 'error' => 'Category not found'], 404);
        }
        
        // Get bookmark count
        $category['bookmark_count'] = Category::getBookmarkCount($id);
        
        View::json([
            'success' => true,
            'data'    => $category
        ]);
    }

    // Check for tree format
    $format = $_GET['format'] ?? 'flat';
    
    if ($format === 'tree') {
        // Hierarchical tree structure
        $categories = Category::getTree();
        View::json([
            'success' => true,
            'data'    => $categories
        ]);
    }
    
    // Flat list with depth indicators (default)
    $categories = Category::getFlatTreeWithCounts();
    
    View::json([
        'success' => true,
        'data'    => $categories
    ]);
}

/**
 * POST - Create a new category
 * 
 * Request body (JSON):
 * {
 *   "name": "Category Name",        // Required
 *   "parent_id": 1,                 // Optional (for nesting)
 *   "description": "Description",   // Optional
 *   "color": "#3B82F6"              // Optional
 * }
 */
function handlePost(): never
{
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        View::json(['success' => false, 'error' => 'Invalid JSON body'], 400);
    }
    
    // Validate name
    $name = Sanitizer::string($input['name'] ?? '', 100);
    
    if (empty($name)) {
        View::json(['success' => false, 'error' => 'Category name is required'], 400);
    }
    
    // Check for duplicate name
    $existing = Category::findByName($name);
    if ($existing) {
        View::json([
            'success'  => false,
            'error'    => 'Category already exists',
            'category' => $existing
        ], 409);
    }
    
    // Prepare data
    $data = [
        'name'        => $name,
        'description' => Sanitizer::string($input['description'] ?? '', 255),
        'color'       => Sanitizer::string($input['color'] ?? '', 20),
        'parent_id'   => Sanitizer::int($input['parent_id'] ?? null)
    ];
    
    // Validate parent if provided
    if ($data['parent_id']) {
        $parent = Category::find($data['parent_id']);
        if (!$parent) {
            View::json(['success' => false, 'error' => 'Parent category not found'], 400);
        }
        
        // Check max depth
        if ($parent['level'] >= CATEGORY_MAX_DEPTH) {
            View::json(['success' => false, 'error' => 'Maximum category depth reached'], 400);
        }
    }
    
    // Create category
    $categoryId = Category::createCategory($data);
    
    // Get the created category
    $category = Category::find($categoryId);
    
    View::json([
        'success' => true,
        'message' => 'Category created successfully',
        'data'    => $category
    ], 201);
}
