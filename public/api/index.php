<?php
/**
 * API Documentation Page
 * Displays documentation for the External API
 * 
 * @package BookmarkManager\API
 */

declare(strict_types=1);

// Define root path  
define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/app/config/config.php';

$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation | <?= APP_NAME ?></title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg: #0f172a;
            --bg-card: #1e293b;
            --bg-code: #0d1117;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --border: #334155;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius: 0.5rem;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        header {
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border);
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .subtitle {
            color: var(--text-muted);
            font-size: 1.125rem;
        }
        
        h2 {
            font-size: 1.5rem;
            margin: 2.5rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }
        
        h3 {
            font-size: 1.25rem;
            margin: 2rem 0 0.75rem;
            color: var(--primary);
        }
        
        h4 {
            font-size: 1rem;
            margin: 1.5rem 0 0.5rem;
        }
        
        p {
            margin-bottom: 1rem;
        }
        
        a {
            color: var(--primary);
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .endpoint {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin: 1.5rem 0;
            overflow: hidden;
        }
        
        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: rgba(37, 99, 235, 0.1);
            border-bottom: 1px solid var(--border);
        }
        
        .method {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        
        .method-get { background: var(--success); color: #000; }
        .method-post { background: var(--primary); color: #fff; }
        .method-delete { background: var(--danger); color: #fff; }
        .method-put { background: var(--warning); color: #000; }
        
        .endpoint-path {
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.9rem;
        }
        
        .endpoint-body {
            padding: 1.25rem;
        }
        
        .endpoint-desc {
            margin-bottom: 1rem;
            color: var(--text-muted);
        }
        
        pre {
            background: var(--bg-code);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            overflow-x: auto;
            font-size: 0.85rem;
            margin: 1rem 0;
        }
        
        code {
            font-family: 'Monaco', 'Consolas', 'Courier New', monospace;
        }
        
        .inline-code {
            background: var(--bg-code);
            padding: 0.125rem 0.375rem;
            border-radius: 3px;
            font-size: 0.875em;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        
        th, td {
            text-align: left;
            padding: 0.75rem;
            border: 1px solid var(--border);
        }
        
        th {
            background: var(--bg-card);
            font-weight: 600;
        }
        
        .required {
            color: var(--danger);
            font-size: 0.75rem;
        }
        
        .optional {
            color: var(--text-muted);
            font-size: 0.75rem;
        }
        
        .type {
            color: var(--warning);
            font-family: monospace;
            font-size: 0.8rem;
        }
        
        .auth-box {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .auth-box h4 {
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success { background: rgba(16, 185, 129, 0.2); color: var(--success); }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: var(--warning); }
        
        .response-example {
            margin-top: 1rem;
        }
        
        .response-example h5 {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        
        .nav {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .nav-title {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .nav-list {
            list-style: none;
        }
        
        .nav-list li {
            margin: 0.375rem 0;
        }
        
        .nav-list a {
            color: var(--text);
            font-size: 0.9rem;
        }
        
        .try-it {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
        
        .try-it textarea {
            width: 100%;
            min-height: 100px;
            background: var(--bg-code);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text);
            font-family: monospace;
            padding: 0.75rem;
            resize: vertical;
        }
        
        .try-it input {
            width: 100%;
            background: var(--bg-code);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text);
            padding: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .try-it button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 500;
        }
        
        .try-it button:hover {
            background: var(--primary-dark);
        }
        
        .try-it-response {
            margin-top: 1rem;
            background: var(--bg-code);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            max-height: 300px;
            overflow: auto;
            display: none;
        }
        
        footer {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
            text-align: center;
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            h1 { font-size: 1.75rem; }
            .endpoint-header { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Bookmark Manager API</h1>
            <p class="subtitle">External API for Chrome extensions, mobile apps, and integrations</p>
        </header>

        <nav class="nav">
            <div class="nav-title">Quick Navigation</div>
            <ul class="nav-list">
                <li><a href="#authentication">🔐 Authentication</a></li>
                <li><a href="#categories">📁 Categories</a></li>
                <li><a href="#add-bookmark">➕ Add Bookmark</a></li>
                <li><a href="#list-bookmarks">📋 List Bookmarks</a></li>
                <li><a href="#get-bookmark">🔍 Get Single Bookmark</a></li>
                <li><a href="#delete-bookmark">🗑️ Delete Bookmark</a></li>
                <li><a href="#errors">⚠️ Error Handling</a></li>
                <li><a href="#examples">💡 Code Examples</a></li>
            </ul>
        </nav>

        <section id="authentication">
            <h2>🔐 Authentication</h2>
            <p>All API requests require authentication using an API key. Generate your API key from <a href="/settings">Settings → API Keys</a>.</p>
            
            <div class="auth-box">
                <h4>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
                    </svg>
                    API Key Format
                </h4>
                <p>API keys start with <code class="inline-code">bm_</code> followed by 48 characters.</p>
                <pre><code>bm_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4</code></pre>
            </div>

            <h4>Authentication Methods</h4>
            <p>Include your API key in the request using one of these methods:</p>

            <h4>Option 1: Authorization Header (Recommended)</h4>
            <pre><code>Authorization: Bearer YOUR_API_KEY</code></pre>

            <h4>Option 2: X-API-Key Header</h4>
            <pre><code>X-API-Key: YOUR_API_KEY</code></pre>

            <h4>Option 3: Query Parameter (Not recommended for production)</h4>
            <pre><code>GET /api/external.php?api_key=YOUR_API_KEY</code></pre>
        </section>

        <section id="base-url">
            <h2>🌐 Base URL</h2>
            <pre><code><?= htmlspecialchars($baseUrl) ?>/api/external.php</code></pre>
            <p style="margin-top: 0.5rem;"><strong>Categories:</strong></p>
            <pre><code><?= htmlspecialchars($baseUrl) ?>/api/categories.php</code></pre>
        </section>

        <section id="categories">
            <h2>📁 Categories</h2>
            <p>Categories (or collections/folders) help organize your bookmarks. Use these endpoints to fetch categories before saving bookmarks.</p>

            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method method-get">GET</span>
                    <span class="endpoint-path">/api/categories.php</span>
                </div>
                <div class="endpoint-body">
                    <p class="endpoint-desc">Retrieve all categories with bookmark counts.</p>
                    
                    <h4>Query Parameters</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Default</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>format</code></td>
                                <td><span class="type">string</span></td>
                                <td>flat</td>
                                <td><code>flat</code> = list with depth, <code>tree</code> = nested hierarchy</td>
                            </tr>
                            <tr>
                                <td><code>id</code></td>
                                <td><span class="type">integer</span></td>
                                <td>-</td>
                                <td>Get a single category by ID</td>
                            </tr>
                        </tbody>
                    </table>

                    <h4>Example Request</h4>
                    <pre><code>curl -X GET "<?= htmlspecialchars($baseUrl) ?>/api/categories.php" \
  -H "Authorization: Bearer YOUR_API_KEY"</code></pre>

                    <div class="response-example">
                        <h5>Success Response (Flat Format)</h5>
                        <pre><code>{
  "success": true,
  "data": [
    { "id": 1, "name": "Uncategorized", "depth": 0, "bookmark_count": 5 },
    { "id": 2, "name": "Development", "depth": 0, "bookmark_count": 12 },
    { "id": 3, "name": "Frontend", "parent_id": 2, "depth": 1, "bookmark_count": 8 },
    { "id": 4, "name": "Backend", "parent_id": 2, "depth": 1, "bookmark_count": 4 },
    { "id": 5, "name": "Personal", "depth": 0, "bookmark_count": 3 }
  ]
}</code></pre>
                    </div>

                    <div class="response-example">
                        <h5>Tree Format (?format=tree)</h5>
                        <pre><code>{
  "success": true,
  "data": [
    { 
      "id": 2, 
      "name": "Development", 
      "bookmark_count": 12,
      "children": [
        { "id": 3, "name": "Frontend", "bookmark_count": 8, "children": [] },
        { "id": 4, "name": "Backend", "bookmark_count": 4, "children": [] }
      ]
    }
  ]
}</code></pre>
                    </div>
                </div>
            </div>

            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method method-post">POST</span>
                    <span class="endpoint-path">/api/categories.php</span>
                </div>
                <div class="endpoint-body">
                    <p class="endpoint-desc">Create a new category.</p>
                    
                    <h4>Request Body (JSON)</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Required</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>name</code></td>
                                <td><span class="type">string</span></td>
                                <td><span class="required">Required</span></td>
                                <td>Category name (max 100 chars)</td>
                            </tr>
                            <tr>
                                <td><code>parent_id</code></td>
                                <td><span class="type">integer</span></td>
                                <td><span class="optional">Optional</span></td>
                                <td>Parent category ID for nesting</td>
                            </tr>
                            <tr>
                                <td><code>description</code></td>
                                <td><span class="type">string</span></td>
                                <td><span class="optional">Optional</span></td>
                                <td>Category description</td>
                            </tr>
                            <tr>
                                <td><code>color</code></td>
                                <td><span class="type">string</span></td>
                                <td><span class="optional">Optional</span></td>
                                <td>Color code (e.g., #3B82F6)</td>
                            </tr>
                        </tbody>
                    </table>

                    <h4>Example Request</h4>
                    <pre><code>curl -X POST "<?= htmlspecialchars($baseUrl) ?>/api/categories.php" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"name": "Work", "description": "Work related bookmarks"}'</code></pre>

                    <div class="response-example">
                        <h5>Success Response (201 Created)</h5>
                        <pre><code>{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "id": 6,
    "name": "Work",
    "slug": "work",
    "description": "Work related bookmarks",
    "parent_id": null,
    "level": 0
  }
}</code></pre>
                    </div>
                </div>
            </div>
        </section>

        <section id="add-bookmark">
            <h2>➕ Add Bookmark</h2>
            <p>Create a new bookmark in your account.</p>

            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method method-post">POST</span>
                    <span class="endpoint-path">/api/external.php</span>
                </div>
                <div class="endpoint-body">
                    <p class="endpoint-desc">Adds a new bookmark. If title or description are not provided, they will be automatically fetched from the URL.</p>
                    
                    <h4>Request Body (JSON)</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Required</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>url</code></td>
                                <td><span class="type">string</span></td>
                                <td><span class="required">Required</span></td>
                                <td>The URL to bookmark (must be valid HTTP/HTTPS)</td>
                            </tr>
                            <tr>
                                <td><code>title</code></td>
                                <td><span class="type">string</span></td>
                                <td><span class="optional">Optional</span></td>
                                <td>Bookmark title (auto-fetched if empty)</td>
                            </tr>
                            <tr>
                                <td><code>description</code></td>
                                <td><span class="type">string</span></td>
                                <td><span class="optional">Optional</span></td>
                                <td>Bookmark description (auto-fetched if empty)</td>
                            </tr>
                            <tr>
                                <td><code>category</code></td>
                                <td><span class="type">string</span></td>
                                <td><span class="optional">Optional</span></td>
                                <td>Category name (created if doesn't exist)</td>
                            </tr>
                            <tr>
                                <td><code>category_id</code></td>
                                <td><span class="type">integer</span></td>
                                <td><span class="optional">Optional</span></td>
                                <td>Category ID (takes precedence over name)</td>
                            </tr>
                            <tr>
                                <td><code>tags</code></td>
                                <td><span class="type">array</span></td>
                                <td><span class="optional">Optional</span></td>
                                <td>Array of tag names</td>
                            </tr>
                            <tr>
                                <td><code>is_favorite</code></td>
                                <td><span class="type">boolean</span></td>
                                <td><span class="optional">Optional</span></td>
                                <td>Mark as favorite (default: false)</td>
                            </tr>
                            <tr>
                                <td><code>fetch_meta</code></td>
                                <td><span class="type">boolean</span></td>
                                <td><span class="optional">Optional</span></td>
                                <td>Auto-fetch metadata (default: true)</td>
                            </tr>
                        </tbody>
                    </table>

                    <h4>Example Request</h4>
                    <pre><code>curl -X POST <?= htmlspecialchars($baseUrl) ?>/api/external.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://github.com",
    "title": "GitHub",
    "description": "Where the world builds software",
    "category": "Development",
    "tags": ["git", "code", "opensource"],
    "is_favorite": true
  }'</code></pre>

                    <div class="response-example">
                        <h5>Success Response (201 Created)</h5>
                        <pre><code>{
  "success": true,
  "message": "Bookmark created successfully",
  "data": {
    "id": 42,
    "url": "https://github.com",
    "title": "GitHub",
    "description": "Where the world builds software",
    "category": { "id": 5, "name": "Development" },
    "tags": ["git", "code", "opensource"],
    "is_favorite": true,
    "created_at": "2025-12-27T10:30:00Z"
  }
}</code></pre>
                    </div>

                    <div class="response-example">
                        <h5>Duplicate Error (409 Conflict)</h5>
                        <pre><code>{
  "success": false,
  "error": "Bookmark already exists",
  "bookmark": { "id": 15, "url": "https://github.com", ... }
}</code></pre>
                    </div>
                </div>
            </div>
        </section>

        <section id="list-bookmarks">
            <h2>📋 List Bookmarks</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method method-get">GET</span>
                    <span class="endpoint-path">/api/external.php</span>
                </div>
                <div class="endpoint-body">
                    <p class="endpoint-desc">Retrieve a paginated list of bookmarks.</p>
                    
                    <h4>Query Parameters</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Default</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>page</code></td>
                                <td><span class="type">integer</span></td>
                                <td>1</td>
                                <td>Page number</td>
                            </tr>
                            <tr>
                                <td><code>per_page</code></td>
                                <td><span class="type">integer</span></td>
                                <td>20</td>
                                <td>Items per page (max 100)</td>
                            </tr>
                            <tr>
                                <td><code>category</code></td>
                                <td><span class="type">integer</span></td>
                                <td>-</td>
                                <td>Filter by category ID</td>
                            </tr>
                            <tr>
                                <td><code>favorites</code></td>
                                <td><span class="type">flag</span></td>
                                <td>-</td>
                                <td>Include to show only favorites</td>
                            </tr>
                        </tbody>
                    </table>

                    <h4>Example Request</h4>
                    <pre><code>curl -X GET "<?= htmlspecialchars($baseUrl) ?>/api/external.php?page=1&per_page=10" \
  -H "Authorization: Bearer YOUR_API_KEY"</code></pre>

                    <div class="response-example">
                        <h5>Success Response</h5>
                        <pre><code>{
  "success": true,
  "data": [
    { "id": 1, "url": "https://example.com", "title": "Example", ... },
    { "id": 2, "url": "https://github.com", "title": "GitHub", ... }
  ],
  "meta": {
    "page": 1,
    "per_page": 10,
    "total": 42,
    "total_pages": 5
  }
}</code></pre>
                    </div>
                </div>
            </div>
        </section>

        <section id="get-bookmark">
            <h2>🔍 Get Single Bookmark</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method method-get">GET</span>
                    <span class="endpoint-path">/api/external.php?id={id}</span>
                </div>
                <div class="endpoint-body">
                    <p class="endpoint-desc">Retrieve details of a specific bookmark.</p>
                    
                    <h4>Example Request</h4>
                    <pre><code>curl -X GET "<?= htmlspecialchars($baseUrl) ?>/api/external.php?id=42" \
  -H "Authorization: Bearer YOUR_API_KEY"</code></pre>

                    <div class="response-example">
                        <h5>Success Response</h5>
                        <pre><code>{
  "success": true,
  "data": {
    "id": 42,
    "url": "https://github.com",
    "title": "GitHub",
    "description": "Where the world builds software",
    "favicon": "https://github.githubassets.com/favicons/favicon.svg",
    "meta_image": "https://github.githubassets.com/images/modules/site/social-cards/github-social.png",
    "category": { "id": 5, "name": "Development" },
    "tags": ["git", "code", "opensource"],
    "is_favorite": true,
    "visit_count": 15,
    "created_at": "2025-12-27T10:30:00Z",
    "updated_at": "2025-12-27T12:00:00Z"
  }
}</code></pre>
                    </div>
                </div>
            </div>
        </section>

        <section id="delete-bookmark">
            <h2>🗑️ Delete Bookmark</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <span class="method method-delete">DELETE</span>
                    <span class="endpoint-path">/api/external.php?id={id}</span>
                </div>
                <div class="endpoint-body">
                    <p class="endpoint-desc">Permanently delete a bookmark.</p>
                    
                    <h4>Example Request</h4>
                    <pre><code>curl -X DELETE "<?= htmlspecialchars($baseUrl) ?>/api/external.php?id=42" \
  -H "Authorization: Bearer YOUR_API_KEY"</code></pre>

                    <div class="response-example">
                        <h5>Success Response</h5>
                        <pre><code>{
  "success": true,
  "message": "Bookmark deleted successfully"
}</code></pre>
                    </div>
                </div>
            </div>
        </section>

        <section id="errors">
            <h2>⚠️ Error Handling</h2>
            <p>The API uses standard HTTP status codes and returns JSON error responses.</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Status Code</th>
                        <th>Meaning</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>200</code></td>
                        <td>OK</td>
                        <td>Request successful</td>
                    </tr>
                    <tr>
                        <td><code>201</code></td>
                        <td>Created</td>
                        <td>Bookmark created successfully</td>
                    </tr>
                    <tr>
                        <td><code>400</code></td>
                        <td>Bad Request</td>
                        <td>Invalid request body or parameters</td>
                    </tr>
                    <tr>
                        <td><code>401</code></td>
                        <td>Unauthorized</td>
                        <td>Invalid or missing API key</td>
                    </tr>
                    <tr>
                        <td><code>404</code></td>
                        <td>Not Found</td>
                        <td>Bookmark not found</td>
                    </tr>
                    <tr>
                        <td><code>405</code></td>
                        <td>Method Not Allowed</td>
                        <td>HTTP method not supported</td>
                    </tr>
                    <tr>
                        <td><code>409</code></td>
                        <td>Conflict</td>
                        <td>Bookmark already exists (duplicate URL)</td>
                    </tr>
                    <tr>
                        <td><code>500</code></td>
                        <td>Server Error</td>
                        <td>Internal server error</td>
                    </tr>
                </tbody>
            </table>

            <h4>Error Response Format</h4>
            <pre><code>{
  "success": false,
  "error": "Error message here",
  "code": 400
}</code></pre>
        </section>

        <section id="examples">
            <h2>💡 Code Examples</h2>

            <h3>JavaScript (Fetch API)</h3>
            <pre><code>// Add a bookmark
async function addBookmark(url, title) {
  const response = await fetch('<?= htmlspecialchars($baseUrl) ?>/api/external.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer YOUR_API_KEY',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ url, title })
  });
  
  return response.json();
}

// Usage
addBookmark('https://example.com', 'Example Site')
  .then(data => console.log(data));</code></pre>

            <h3>Chrome Extension</h3>
            <pre><code>// background.js - Add current tab as bookmark
chrome.action.onClicked.addListener(async (tab) => {
  const response = await fetch('<?= htmlspecialchars($baseUrl) ?>/api/external.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer YOUR_API_KEY',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      url: tab.url,
      title: tab.title
    })
  });
  
  const result = await response.json();
  
  if (result.success) {
    chrome.notifications.create({
      type: 'basic',
      iconUrl: 'icon.png',
      title: 'Bookmark Added!',
      message: result.data.title
    });
  }
});</code></pre>

            <h3>Python</h3>
            <pre><code>import requests

API_URL = '<?= htmlspecialchars($baseUrl) ?>/api/external.php'
API_KEY = 'YOUR_API_KEY'

def add_bookmark(url, title=None, tags=None):
    headers = {
        'Authorization': f'Bearer {API_KEY}',
        'Content-Type': 'application/json'
    }
    
    data = {'url': url}
    if title:
        data['title'] = title
    if tags:
        data['tags'] = tags
    
    response = requests.post(API_URL, json=data, headers=headers)
    return response.json()

# Usage
result = add_bookmark(
    'https://python.org',
    title='Python',
    tags=['programming', 'language']
)
print(result)</code></pre>

            <h3>Bookmarklet</h3>
            <p>Drag this to your bookmarks bar to quickly add the current page:</p>
            <pre><code>javascript:(function(){
  fetch('<?= htmlspecialchars($baseUrl) ?>/api/external.php',{
    method:'POST',
    headers:{
      'Authorization':'Bearer YOUR_API_KEY',
      'Content-Type':'application/json'
    },
    body:JSON.stringify({
      url:location.href,
      title:document.title
    })
  }).then(r=>r.json()).then(d=>{
    alert(d.success?'✓ Bookmarked!':'✗ '+d.error);
  });
})();</code></pre>
        </section>

        <section id="try-it">
            <h2>🧪 Try It Out</h2>
            <div class="auth-box">
                <div class="try-it">
                    <h4>Quick Test</h4>
                    <input type="text" id="apiKeyInput" placeholder="Enter your API key (bm_...)">
                    <textarea id="requestBody" placeholder='{"url": "https://example.com", "title": "Example"}'></textarea>
                    <button onclick="testApi()">Send POST Request</button>
                    <pre class="try-it-response" id="responseOutput"></pre>
                </div>
            </div>
        </section>

        <footer>
            <p>
                <a href="/">← Back to Bookmark Manager</a> · 
                <a href="/settings">Manage API Keys</a> · 
                <a href="https://github.com/nityam2007/bookmark-php" target="_blank">GitHub</a>
            </p>
            <p style="margin-top: 1rem; font-size: 0.875rem;">
                <?= APP_NAME ?> API v1.0
            </p>
        </footer>
    </div>

    <script>
    async function testApi() {
        const apiKey = document.getElementById('apiKeyInput').value;
        const body = document.getElementById('requestBody').value;
        const output = document.getElementById('responseOutput');
        
        if (!apiKey) {
            alert('Please enter your API key');
            return;
        }
        
        output.style.display = 'block';
        output.textContent = 'Sending request...';
        
        try {
            const response = await fetch('/api/external.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${apiKey}`,
                    'Content-Type': 'application/json'
                },
                body: body
            });
            
            const data = await response.json();
            output.textContent = JSON.stringify(data, null, 2);
        } catch (error) {
            output.textContent = 'Error: ' + error.message;
        }
    }
    </script>
</body>
</html>
