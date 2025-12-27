# Bookmark Manager API Documentation

External API for integrating with Chrome extensions, mobile apps, and other third-party applications.

## Table of Contents

- [Authentication](#authentication)
- [Base URL](#base-url)
- [Endpoints](#endpoints)
  - [Add Bookmark](#add-bookmark)
  - [List Bookmarks](#list-bookmarks)
  - [Get Single Bookmark](#get-single-bookmark)
  - [Delete Bookmark](#delete-bookmark)
- [Error Handling](#error-handling)
- [Code Examples](#code-examples)
  - [cURL](#curl)
  - [JavaScript](#javascript)
  - [Python](#python)
  - [Chrome Extension](#chrome-extension)
  - [Bookmarklet](#bookmarklet)
- [Rate Limiting](#rate-limiting)

---

## Authentication

All API requests require authentication using an API key.

### Getting an API Key

1. Log in to your Bookmark Manager account
2. Go to **Settings → API Keys**
3. Click **Generate Key**
4. Copy and securely store your key (it's only shown once!)

### API Key Format

API keys start with `bm_` followed by 48 characters:

```
bm_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4
```

### Authentication Methods

#### Option 1: Authorization Header (Recommended)

```http
Authorization: Bearer bm_your_api_key_here
```

#### Option 2: X-API-Key Header

```http
X-API-Key: bm_your_api_key_here
```

#### Option 3: Query Parameter

> ⚠️ **Not recommended for production** - API key may be logged in server access logs

```
GET /api/external.php?api_key=bm_your_api_key_here
```

---

## Base URL

```
https://your-domain.com/api/external.php
```

For local development:
```
http://localhost:8080/api/external.php
```

---

## Endpoints

### Add Bookmark

Create a new bookmark in your account.

```http
POST /api/external.php
Content-Type: application/json
Authorization: Bearer YOUR_API_KEY
```

#### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `url` | string | **Yes** | The URL to bookmark (must be valid HTTP/HTTPS) |
| `title` | string | No | Bookmark title (auto-fetched if empty) |
| `description` | string | No | Bookmark description (auto-fetched if empty) |
| `category` | string | No | Category name (created if doesn't exist) |
| `category_id` | integer | No | Category ID (takes precedence over name) |
| `tags` | array | No | Array of tag names (e.g., `["work", "reference"]`) |
| `is_favorite` | boolean | No | Mark as favorite (default: `false`) |
| `fetch_meta` | boolean | No | Auto-fetch title/description from URL (default: `true`) |

#### Example Request

```bash
curl -X POST "https://your-domain.com/api/external.php" \
  -H "Authorization: Bearer bm_your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://github.com",
    "title": "GitHub",
    "description": "Where the world builds software",
    "category": "Development",
    "tags": ["git", "code", "opensource"],
    "is_favorite": true
  }'
```

#### Success Response (201 Created)

```json
{
  "success": true,
  "message": "Bookmark created successfully",
  "data": {
    "id": 42,
    "url": "https://github.com",
    "title": "GitHub",
    "description": "Where the world builds software",
    "favicon": "https://github.githubassets.com/favicons/favicon.svg",
    "category": {
      "id": 5,
      "name": "Development"
    },
    "tags": ["git", "code", "opensource"],
    "is_favorite": true,
    "created_at": "2025-12-27T10:30:00Z"
  }
}
```

#### Duplicate Error (409 Conflict)

```json
{
  "success": false,
  "error": "Bookmark already exists",
  "bookmark": {
    "id": 15,
    "url": "https://github.com",
    "title": "GitHub"
  }
}
```

---

### List Bookmarks

Retrieve a paginated list of bookmarks.

```http
GET /api/external.php
Authorization: Bearer YOUR_API_KEY
```

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 20 | Items per page (max: 100) |
| `category` | integer | - | Filter by category ID |
| `favorites` | flag | - | Include to show only favorites |

#### Example Request

```bash
curl -X GET "https://your-domain.com/api/external.php?page=1&per_page=10&favorites" \
  -H "Authorization: Bearer bm_your_api_key"
```

#### Success Response

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "url": "https://example.com",
      "title": "Example",
      "description": "An example website",
      "is_favorite": false
    },
    {
      "id": 2,
      "url": "https://github.com",
      "title": "GitHub",
      "description": "Where the world builds software",
      "is_favorite": true
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 10,
    "total": 42,
    "total_pages": 5
  }
}
```

---

### Get Single Bookmark

Retrieve details of a specific bookmark.

```http
GET /api/external.php?id={bookmark_id}
Authorization: Bearer YOUR_API_KEY
```

#### Example Request

```bash
curl -X GET "https://your-domain.com/api/external.php?id=42" \
  -H "Authorization: Bearer bm_your_api_key"
```

#### Success Response

```json
{
  "success": true,
  "data": {
    "id": 42,
    "url": "https://github.com",
    "title": "GitHub",
    "description": "Where the world builds software",
    "favicon": "https://github.githubassets.com/favicons/favicon.svg",
    "meta_image": "https://github.githubassets.com/images/modules/site/social-cards/github-social.png",
    "category": {
      "id": 5,
      "name": "Development"
    },
    "tags": ["git", "code", "opensource"],
    "is_favorite": true,
    "is_archived": false,
    "visit_count": 15,
    "last_visited_at": "2025-12-27T11:45:00Z",
    "created_at": "2025-12-27T10:30:00Z",
    "updated_at": "2025-12-27T12:00:00Z"
  }
}
```

#### Not Found Error (404)

```json
{
  "success": false,
  "error": "Bookmark not found"
}
```

---

### Delete Bookmark

Permanently delete a bookmark.

```http
DELETE /api/external.php?id={bookmark_id}
Authorization: Bearer YOUR_API_KEY
```

#### Example Request

```bash
curl -X DELETE "https://your-domain.com/api/external.php?id=42" \
  -H "Authorization: Bearer bm_your_api_key"
```

#### Success Response

```json
{
  "success": true,
  "message": "Bookmark deleted successfully"
}
```

---

## Error Handling

The API uses standard HTTP status codes and returns JSON error responses.

### HTTP Status Codes

| Code | Name | Description |
|------|------|-------------|
| `200` | OK | Request successful |
| `201` | Created | Resource created successfully |
| `400` | Bad Request | Invalid request body or parameters |
| `401` | Unauthorized | Invalid or missing API key |
| `404` | Not Found | Resource not found |
| `405` | Method Not Allowed | HTTP method not supported |
| `409` | Conflict | Resource already exists (duplicate) |
| `500` | Server Error | Internal server error |

### Error Response Format

```json
{
  "success": false,
  "error": "Human-readable error message",
  "code": 400
}
```

---

## Code Examples

### cURL

```bash
# Add a bookmark
curl -X POST "https://your-domain.com/api/external.php" \
  -H "Authorization: Bearer bm_your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://example.com"}'

# List bookmarks
curl -X GET "https://your-domain.com/api/external.php" \
  -H "Authorization: Bearer bm_your_api_key"

# Delete a bookmark
curl -X DELETE "https://your-domain.com/api/external.php?id=42" \
  -H "Authorization: Bearer bm_your_api_key"
```

### JavaScript

```javascript
const API_URL = 'https://your-domain.com/api/external.php';
const API_KEY = 'bm_your_api_key';

// Add a bookmark
async function addBookmark(url, title, tags = []) {
  const response = await fetch(API_URL, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${API_KEY}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ url, title, tags })
  });
  
  return response.json();
}

// List bookmarks
async function listBookmarks(page = 1, perPage = 20) {
  const response = await fetch(`${API_URL}?page=${page}&per_page=${perPage}`, {
    headers: {
      'Authorization': `Bearer ${API_KEY}`
    }
  });
  
  return response.json();
}

// Usage
addBookmark('https://example.com', 'Example Site', ['work', 'reference'])
  .then(result => console.log(result));
```

### Python

```python
import requests

API_URL = 'https://your-domain.com/api/external.php'
API_KEY = 'bm_your_api_key'

headers = {
    'Authorization': f'Bearer {API_KEY}',
    'Content-Type': 'application/json'
}

# Add a bookmark
def add_bookmark(url, title=None, tags=None):
    data = {'url': url}
    if title:
        data['title'] = title
    if tags:
        data['tags'] = tags
    
    response = requests.post(API_URL, json=data, headers=headers)
    return response.json()

# List bookmarks
def list_bookmarks(page=1, per_page=20):
    params = {'page': page, 'per_page': per_page}
    response = requests.get(API_URL, params=params, headers=headers)
    return response.json()

# Delete a bookmark
def delete_bookmark(bookmark_id):
    response = requests.delete(f'{API_URL}?id={bookmark_id}', headers=headers)
    return response.json()

# Usage
result = add_bookmark(
    'https://python.org',
    title='Python',
    tags=['programming', 'language']
)
print(result)
```

### Chrome Extension

**manifest.json**
```json
{
  "manifest_version": 3,
  "name": "Bookmark to Manager",
  "version": "1.0",
  "permissions": ["activeTab"],
  "action": {
    "default_title": "Add to Bookmark Manager"
  },
  "background": {
    "service_worker": "background.js"
  }
}
```

**background.js**
```javascript
const API_URL = 'https://your-domain.com/api/external.php';
const API_KEY = 'bm_your_api_key';

chrome.action.onClicked.addListener(async (tab) => {
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${API_KEY}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        url: tab.url,
        title: tab.title
      })
    });
    
    const result = await response.json();
    
    // Show notification
    chrome.notifications.create({
      type: 'basic',
      iconUrl: 'icon128.png',
      title: result.success ? '✓ Bookmark Added!' : '✗ Error',
      message: result.success ? result.data.title : result.error
    });
    
  } catch (error) {
    console.error('Failed to add bookmark:', error);
  }
});
```

### Bookmarklet

Create a bookmarklet by adding this as a bookmark URL:

```javascript
javascript:(function(){
  var apiKey = 'bm_your_api_key';
  var apiUrl = 'https://your-domain.com/api/external.php';
  
  fetch(apiUrl, {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + apiKey,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      url: location.href,
      title: document.title
    })
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    alert(d.success ? '✓ Bookmarked: ' + d.data.title : '✗ Error: ' + d.error);
  })
  .catch(function(e) {
    alert('✗ Failed: ' + e.message);
  });
})();
```

---

## Rate Limiting

Currently, there are no strict rate limits. However, please be respectful:

- Avoid making more than 60 requests per minute
- Use bulk operations where possible
- Cache responses when appropriate

Future versions may implement rate limiting. The following headers will be included in responses:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 55
X-RateLimit-Reset: 1703688000
```

---

## CORS Support

The API supports Cross-Origin Resource Sharing (CORS) for browser-based applications:

```http
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS
Access-Control-Allow-Headers: Authorization, X-API-Key, Content-Type
```

---

## Changelog

### v1.0.0 (2025-12-27)
- Initial API release
- Add, list, get, and delete bookmarks
- API key authentication
- Auto-fetch metadata from URLs
- Category and tag support

---

## Support

- **Documentation**: `/api/` or `/api/index.php`
- **GitHub Issues**: [nityam2007/bookmark-php](https://github.com/nityam2007/bookmark-php/issues)
- **API Keys**: Settings → API Keys in your dashboard
