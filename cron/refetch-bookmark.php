#!/usr/bin/env php
<?php
/**
 * Refetch Bookmark Metadata
 * 
 * Reset and re-fetch metadata for specific bookmarks by ID.
 * Similar to fetch-meta.php but for manual/targeted refetching.
 * 
 * USAGE:
 *   php refetch-bookmark.php --id=123
 *   php refetch-bookmark.php --id=123 --verbose
 *   php refetch-bookmark.php --id=123,456,789    (multiple IDs)
 *   php refetch-bookmark.php --all --batch=50    (refetch all bookmarks)
 * 
 * OPTIONS:
 * --id=N          Bookmark ID(s) to refetch (comma-separated)
 * --all           Refetch all bookmarks
 * --batch=N       Batch size when using --all (default: 50)
 * --timeout=300   Max runtime in seconds (default: 300)
 * --verbose       Show detailed output
 * --dry-run       Preview without making changes
 * --help          Show help message
 * 
 * @package BookmarkManager\Cron
 */

declare(strict_types=1);

// ============================================
// ENVIRONMENT CHECKS
// ============================================
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script must be run from command line');
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ============================================
// BOOTSTRAP
// ============================================
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/config/config.php';
require_once APP_ROOT . '/app/core/Autoloader.php';

use App\Core\Database;
use App\Models\Bookmark;
use App\Services\EnhancedMetaFetcher;
use App\Services\ImageCacheService;

// ============================================
// PARSE CLI OPTIONS
// ============================================
$options = getopt('', [
    'id:',
    'all',
    'batch:',
    'timeout:',
    'verbose',
    'dry-run',
    'help'
]);

if (isset($options['help'])) {
    echo <<<HELP
Refetch Bookmark Metadata

Usage: php refetch-bookmark.php [options]

Options:
  --id=N         Bookmark ID to refetch (comma-separated for multiple)
  --all          Refetch all bookmarks
  --batch=N      Batch size when using --all (default: 50)
  --timeout=N    Max runtime in seconds (default: 300)
  --verbose      Show detailed output
  --dry-run      Preview without making changes
  --help         Show this help message

Examples:
  php refetch-bookmark.php --id=123
  php refetch-bookmark.php --id=123,456,789 --verbose
  php refetch-bookmark.php --all --batch=100 --verbose

HELP;
    exit(0);
}

$config = [
    'ids'          => isset($options['id']) ? array_map('intval', explode(',', $options['id'])) : [],
    'all'          => isset($options['all']),
    'batch_size'   => (int)($options['batch'] ?? 50),
    'max_timeout'  => (int)($options['timeout'] ?? 300),
    'verbose'      => isset($options['verbose']),
    'dry_run'      => isset($options['dry-run'])
];

// Validate input
if (empty($config['ids']) && !$config['all']) {
    echo "Error: No bookmark ID specified. Use --id=N or --all\n";
    echo "Run with --help for usage information.\n";
    exit(1);
}

// ============================================
// LOGGING FUNCTIONS
// ============================================
function logInfo(string $message, bool $verboseOnly = false): void
{
    global $config;
    if (!$verboseOnly || $config['verbose']) {
        echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    }
}

function logError(string $message): void
{
    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] ERROR: " . $message . "\n");
}

function logSuccess(string $message): void
{
    echo "[" . date('Y-m-d H:i:s') . "] ✓ " . $message . "\n";
}

function logWarning(string $message): void
{
    echo "[" . date('Y-m-d H:i:s') . "] ⚠ " . $message . "\n";
}

// ============================================
// HELPER FUNCTION: Process Single Bookmark
// Must be defined BEFORE it's called
// ============================================
function processBookmark(
    array $bookmark,
    EnhancedMetaFetcher $fetcher,
    ImageCacheService $imageCache,
    array &$stats
): void {
    global $config;
    
    $id = $bookmark['id'];
    $url = $bookmark['url'];
    
    if ($config['dry_run']) {
        logInfo("  Would refetch meta for ID {$id}", true);
        $stats['skipped']++;
        return;
    }
    
    try {
        // Fetch metadata
        logInfo("  Fetching metadata...", true);
        $meta = $fetcher->fetch($url);
        $stats['processed']++;
        
        // Check if it's a direct image URL
        $contentType = $meta['content_type'] ?? '';
        $isDirectImage = str_starts_with($contentType, 'image/');
        
        if ($isDirectImage) {
            // Handle direct image URLs
            logInfo("  Direct image detected, caching...", true);
            $cachedImage = $imageCache->cacheImageForOffload($url);
            
            $urlPath = parse_url($url, PHP_URL_PATH);
            $filename = $urlPath ? basename($urlPath) : 'Image';
            $host = parse_url($url, PHP_URL_HOST) ?? '';
            
            Bookmark::updateMeta($id, [
                'meta_title'        => $filename,
                'meta_description'  => 'Image from ' . $host,
                'meta_site_name'    => $host,
                'meta_type'         => 'image',
                'meta_image'        => $cachedImage ?: null,
                'http_status'       => $meta['http_status'] ?? null,
                'content_type'      => $contentType,
                'meta_fetch_error'  => null,
                'meta_fetched_at'   => date('Y-m-d H:i:s')
            ]);
            
            $stats['success']++;
            logSuccess("Image cached: {$filename}");
            
        } elseif ($meta['success']) {
            // Normal webpage - cache images locally
            $cachedFavicon = null;
            $cachedImage = null;
            
            if (!empty($meta['favicon'])) {
                logInfo("  Caching favicon...", true);
                $cachedFavicon = $imageCache->getCachedUrl($meta['favicon'], 'favicon');
            }
            
            if (!empty($meta['meta_image'])) {
                logInfo("  Caching meta image...", true);
                $cachedImage = $imageCache->getCachedUrl($meta['meta_image'], 'image');
            }
            
            // Update bookmark using updateMeta (same as fetch-meta.php)
            Bookmark::updateMeta($id, [
                'meta_title'        => $meta['meta_title'],
                'meta_description'  => $meta['meta_description'],
                'meta_site_name'    => $meta['meta_site_name'],
                'meta_type'         => $meta['meta_type'],
                'meta_author'       => $meta['meta_author'],
                'meta_keywords'     => $meta['meta_keywords'],
                'meta_locale'       => $meta['meta_locale'],
                'meta_twitter_card' => $meta['meta_twitter_card'] ?? null,
                'meta_twitter_site' => $meta['meta_twitter_site'] ?? null,
                'meta_image'        => $cachedImage ?? $meta['meta_image'],
                'favicon'           => $cachedFavicon ?? $meta['favicon'],
                'http_status'       => $meta['http_status'],
                'content_type'      => $meta['content_type'],
                'meta_fetch_error'  => null,
                'meta_fetched_at'   => date('Y-m-d H:i:s')
            ]);
            
            $stats['success']++;
            $title = $meta['meta_title'] ?? 'No title';
            $fetchTime = $meta['fetch_time_ms'] ?? 0;
            logSuccess("Fetched: {$title} ({$fetchTime}ms)");
            
            if ($config['verbose']) {
                logInfo("  Title: " . ($meta['meta_title'] ?? 'N/A'), true);
                logInfo("  Description: " . mb_substr($meta['meta_description'] ?? 'N/A', 0, 50) . "...", true);
                logInfo("  Image: " . ($cachedImage ? 'Cached' : ($meta['meta_image'] ? 'Yes' : 'No')), true);
                logInfo("  Favicon: " . ($cachedFavicon ?? $meta['favicon'] ?? 'N/A'), true);
            }
            
        } else {
            // Failed fetch
            $error = $meta['error'] ?? 'Unknown error';
            Bookmark::updateMeta($id, [
                'meta_fetch_error' => mb_substr($error, 0, 255),
                'http_status'      => $meta['http_status'] ?? null,
                'meta_fetched_at'  => date('Y-m-d H:i:s')
            ]);
            
            $stats['failed']++;
            logWarning("Failed: {$error} (HTTP " . ($meta['http_status'] ?? '?') . ")");
        }
        
    } catch (\Exception $e) {
        $stats['failed']++;
        logError("Exception for ID {$id}: {$e->getMessage()}");
        
        // Update to record error
        Bookmark::updateMeta($id, [
            'meta_fetch_error' => mb_substr($e->getMessage(), 0, 255),
            'meta_fetched_at'  => date('Y-m-d H:i:s')
        ]);
    }
    
    // Small delay between requests
    usleep(200000); // 200ms
}

// ============================================
// MAIN EXECUTION
// ============================================
$startTime = time();
$stats = [
    'processed' => 0,
    'success'   => 0,
    'failed'    => 0,
    'skipped'   => 0,
    'not_found' => 0
];

logInfo("========================================");
logInfo("Refetch Bookmark Job Started");
if ($config['all']) {
    logInfo("Mode: ALL bookmarks (batch: {$config['batch_size']})");
} else {
    logInfo("Mode: Specific IDs (" . count($config['ids']) . " bookmark(s))");
    logInfo("IDs: " . implode(', ', $config['ids']));
}
logInfo("Timeout: {$config['max_timeout']}s");
if ($config['dry_run']) {
    logWarning("DRY RUN MODE - No changes will be made");
}
logInfo("========================================");

try {
    // Initialize services
    logInfo("Initializing services...", true);
    $fetcher = new EnhancedMetaFetcher();
    $imageCache = new ImageCacheService();
    logInfo("Services initialized.", true);
    
    // Get bookmarks to process
    if ($config['all']) {
        // Process all bookmarks in batches
        $offset = 0;
        
        while (true) {
            // Check timeout
            $elapsed = time() - $startTime;
            if ($elapsed >= $config['max_timeout']) {
                logWarning("Timeout reached ({$config['max_timeout']}s). Stopping.");
                break;
            }
            
            $sql = "SELECT id, url FROM bookmarks ORDER BY id LIMIT ? OFFSET ?";
            $bookmarks = Database::fetchAll($sql, [$config['batch_size'], $offset]);
            
            if (empty($bookmarks)) {
                logInfo("No more bookmarks to process.");
                break;
            }
            
            logInfo("Processing batch: " . ($offset + 1) . " to " . ($offset + count($bookmarks)));
            
            foreach ($bookmarks as $bookmark) {
                processBookmark($bookmark, $fetcher, $imageCache, $stats);
            }
            
            $offset += $config['batch_size'];
            
            // Reconnect to prevent MySQL timeout
            if (method_exists(Database::class, 'reconnect')) {
                Database::reconnect();
            }
        }
        
    } else {
        // Process specific IDs
        $totalBookmarks = count($config['ids']);
        logInfo("Processing {$totalBookmarks} bookmark(s)...");
        
        foreach ($config['ids'] as $index => $id) {
            // Check timeout
            $elapsed = time() - $startTime;
            if ($elapsed >= $config['max_timeout']) {
                logWarning("Timeout reached ({$config['max_timeout']}s). Stopping.");
                break;
            }
            
            $progress = $index + 1;
            
            // Find bookmark using Bookmark::find (same as fetch-meta.php)
            logInfo("[{$progress}/{$totalBookmarks}] Looking up bookmark ID {$id}...", true);
            $bookmark = Bookmark::find($id);
            
            if (!$bookmark) {
                logWarning("[{$progress}/{$totalBookmarks}] ID {$id}: Bookmark not found");
                $stats['not_found']++;
                continue;
            }
            
            logInfo("[{$progress}/{$totalBookmarks}] Processing: {$bookmark['url']}");
            processBookmark($bookmark, $fetcher, $imageCache, $stats);
        }
    }

} catch (\Exception $e) {
    logError("Fatal error: {$e->getMessage()}");
    logError("Stack trace:\n{$e->getTraceAsString()}");
    exit(1);
}

// ============================================
// FINAL REPORT
// ============================================
$elapsed = time() - $startTime;
logInfo("========================================");
logInfo("Refetch Job Completed");
logInfo("----------------------------------------");
logInfo("Duration: {$elapsed} seconds");
logInfo("Processed: {$stats['processed']}");
logInfo("Success: {$stats['success']}");
logInfo("Failed: {$stats['failed']}");
if ($stats['skipped'] > 0) {
    logInfo("Skipped: {$stats['skipped']}");
}
if ($stats['not_found'] > 0) {
    logInfo("Not Found: {$stats['not_found']}");
}
logInfo("========================================");

exit($stats['failed'] > 0 ? 1 : 0);
