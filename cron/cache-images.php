#!/usr/bin/env php
<?php
/**
 * Image Cache Cron Job
 * 
 * Caches external images locally for bookmarks that already have
 * meta_image or favicon URLs but they are still external.
 * 
 * This prevents:
 * - Image flickering when loading cards
 * - Dependency on external servers being up
 * - CORS/privacy issues with external image requests
 * 
 * CRON SETUP:
 * Run once per hour:
 * 0 * * * * /usr/bin/php /path/to/cron/cache-images.php >> /path/to/logs/cache-images.log 2>&1
 * 
 * OPTIONS:
 * --batch=100     Number of bookmarks to process per run (default: 100)
 * --verbose       Show detailed output
 * --dry-run       Show what would be done without doing it
 * --force         Re-cache even if already cached
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
$lockFile = sys_get_temp_dir() . '/bookmark_image_cache.lock';
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

use App\Core\Database;
use App\Services\ImageCacheService;

// ============================================
// PARSE CLI OPTIONS
// ============================================
$options = getopt('', [
    'batch:',
    'verbose',
    'dry-run',
    'force',
    'help'
]);

if (isset($options['help'])) {
    echo <<<HELP
Image Cache Job - Cache external images locally

Usage: php cache-images.php [options]

Options:
  --batch=N      Number of bookmarks to process (default: 100)
  --verbose      Show detailed output
  --dry-run      Preview without making changes
  --force        Re-cache even if already cached
  --help         Show this help message

Examples:
  php cache-images.php --batch=200 --verbose
  php cache-images.php --force --verbose

HELP;
    exit(0);
}

$config = [
    'batch_size' => (int)($options['batch'] ?? 100),
    'verbose'    => isset($options['verbose']),
    'dry_run'    => isset($options['dry-run']),
    'force'      => isset($options['force'])
];

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

function logSuccess(string $message): void
{
    echo "[" . date('Y-m-d H:i:s') . "] ✓ " . $message . "\n";
}

function logWarning(string $message): void
{
    echo "[" . date('Y-m-d H:i:s') . "] ⚠ " . $message . "\n";
}

function logError(string $message): void
{
    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] ERROR: " . $message . "\n");
}

// ============================================
// MAIN EXECUTION
// ============================================
$startTime = time();
$stats = [
    'processed' => 0,
    'cached'    => 0,
    'failed'    => 0,
    'skipped'   => 0
];

logInfo("========================================");
logInfo("Image Cache Job Started");
logInfo("Batch: {$config['batch_size']}, Force: " . ($config['force'] ? 'Yes' : 'No'));
if ($config['dry_run']) {
    logWarning("DRY RUN MODE - No changes will be made");
}
logInfo("========================================");

try {
    // Get bookmarks with external images
    // External = starts with http:// or https:// (not /cache/)
    $sql = "SELECT id, url, favicon, meta_image 
            FROM bookmarks 
            WHERE (
                (favicon IS NOT NULL AND favicon != '' AND favicon LIKE 'http%')
                OR (meta_image IS NOT NULL AND meta_image != '' AND meta_image LIKE 'http%')
            )
            ORDER BY updated_at DESC
            LIMIT ?";
    
    $bookmarks = Database::fetchAll($sql, [$config['batch_size']]);
    $total = count($bookmarks);
    
    if ($total === 0) {
        logInfo("No bookmarks with external images found.");
        exit(0);
    }
    
    logInfo("Found {$total} bookmarks with external images.");
    
    $imageCache = new ImageCacheService();
    
    foreach ($bookmarks as $index => $bookmark) {
        $progress = $index + 1;
        logInfo("[{$progress}/{$total}] Processing bookmark ID {$bookmark['id']}", true);
        
        $updates = [];
        $cachedAny = false;
        
        // Cache favicon
        if (!empty($bookmark['favicon']) && str_starts_with($bookmark['favicon'], 'http')) {
            logInfo("  Caching favicon: {$bookmark['favicon']}", true);
            
            if (!$config['dry_run']) {
                $cachedFavicon = $imageCache->getCachedUrl($bookmark['favicon'], 'favicon');
                if ($cachedFavicon) {
                    $updates['favicon'] = $cachedFavicon;
                    $cachedAny = true;
                    logInfo("  -> Cached as: {$cachedFavicon}", true);
                }
            } else {
                logInfo("  -> Would cache favicon", true);
            }
        }
        
        // Cache meta image
        if (!empty($bookmark['meta_image']) && str_starts_with($bookmark['meta_image'], 'http')) {
            logInfo("  Caching meta image: {$bookmark['meta_image']}", true);
            
            if (!$config['dry_run']) {
                $cachedImage = $imageCache->getCachedUrl($bookmark['meta_image'], 'image');
                if ($cachedImage) {
                    $updates['meta_image'] = $cachedImage;
                    $cachedAny = true;
                    logInfo("  -> Cached as: {$cachedImage}", true);
                }
            } else {
                logInfo("  -> Would cache meta_image", true);
            }
        }
        
        // Update database
        if (!empty($updates) && !$config['dry_run']) {
            $setClauses = [];
            $params = [];
            foreach ($updates as $col => $val) {
                $setClauses[] = "{$col} = ?";
                $params[] = $val;
            }
            $params[] = $bookmark['id'];
            
            $updateSql = "UPDATE bookmarks SET " . implode(', ', $setClauses) . " WHERE id = ?";
            Database::execute($updateSql, $params);
            
            $stats['cached']++;
            logSuccess("Updated bookmark ID {$bookmark['id']}");
        } elseif ($cachedAny) {
            $stats['cached']++;
        }
        
        if (!$cachedAny && !$config['dry_run']) {
            $stats['skipped']++;
        }
        
        $stats['processed']++;
        
        // Small delay
        usleep(50000); // 50ms
    }
    
} catch (\Exception $e) {
    logError("Fatal error: " . $e->getMessage());
    exit(1);
}

// ============================================
// SUMMARY
// ============================================
$elapsed = time() - $startTime;

logInfo("========================================");
logInfo("Image Cache Job Completed");
logInfo("========================================");
logInfo("Runtime: {$elapsed}s");
logInfo("Processed: {$stats['processed']}");
logInfo("Cached: {$stats['cached']}");
logInfo("Skipped: {$stats['skipped']}");

// Release lock
flock($lockHandle, LOCK_UN);
fclose($lockHandle);

exit(0);
