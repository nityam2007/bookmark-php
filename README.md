# Bookmark Manager

A production-ready, fast, and modular bookmark management system built with PHP 8.2+ and MySQL/MariaDB. Designed for both Docker deployment and cPanel shared hosting with a focus on speed, security, and maintainability.

## Features

- ⚡ **Fast Search** - Full-text search with AJAX and client-side caching
- 📁 **Nested Categories** - Support for up to 10 levels of category hierarchy
- 🏷️ **Flexible Tagging** - Tag bookmarks for quick filtering
- 📥 **Import/Export** - Support for JSON, HTML (browser format), and CSV
- 📥 **Smart Duplicate Detection** - Skip duplicates only when URL AND category match exactly
- 🌙 **Dark Mode** - Automatic theme detection with manual toggle
- 🔒 **Secure by Default** - CSRF protection, XSS prevention, prepared statements
- 🇪🇺 **GDPR Compliant** - Cookie consent and data privacy features
- 📱 **Responsive Design** - Mobile-first approach
- ⌨️ **Keyboard Shortcuts** - Power user friendly
- 🐳 **Docker Ready** - Easy deployment with Docker Compose
- 🖼️ **Image Caching** - Automatic favicon and image caching (weekly refresh for bandwidth efficiency)
- 🖼️ **Image URL Support** - Save image URLs directly with automatic offloading/caching
- 🔄 **Meta Fetching** - Automatic title and description extraction from URLs
- 🧩 **Browser Extension** - Chrome/Edge extension for quick bookmark saving
- 🛠️ **Web Installer** - Easy setup wizard for shared hosting

## Requirements

### For Docker Deployment
- Docker Engine 20.10+
- Docker Compose 2.0+

### For Manual/Shared Hosting Installation
- PHP 8.1 or higher
- MySQL 8.0+ or MariaDB 10.5+
- Apache with mod_rewrite (or nginx)
- Required PHP extensions: pdo, pdo_mysql, json, mbstring, curl, openssl

## Installation

### Option 1: Docker (Recommended)

The easiest way to get started is using Docker:

```bash
# Clone the repository
git clone <repository-url>
cd bookmark-manager

# Start the containers
docker compose up -d
```

The application will be available at `http://localhost:8080`.

**Default Docker Configuration:**
- Web Server: `http://localhost:8080`
- MySQL: `localhost:3306`
- Database: `bookmarks_db`
- User: `bookmark_user`
- Password: `bookmark_pass`

### Option 2: Web Installer (Shared Hosting)

For cPanel, Plesk, or any shared hosting with PHP 8.1+ and MySQL:

1. **Upload Files** - Upload all files to your web hosting via FTP/File Manager
2. **Point Domain** - Set your domain's document root to the `public` folder
3. **Run Installer** - Visit `https://yourdomain.com/install.php`
4. **Follow Wizard** - The installer will:
   - Check system requirements
   - Configure database connection
   - Create database tables
   - Set up admin account
5. **Delete Installer** - Remove `install.php` after installation for security

### Option 3: Manual Installation

#### 1. Upload Files

Upload all files to your web hosting. The `public` folder should be your document root.

```
/home/username/
├── bookmark-manager/       # Application root
│   ├── app/               # Application code
│   ├── cache/             # Cache storage
│   ├── cron/              # Cron scripts
│   ├── database/          # Database schema
│   └── public/            # Document root (point domain here)
```

#### 2. Create Database

Create a new MySQL database via cPanel and import the schema:

```bash
mysql -u username -p database_name < database/schema.sql
```

Or use phpMyAdmin to import `database/schema.sql`.

#### 3. Configure Application

Copy the example config and edit with your settings:

```bash
cp app/config/config.example.json app/config/config.json
```

Edit `config.json`:

```json
{
    "database": {
        "host": "localhost",
        "name": "your_database",
        "user": "your_username",
        "password": "your_password"
    },
    "app": {
        "url": "https://yourdomain.com"
    }
}
```

#### 4. Set Permissions

```bash
chmod 755 -R app/
chmod 777 cache/
chmod 777 logs/
chmod 644 app/config/config.json
```

#### 5. Set Up Cron Jobs (Optional)

In cPanel > Cron Jobs, add:

```
# Refresh bookmark metadata weekly (Sunday 4 AM)
0 4 * * 0 /usr/local/bin/php /home/username/bookmark-manager/cron/refresh-meta.php

# Cache bookmark images weekly (Sunday 3 AM) - bandwidth efficient
0 3 * * 0 /usr/local/bin/php /home/username/bookmark-manager/cron/cache-images.php

# Cleanup expired cache weekly (Sunday 2 AM)
0 2 * * 0 /usr/local/bin/php /home/username/bookmark-manager/cron/cleanup.php
```

#### 6. Create Admin User

Visit `https://yourdomain.com/register` to create your first account.

## Configuration Options

### config.json

```json
{
    "database": {
        "host": "localhost",
        "name": "bookmarks",
        "user": "root",
        "password": "",
        "charset": "utf8mb4"
    },
    "app": {
        "name": "Bookmark Manager",
        "url": "http://localhost",
        "debug": false,
        "timezone": "UTC",
        "per_page": 20
    },
    "security": {
        "session_lifetime": 7200,
        "password_algo": "argon2id"
    },
    "cache": {
        "enabled": true,
        "ttl": 3600,
        "driver": "file"
    },
    "gdpr": {
        "enabled": true,
        "cookie_consent": true,
        "data_retention_days": 365
    }
}
```

## Folder Structure

```
bookmark-manager/
├── app/
│   ├── api/              # API endpoints (bookmarks, export, import, meta, search)
│   ├── config/           # Configuration files
│   ├── controllers/      # Request handlers
│   ├── core/             # Core framework classes (Autoloader, Database, Router, View)
│   ├── helpers/          # Utility functions (Auth, CSRF, Sanitizer)
│   ├── models/           # Database models (Bookmark, Category, Tag, User)
│   ├── services/         # Business logic services
│   │   ├── CacheService.php
│   │   ├── EnhancedMetaFetcher.php
│   │   ├── ImageCacheService.php
│   │   ├── ImportExportService.php
│   │   ├── MetaFetcher.php
│   │   └── SearchService.php
│   └── views/
│       ├── components/   # Reusable UI components
│       ├── layout.php    # Main layout template
│       └── pages/        # Page templates
├── cache/                # File-based cache storage
│   └── images/           # Cached favicon and images
├── cron/                 # Scheduled task scripts
│   ├── cache-images.php
│   ├── cleanup.php
│   ├── fetch-meta.php
│   └── refresh-meta.php
├── database/             # SQL schema and migrations
│   ├── schema.sql
│   └── migrations/
├── logs/                 # Application logs
├── public/               # Web-accessible files
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   ├── img/              # Images
│   ├── errors/           # Error pages (404, 500)
│   └── index.php         # Application entry point
├── docker-compose.yml    # Docker Compose configuration
├── Dockerfile            # Docker image definition
└── README.md             # This file
```

## API Endpoints

### Internal API (Session Auth)

These endpoints require session authentication (login via browser):

```
GET    /api/search?q=query&limit=10    # Search bookmarks
GET    /api/meta?url=https://example.com    # Fetch URL metadata
GET    /api/bookmarks                  # List bookmarks
POST   /api/bookmarks                  # Create bookmark
GET    /api/bookmarks/:id              # Get bookmark
PUT    /api/bookmarks/:id              # Update bookmark
DELETE /api/bookmarks/:id              # Delete bookmark
```

### External API (API Key Auth)

For Chrome extensions, mobile apps, or other external integrations.

**Authentication:** Include your API key in the request header:
```
Authorization: Bearer bm_xxxxxxxxxxxxxxxxxxxx
```
Or use the `X-API-Key` header:
```
X-API-Key: bm_xxxxxxxxxxxxxxxxxxxx
```

**Generate API Keys:** Go to Settings → API Keys in your dashboard.

#### Add Bookmark
```bash
POST /api/external.php

curl -X POST https://yourdomain.com/api/external.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://example.com",
    "title": "Example Site",
    "description": "Optional description",
    "category": "Work",
    "tags": ["reference", "tools"],
    "is_favorite": true
  }'
```

**Request Body:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `url` | string | Yes | The URL to bookmark |
| `title` | string | No | Title (auto-fetched if empty) |
| `description` | string | No | Description (auto-fetched if empty) |
| `category` | string | No | Category name (created if doesn't exist) |
| `category_id` | int | No | Category ID (takes precedence over name) |
| `tags` | array | No | Array of tag names |
| `is_favorite` | bool | No | Mark as favorite |
| `fetch_meta` | bool | No | Auto-fetch title/description (default: true) |

**Response:**
```json
{
  "success": true,
  "message": "Bookmark created successfully",
  "data": {
    "id": 123,
    "url": "https://example.com",
    "title": "Example Site",
    ...
  }
}
```

#### List Bookmarks
```bash
GET /api/external.php?page=1&per_page=20
```

#### Get Single Bookmark
```bash
GET /api/external.php?id=123
```

#### Delete Bookmark
```bash
DELETE /api/external.php?id=123
```

## Browser Extension

A Chrome/Edge/Brave extension is included for quickly saving bookmarks from any webpage.

### Installation

1. Open `chrome://extensions/` (or `edge://extensions/` for Edge)
2. Enable **Developer mode**
3. Click **Load unpacked**
4. Select the `browser-extension` folder
5. The extension icon will appear in your toolbar

### Setup

1. Click the extension icon → **Settings**
2. Enter your **Server URL** (e.g., `http://127.0.0.1:8080`)
3. Enter your **API Key** (from Settings → API Keys in your dashboard)
4. Click **Test Connection** then **Save**

### Usage

1. Navigate to any webpage
2. Click the extension icon
3. Select a category (default: Uncategorized)
4. Add tags (optional)
5. Click **Save Bookmark**

See [browser-extension/README.md](browser-extension/README.md) for detailed documentation.

## Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `Ctrl+K` / `Cmd+K` | Focus search |
| `N` | New bookmark |
| `?` | Show shortcuts help |
| `↑` / `↓` | Navigate search results |
| `Enter` | Select result |
| `Esc` | Close search/modal |

## Import/Export Formats

### JSON Format
```json
{
    "bookmarks": [
        {
            "url": "https://example.com",
            "title": "Example",
            "description": "Description here",
            "category": "Category Name",
            "tags": ["tag1", "tag2"],
            "is_favorite": true
        }
    ]
}
```

### HTML Format
Standard Netscape Bookmark File format compatible with all major browsers.

### CSV Format
```csv
url,title,description,category,tags,is_favorite
https://example.com,Example,Description,Category,"tag1,tag2",1
```

## Security Features

- **CSRF Protection**: All forms include CSRF tokens
- **XSS Prevention**: All output is escaped by default
- **SQL Injection Prevention**: All queries use prepared statements
- **Password Hashing**: Argon2id or bcrypt with secure defaults
- **Session Security**: HTTPOnly cookies, secure flags, regeneration
- **Rate Limiting Ready**: API endpoints support rate limiting headers

## Performance Optimizations

- **Full-text Search**: MySQL FULLTEXT indexes for fast search
- **File-based Caching**: JSON cache for search results and metadata
- **Lazy Loading**: Images and non-critical resources load lazily
- **Minimal Dependencies**: No external PHP packages required
- **Client-side Caching**: Search results cached in browser memory

## Troubleshooting

### Docker Issues

**Containers won't start:**
```bash
# Check container logs
docker compose logs -f

# Rebuild containers
docker compose down && docker compose up -d --build
```

**Database connection issues in Docker:**
- Wait for MySQL to fully initialize (check with `docker compose logs mysql`)
- Ensure the MySQL health check passes before the web container starts

### General Issues

### "500 Internal Server Error"
- Check PHP error logs (in Docker: `docker compose logs web`)
- Verify `config.json` syntax is valid
- Ensure cache and logs directories are writable

### "Database Connection Failed"
- Verify database credentials in `config.json`
- Check if MySQL server is running
- Ensure database user has proper permissions

### "Class Not Found"
- Check namespace declarations match folder structure
- Verify autoloader is properly included

### Search Not Working
- Ensure full-text indexes exist on bookmarks table
- Check if minimum word length is configured in MySQL

## License

MIT License - see LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Docker Commands Reference

```bash
# Start containers
docker compose up -d

# Stop containers
docker compose down

# View logs
docker compose logs -f

# Rebuild after changes
docker compose up -d --build

# Access MySQL CLI
docker compose exec mysql mysql -u bookmark_user -pbookmark_pass bookmarks_db

# Access PHP container shell
docker compose exec web bash
```

## Support

For issues and feature requests, please use the GitHub issue tracker.
