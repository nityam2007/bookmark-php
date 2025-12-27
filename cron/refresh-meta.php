<?php
/**
 * Meta Refresh Cron Job
 * Refreshes metadata for bookmarks that haven't been updated recently
 * 
 * Run via cPanel: php /path/to/cron/refresh-meta.php
 * Recommended: Every 6 hours
 * 
 * @package BookmarkManager\Cron
 */

declare(strict_types=1);

// CLI only
if (PHP_SAPI !== 'cli') {
    exit('This script must be run from command line');
}

// Bootstrap
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/config/config.php';
require_once APP_ROOT . '/app/core/Autoloader.php';

use App\Core\Database;
use App\Services\MetaFetcher;

// Configuration
$batchSize = 50;      // Process 50 bookmarks per run
$ageThreshold = 7;    // Refresh if older than 7 days
$maxRuntime = 240;    // Max 4 minutes runtime (leave buffer for shared hosting)

$startTime = time();
$processed = 0;
$updated = 0;
$errors = 0;

echo "Starting meta refresh...\n";

// Get bookmarks that need refresh
$sql = "SELECT id, url, title FROM bookmarks 
        WHERE meta_fetched_at IS NULL 
           OR meta_fetched_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY meta_fetched_at ASC NULLS FIRST
        LIMIT ?";

$bookmarks = Database::fetchAll($sql, [$ageThreshold, $batchSize]);

if (empty($bookmarks)) {
    echo "No bookmarks need refreshing.\n";
    exit(0);
}

echo "Found " . count($bookmarks) . " bookmarks to refresh.\n";

$fetcher = new MetaFetcher();

foreach ($bookmarks as $bookmark) {
    // Check runtime
    if ((time() - $startTime) > $maxRuntime) {
        echo "Max runtime reached. Stopping.\n";
        break;
    }
    
    $processed++;
    echo "[{$processed}] Processing: {$bookmark['url']}\n";
    
    try {
        $meta = $fetcher->fetch($bookmark['url']);
        
        if ($meta['success']) {
            $updateData = [
                'meta_fetched_at' => date('Y-m-d H:i:s')
            ];
            
            // Only update if we got new data
            if ($meta['title'] && empty($bookmark['title'])) {
                $updateData['title'] = $meta['title'];
            }
            if ($meta['description']) {
                $updateData['description'] = $meta['description'];
            }
            if ($meta['meta_image']) {
                $updateData['meta_image'] = $meta['meta_image'];
            }
            if ($meta['favicon']) {
                $updateData['favicon'] = $meta['favicon'];
            }
            
            Database::update('bookmarks', $updateData, 'id = ?', [$bookmark['id']]);
            $updated++;
            echo "  ✓ Updated\n";
        } else {
            echo "  ✗ Failed: {$meta['error']}\n";
            
            // Still update timestamp to avoid retrying too often
            Database::update('bookmarks', 
                ['meta_fetched_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$bookmark['id']]
            );
            $errors++;
        }
    } catch (\Exception $e) {
        echo "  ✗ Error: {$e->getMessage()}\n";
        $errors++;
    }
    
    // Small delay to be nice to servers
    usleep(500000); // 0.5 second
}

$duration = time() - $startTime;

echo "\n";
echo "=== Summary ===\n";
echo "Processed: {$processed}\n";
echo "Updated:   {$updated}\n";
echo "Errors:    {$errors}\n";
echo "Duration:  {$duration}s\n";
