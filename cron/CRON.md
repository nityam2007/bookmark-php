# Cron Jobs Documentation

This document describes all cron jobs available in Bookmark Manager for automated maintenance and data processing.

---

## Table of Contents

- [Overview](#overview)
- [Cron Jobs](#cron-jobs)
  - [fetch-meta.php](#fetch-metaphp) - Fetch metadata for new bookmarks
  - [cache-images.php](#cache-imagesphp) - Cache external images locally
  - [refresh-meta.php](#refresh-metaphp) - Refresh old metadata
  - [cleanup.php](#cleanupphp) - Remove expired cache and orphaned data
  - [refetch-bookmark.php](#refetch-bookmarkphp) - Manually refetch specific bookmarks
- [Hostinger/Shared Hosting Setup](#hostingershared-hosting-setup)
- [Docker Setup](#docker-setup)
- [Troubleshooting](#troubleshooting)

---

## Overview

Cron jobs handle background tasks that shouldn't block user requests:

| Job | Purpose | Recommended Schedule |
|-----|---------|---------------------|
| `fetch-meta.php` | Fetch metadata for new bookmarks | Every 15 minutes |
| `cache-images.php` | Cache external images locally | Weekly (or daily) |
| `refresh-meta.php` | Refresh old/stale metadata | Every 6 hours |
| `cleanup.php` | Remove expired cache, orphaned data | Daily at 3 AM |
| `refetch-bookmark.php` | Manual refetch by ID | On-demand |

---

## Cron Jobs

### fetch-meta.php

**Purpose:** Fetches and updates metadata for bookmarks that don't have it yet or had fetch errors.

**Features:**
- Processes new bookmarks without metadata
- Retries failed fetches (up to 3 times)
- Skips non-webpage files (.pdf, .zip, .exe, .mp3, .sh, etc.)
- Handles direct image URLs (caches them)
- Lock file prevents concurrent runs

**Options:**
```
--batch=50      Number of bookmarks to process (default: 50)
--age=7         Refresh if older than X days (default: 7)
--timeout=300   Max runtime in seconds (default: 300)
--verbose       Show detailed output
--dry-run       Preview without making changes
--force-id=123  Force refresh for specific bookmark ID
```

**Cron Schedule:**
```bash
# Every 15 minutes
*/15 * * * * /usr/bin/php /path/to/cron/fetch-meta.php >> /path/to/logs/meta-fetch.log 2>&1
```

**Example:**
```bash
php fetch-meta.php --batch=100 --verbose
```

**For shared hosting with MySQL timeouts (batch loop):**
```bash
for i in {1..30}; do echo "=== Batch $i ==="; php fetch-meta.php --batch=50 --verbose; sleep 3; done
```

---

### cache-images.php

**Purpose:** Downloads and caches external images (meta_image, favicon) locally to prevent:
- Image flickering on page load
- Dependency on external servers
- CORS/privacy issues
- Hotlink blocking

**Features:**
- Finds bookmarks with external image URLs (http...)
- Downloads and caches images locally
- Updates database with local cache URLs
- Resizes images for efficiency
- Lock file prevents concurrent runs

**Options:**
```
--batch=100     Number of bookmarks to process (default: 100)
--verbose       Show detailed output
--dry-run       Preview without making changes
--force         Re-cache even if already cached
```

**Cron Schedule:**
```bash
# Weekly on Sunday at 3 AM (bandwidth efficient)
0 3 * * 0 /usr/bin/php /path/to/cron/cache-images.php >> /path/to/logs/cache-images.log 2>&1

# Or daily if you add many bookmarks
0 3 * * * /usr/bin/php /path/to/cron/cache-images.php >> /path/to/logs/cache-images.log 2>&1
```

**Example:**
```bash
php cache-images.php --batch=200 --verbose
```

**For shared hosting with MySQL timeouts:**
```bash
for i in {1..50}; do echo "=== Batch $i ==="; php cache-images.php --batch=50 --verbose; sleep 3; done
```

---

### refresh-meta.php

**Purpose:** Refreshes metadata for bookmarks that haven't been updated recently (stale data).

**Features:**
- Finds bookmarks older than X days
- Re-fetches metadata from original URL
- Updates title, description, images
- Respects runtime limits for shared hosting

**Configuration (in file):**
```php
$batchSize = 50;      // Process 50 bookmarks per run
$ageThreshold = 7;    // Refresh if older than 7 days
$maxRuntime = 240;    // Max 4 minutes runtime
```

**Cron Schedule:**
```bash
# Every 6 hours
0 */6 * * * /usr/bin/php /path/to/cron/refresh-meta.php >> /path/to/logs/refresh-meta.log 2>&1
```

**Example:**
```bash
php refresh-meta.php
```

---

### cleanup.php

**Purpose:** Removes expired data and cleans up the system.

**What it cleans:**
- Expired cache files (`.cache` files past expiry)
- Orphaned tags (tags not attached to any bookmark)
- Old/expired sessions
- Orphaned image cache files

**Cron Schedule:**
```bash
# Daily at 3 AM
0 3 * * * /usr/bin/php /path/to/cron/cleanup.php >> /path/to/logs/cleanup.log 2>&1
```

**Example:**
```bash
php cleanup.php
```

---

### refetch-bookmark.php

**Purpose:** Manually reset and re-fetch metadata for specific bookmarks by ID. Useful for fixing individual bookmarks or bulk refreshing.

**Features:**
- Refetch single or multiple bookmarks by ID
- Refetch ALL bookmarks with `--all`
- Handles direct image URLs
- Caches images locally
- Works via CLI or Web API (with authentication)

**CLI Options:**
```
--id=N          Bookmark ID(s) to refetch (comma-separated)
--all           Refetch all bookmarks
--batch=N       Batch size when using --all (default: 50)
--verbose       Show detailed output
--help          Show help message
```

**CLI Examples:**
```bash
# Single bookmark
php refetch-bookmark.php --id=123

# Multiple bookmarks
php refetch-bookmark.php --id=123,456,789 --verbose

# Refetch ALL bookmarks (careful!)
php refetch-bookmark.php --all --batch=100 --verbose
```

**Web API (requires API key):**
```
GET /cron/refetch-bookmark.php?id=123&api_key=bm_your_key
GET /cron/refetch-bookmark.php?id=123,456&verbose&api_key=bm_your_key
GET /cron/refetch-bookmark.php?all&batch=50&api_key=bm_your_key
```

**Response (JSON):**
```json
{
  "success": true,
  "processed": 3,
  "succeeded": 2,
  "failed": 1,
  "bookmarks": [
    {"id": 123, "success": true, "message": "Metadata fetched successfully"},
    {"id": 456, "success": true, "message": "Image cached successfully"},
    {"id": 789, "success": false, "message": "Connection timeout"}
  ]
}
```

---

## Hostinger/Shared Hosting Setup

### Via cPanel Cron Jobs

1. Go to **cPanel → Cron Jobs**
2. Add each job with the path to your installation:

```
# Fetch metadata - Every 15 minutes
*/15 * * * * /usr/bin/php /home/username/public_html/cron/fetch-meta.php --batch=30 >> /home/username/logs/meta.log 2>&1

# Cache images - Weekly on Sunday
0 3 * * 0 /usr/bin/php /home/username/public_html/cron/cache-images.php --batch=50 >> /home/username/logs/images.log 2>&1

# Refresh old meta - Every 6 hours
0 */6 * * * /usr/bin/php /home/username/public_html/cron/refresh-meta.php >> /home/username/logs/refresh.log 2>&1

# Cleanup - Daily at 3 AM
0 3 * * * /usr/bin/php /home/username/public_html/cron/cleanup.php >> /home/username/logs/cleanup.log 2>&1
```

### Via SSH (Manual Run)

```bash
cd /home/username/public_html/cron
php fetch-meta.php --batch=50 --verbose
php cache-images.php --batch=100 --verbose
```

### Handling MySQL "Server Has Gone Away" Error

Shared hosting often kills long-running MySQL connections. Use batch loops:

```bash
# Run fetch-meta in small batches
for i in {1..30}; do 
  echo "=== Batch $i ===" 
  php fetch-meta.php --batch=50 --verbose
  sleep 3
done

# Run cache-images in small batches
for i in {1..50}; do 
  echo "=== Batch $i ===" 
  php cache-images.php --batch=50 --verbose
  sleep 3
done
```

---

## Docker Setup

If using Docker, cron jobs can be added to the container or run from host:

### Option 1: Run from Host

```bash
# Add to host crontab
*/15 * * * * docker exec bookmark-app php /var/www/html/cron/fetch-meta.php
0 3 * * 0 docker exec bookmark-app php /var/www/html/cron/cache-images.php
0 3 * * * docker exec bookmark-app php /var/www/html/cron/cleanup.php
```

### Option 2: Add to Docker Container

Create a cron container or add to docker-compose.yml:

```yaml
services:
  cron:
    image: bookmark-manager
    command: crond -f
    volumes:
      - ./cron:/var/www/html/cron
      - ./logs:/var/www/html/logs
```

---

## Troubleshooting

### Job not running?
1. Check PHP path: `which php` → use full path like `/usr/bin/php`
2. Check file permissions: `chmod +x cron/*.php`
3. Check logs for errors: `tail -f /path/to/logs/meta-fetch.log`

### "Another instance is already running"?
Lock file exists from crashed run:
```bash
rm /tmp/bookmark_meta_fetch.lock
rm /tmp/bookmark_image_cache.lock
```

### MySQL "Server has gone away"?
- Reduce batch size: `--batch=30`
- Use batch loop with sleep (see above)
- Script now auto-reconnects: `Database::reconnect()`

### Images not caching?
1. Check `/cache/images/` directory exists and is writable
2. Check cURL is enabled: `php -m | grep curl`
3. Run with `--verbose` to see errors

### Cron output not appearing?
Redirect stderr to log:
```bash
php fetch-meta.php >> /path/to/log.txt 2>&1
```

---

## File Locations

| File | Description |
|------|-------------|
| `cron/fetch-meta.php` | Fetch metadata for new bookmarks |
| `cron/cache-images.php` | Cache external images locally |
| `cron/refresh-meta.php` | Refresh stale metadata |
| `cron/cleanup.php` | Clean expired cache and orphaned data |
| `cron/refetch-bookmark.php` | Manual refetch by ID |
| `cache/images/` | Cached image files |
| `logs/` | Log files (create this directory) |
