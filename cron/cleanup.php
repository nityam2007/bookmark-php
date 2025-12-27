<?php
/**
 * Cleanup Cron Job
 * Removes expired cache, old sessions, orphaned data
 * 
 * Run via cPanel: php /path/to/cron/cleanup.php
 * Recommended: Daily at 3 AM
 * 
 * @package BookmarkManager\Cron
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('This script must be run from command line');
}

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/config/config.php';
require_once APP_ROOT . '/app/core/Autoloader.php';

use App\Core\Database;
use App\Services\CacheService;

$stats = [
    'cache_files' => 0,
    'orphaned_tags' => 0,
    'old_sessions' => 0
];

echo "Starting cleanup...\n\n";

// 1. Clear expired cache
echo "1. Clearing expired cache...\n";
$cacheDir = APP_ROOT . '/cache';

if (is_dir($cacheDir)) {
    $now = time();
    $files = glob($cacheDir . '/*.cache');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if ($data && isset($data['expires']) && $data['expires'] < $now) {
            unlink($file);
            $stats['cache_files']++;
            echo "  Removed: " . basename($file) . "\n";
        }
    }
}
echo "  Removed {$stats['cache_files']} expired cache files.\n\n";

// 2. Remove orphaned tags (not attached to any bookmark)
echo "2. Removing orphaned tags...\n";

$sql = "DELETE FROM tags 
        WHERE id NOT IN (SELECT DISTINCT tag_id FROM bookmark_tags)";
$stmt = Database::execute($sql);
$stats['orphaned_tags'] = $stmt->rowCount();

echo "  Removed {$stats['orphaned_tags']} orphaned tags.\n\n";

// 3. Clean up old PHP sessions
echo "3. Cleaning old sessions...\n";

$sessionPath = session_save_path() ?: '/tmp';
$maxAge = 86400 * 7; // 7 days

if (is_dir($sessionPath)) {
    $files = glob($sessionPath . '/sess_*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
            @unlink($file);
            $stats['old_sessions']++;
        }
    }
}
echo "  Removed {$stats['old_sessions']} old session files.\n\n";

// 4. Optimize database tables
echo "4. Optimizing database tables...\n";

$tables = ['bookmarks', 'categories', 'tags', 'bookmark_tags', 'users'];
foreach ($tables as $table) {
    try {
        Database::execute("OPTIMIZE TABLE `{$table}`");
        echo "  ✓ {$table}\n";
    } catch (\Exception $e) {
        echo "  ✗ {$table}: {$e->getMessage()}\n";
    }
}

echo "\n=== Cleanup Complete ===\n";
echo "Cache files removed: {$stats['cache_files']}\n";
echo "Orphaned tags removed: {$stats['orphaned_tags']}\n";
echo "Old sessions removed: {$stats['old_sessions']}\n";
