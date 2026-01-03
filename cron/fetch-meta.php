#!/usr/bin/env php
<?php
/**
 * Meta Refresh Cron Job (Production-Ready)
 * 
 * Fetches and updates metadata for bookmarks:
 * - New bookmarks without meta
 * - Old bookmarks (older than X days)
 * - Failed fetches (retry up to 3 times)
 * 
 * CRON SETUP:
 * Run every 15 minutes:
 * 0,15,30,45 * * * * /usr/bin/php /path/to/cron/fetch-meta.php >> /path/to/logs/meta-fetch.log 2>&1
 * 
  * OPTIONS:
 * --batch=50      Number of bookmarks to process per run (default: 50)
 * --age=7         Refresh if older than X days (default: 7)
 * --timeout=300   Max runtime in seconds (default: 300)
 * --verbose       Show detailed output
 * --dry-run       Show what would be done without doing it
 * --force-id=123  Force refresh for specific bookmark ID
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

// Prevent concurrent runs
$lockFile = sys_get_temp_dir() . '/bookmark_meta_fetch.lock';
$lockHandle = fopen($lockFile, 'w');
if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Another instance is already running. Exiting.\n";
    exit(0);
}

// ============================================
// BOOTSTRAP
// ============================================
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/config/config.php';
require_once APP_ROOT . '/app/core/Autoloader.php';

use App\Models\Bookmark;
use App\Services\EnhancedMetaFetcher;
use App\Services\ImageCacheService;
use App\Core\Database;

// ============================================
// PARSE CLI OPTIONS
// ============================================
$options = getopt('', [
    'batch:',
    'age:',
    'timeout:',
    'verbose',
    'dry-run',
    'force-id:',
    'help'
]);

if (isset($options['help'])) {
    echo <<<HELP
Bookmark Meta Fetcher - Production Cron Job

Usage: php fetch-meta.php [options]

Options:
  --batch=N      Number of bookmarks to process (default: 50)
  --age=N        Refresh bookmarks older than N days (default: 7)
  --timeout=N    Max runtime in seconds (default: 300)
  --verbose      Show detailed output
  --dry-run      Preview without making changes
  --force-id=N   Force refresh for specific bookmark ID
  --help         Show this help message

Examples:
  php fetch-meta.php --batch=100 --verbose
  php fetch-meta.php --force-id=123
  php fetch-meta.php --dry-run

HELP;
    exit(0);
}

$config = [
    'batch_size'   => (int)($options['batch'] ?? 250),
    'age_days'     => (int)($options['age'] ?? 7),
    'max_timeout'  => (int)($options['timeout'] ?? 300),
    'verbose'      => isset($options['verbose']),
    'dry_run'      => isset($options['dry-run']),
    'force_id'     => isset($options['force-id']) ? (int)$options['force-id'] : null
];

// ============================================
// LOGGING FUNCTIONS
// ============================================
function logInfo(string $message, bool $verbose = false): void
{
    global $config;
    if (!$verbose || $config['verbose']) {
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
// MAIN EXECUTION
// ============================================
$startTime = time();
$stats = [
    'processed' => 0,
    'success'   => 0,
    'failed'    => 0,
    'skipped'   => 0
];

logInfo("========================================");
logInfo("Meta Fetch Job Started");
logInfo("Batch: {$config['batch_size']}, Age: {$config['age_days']} days, Timeout: {$config['max_timeout']}s");
if ($config['dry_run']) {
    logWarning("DRY RUN MODE - No changes will be made");
}
logInfo("========================================");

try {
    // Get bookmarks to process
    if ($config['force_id']) {
        $bookmark = Bookmark::find($config['force_id']);
        $bookmarks = $bookmark ? [$bookmark] : [];
        logInfo("Force fetching bookmark ID: {$config['force_id']}");
    } else {
        $bookmarks = Bookmark::getNeedingMetaRefresh(
            $config['batch_size'],
            $config['age_days']
        );
    }

    $totalBookmarks = count($bookmarks);
    
    if ($totalBookmarks === 0) {
        logInfo("No bookmarks need meta refresh.");
        exit(0);
    }

    logInfo("Found {$totalBookmarks} bookmarks to process.");

    // Initialize fetcher and image cache
    $fetcher = new EnhancedMetaFetcher();
    $imageCache = new ImageCacheService();

    foreach ($bookmarks as $index => $bookmark) {
        // Check timeout
        $elapsed = time() - $startTime;
        if ($elapsed >= $config['max_timeout']) {
            logWarning("Timeout reached ({$config['max_timeout']}s). Stopping.");
            break;
        }

        $remaining = $config['max_timeout'] - $elapsed;
        $progress = $index + 1;
        
        // Skip non-webpage URLs (scripts, binaries, media files, etc.)
        $skipExtensions = [
            // Scripts & binaries
            'sh', 'bash', 'exe', 'msi', 'dmg', 'pkg', 'deb', 'rpm', 'AppImage',
            // Archives
            'zip', 'tar', 'gz', 'bz2', 'xz', '7z', 'rar', 'tgz',
            // Media files
            'mp3', 'mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm', 'wav', 'flac', 'ogg',
            // Documents (usually no meta)
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods',
            // Images (already handled separately)
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'bmp', 'tiff',
            // Other
            'iso', 'bin', 'img', 'torrent'
        ];
        
        $urlPath = parse_url($bookmark['url'], PHP_URL_PATH) ?? '';
        $extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
        
        if (in_array($extension, $skipExtensions)) {
            logInfo("[{$progress}/{$totalBookmarks}] Skipping (file): {$bookmark['url']}", true);
            $stats['skipped']++;
            // Mark as fetched so we don't retry
            Bookmark::updateMeta($bookmark['id'], [
                'meta_title'       => pathinfo($urlPath, PATHINFO_BASENAME),
                'meta_description' => "Direct file download (.{$extension})",
                'meta_type'        => 'file',
                'meta_fetched_at'  => date('Y-m-d H:i:s'),
                'meta_fetch_error' => null
            ]);
            continue;
        }
        
        logInfo("[{$progress}/{$totalBookmarks}] Processing: {$bookmark['url']}", true);
        logInfo("  Time remaining: {$remaining}s", true);

        if ($config['dry_run']) {
            logInfo("  Would fetch meta for bookmark ID {$bookmark['id']}", true);
            $stats['skipped']++;
            continue;
        }

        // Fetch metadata
        try {
            $meta = $fetcher->fetch($bookmark['url']);
            $stats['processed']++;

            if ($meta['success']) {
                // Cache images locally
                $cachedFavicon = null;
                $cachedImage = null;
                
                if (!empty($meta['favicon'])) {
                    $cachedFavicon = $imageCache->getCachedUrl($meta['favicon'], 'favicon');
                    if ($config['verbose'] && $cachedFavicon) {
                        logInfo("  Cached favicon: {$cachedFavicon}", true);
                    }
                }
                
                if (!empty($meta['meta_image'])) {
                    $cachedImage = $imageCache->getCachedUrl($meta['meta_image'], 'image');
                    if ($config['verbose'] && $cachedImage) {
                        logInfo("  Cached meta image: {$cachedImage}", true);
                    }
                }
                
                // Update bookmark with cached image paths if available
                Bookmark::updateMeta($bookmark['id'], [
                    'meta_title'        => $meta['meta_title'],
                    'meta_description'  => $meta['meta_description'],
                    'meta_site_name'    => $meta['meta_site_name'],
                    'meta_type'         => $meta['meta_type'],
                    'meta_author'       => $meta['meta_author'],
                    'meta_keywords'     => $meta['meta_keywords'],
                    'meta_locale'       => $meta['meta_locale'],
                    'meta_twitter_card' => $meta['meta_twitter_card'],
                    'meta_twitter_site' => $meta['meta_twitter_site'],
                    'meta_image'        => $cachedImage ?? $meta['meta_image'],
                    'favicon'           => $cachedFavicon ?? $meta['favicon'],
                    'http_status'       => $meta['http_status'],
                    'content_type'      => $meta['content_type'],
                    'meta_fetch_error'  => null,
                    'meta_fetched_at'   => date('Y-m-d H:i:s')
                ]);

                // Save images
                if (!empty($meta['images'])) {
                    Bookmark::saveMetaImages($bookmark['id'], $meta['images']);
                }

                // Log fetch
                Bookmark::logMetaFetch($bookmark['id'], $bookmark['url'], $meta);

                $stats['success']++;
                logSuccess("Fetched: {$meta['meta_title']} ({$meta['fetch_time_ms']}ms)");
                
                if ($config['verbose']) {
                    logInfo("  Title: " . ($meta['meta_title'] ?? 'N/A'), true);
                    logInfo("  Description: " . mb_substr($meta['meta_description'] ?? 'N/A', 0, 50) . "...", true);
                    logInfo("  Image: " . ($meta['meta_image'] ? 'Yes' : 'No'), true);
                    logInfo("  Favicon: " . ($meta['favicon'] ?? 'N/A'), true);
                }
            } else {
                // Update with error
                Bookmark::updateMeta($bookmark['id'], [
                    'meta_fetch_error' => mb_substr($meta['error'] ?? 'Unknown error', 0, 255),
                    'http_status'      => $meta['http_status'],
                    'meta_fetched_at'  => date('Y-m-d H:i:s')
                ]);

                // Log fetch
                Bookmark::logMetaFetch($bookmark['id'], $bookmark['url'], $meta);

                $stats['failed']++;
                logWarning("Failed: {$meta['error']} (HTTP {$meta['http_status']})");
            }

        } catch (\Exception $e) {
            $stats['failed']++;
            logError("Exception: {$e->getMessage()}");
            
            // Still update to prevent infinite retries
            Bookmark::updateMeta($bookmark['id'], [
                'meta_fetch_error' => mb_substr($e->getMessage(), 0, 255),
                'meta_fetched_at'  => date('Y-m-d H:i:s')
            ]);
        }

        // Small delay between requests
        usleep(200000); // 200ms
    }

} catch (\Exception $e) {
    logError("Fatal error: {$e->getMessage()}");
    logError("Stack trace:\n{$e->getTraceAsString()}");
    exit(1);
} finally {
    // Release lock
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    @unlink($lockFile);
}

// ============================================
// FINAL REPORT
// ============================================
$elapsed = time() - $startTime;
logInfo("========================================");
logInfo("Meta Fetch Job Completed");
logInfo("----------------------------------------");
logInfo("Duration: {$elapsed} seconds");
logInfo("Processed: {$stats['processed']}");
logInfo("Success: {$stats['success']}");
logInfo("Failed: {$stats['failed']}");
if ($stats['skipped'] > 0) {
    logInfo("Skipped (dry-run): {$stats['skipped']}");
}
logInfo("========================================");

exit($stats['failed'] > 0 ? 1 : 0);
