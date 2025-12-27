<?php
/**
 * Cache Service
 * JSON-based caching for shared hosting
 * 
 * @package BookmarkManager\Services
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class CacheService
{
    private string $cacheDir;
    private bool $useDatabase;

    public function __construct(bool $useDatabase = true)
    {
        $this->cacheDir = CACHE_PATH;
        $this->useDatabase = $useDatabase;
        
        if (!$useDatabase && !is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get cached value
     */
    public function get(string $key): mixed
    {
        if ($this->useDatabase) {
            return $this->getFromDatabase($key);
        }
        return $this->getFromFile($key);
    }

    /**
     * Set cache value
     */
    public function set(string $key, mixed $value, int $ttl = 300): bool
    {
        if ($this->useDatabase) {
            return $this->setToDatabase($key, $value, $ttl);
        }
        return $this->setToFile($key, $value, $ttl);
    }

    /**
     * Delete cache entry
     */
    public function delete(string $key): bool
    {
        if ($this->useDatabase) {
            return $this->deleteFromDatabase($key);
        }
        return $this->deleteFromFile($key);
    }

    /**
     * Clear cache by prefix
     */
    public function clear(string $prefix = ''): int
    {
        if ($this->useDatabase) {
            return $this->clearFromDatabase($prefix);
        }
        return $this->clearFromFile($prefix);
    }

    // Database cache methods
    private function getFromDatabase(string $key): mixed
    {
        $sql = "SELECT results FROM search_cache 
                WHERE cache_key = ? AND expires_at > NOW() 
                LIMIT 1";
        
        $result = Database::fetchColumn($sql, [hash('sha256', $key)]);
        
        if ($result === false) {
            return null;
        }

        return json_decode($result, true);
    }

    private function setToDatabase(string $key, mixed $value, int $ttl): bool
    {
        $hash = hash('sha256', $key);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        
        // Use REPLACE to handle duplicates
        $sql = "REPLACE INTO search_cache (cache_key, query, results, result_count, expires_at) 
                VALUES (?, ?, ?, ?, ?)";
        
        $json = json_encode($value);
        $count = is_array($value) && isset($value['total']) ? $value['total'] : 0;
        
        Database::execute($sql, [$hash, $key, $json, $count, $expiresAt]);
        return true;
    }

    private function deleteFromDatabase(string $key): bool
    {
        return Database::delete('search_cache', 'cache_key = ?', [hash('sha256', $key)]) > 0;
    }

    private function clearFromDatabase(string $prefix): int
    {
        if (empty($prefix)) {
            return Database::execute("DELETE FROM search_cache")->rowCount();
        }
        
        return Database::delete('search_cache', 'query LIKE ?', [$prefix . '%']);
    }

    // File cache methods (fallback)
    private function getFromFile(string $key): mixed
    {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (!$data || $data['expires'] < time()) {
            @unlink($file);
            return null;
        }

        return $data['value'];
    }

    private function setToFile(string $key, mixed $value, int $ttl): bool
    {
        $file = $this->getCacheFile($key);
        
        $data = [
            'expires' => time() + $ttl,
            'value'   => $value
        ];

        return file_put_contents($file, json_encode($data), LOCK_EX) !== false;
    }

    private function deleteFromFile(string $key): bool
    {
        $file = $this->getCacheFile($key);
        return file_exists($file) && @unlink($file);
    }

    private function clearFromFile(string $prefix): int
    {
        $count = 0;
        $pattern = $this->cacheDir . '/' . ($prefix ? $prefix . '*' : '*') . '.cache';
        
        foreach (glob($pattern) as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }

        return $count;
    }

    private function getCacheFile(string $key): string
    {
        return $this->cacheDir . '/' . hash('sha256', $key) . '.cache';
    }

    /**
     * Cleanup expired cache entries
     */
    public function cleanup(): int
    {
        if ($this->useDatabase) {
            return Database::delete('search_cache', 'expires_at < NOW()');
        }

        $count = 0;
        foreach (glob($this->cacheDir . '/*.cache') as $file) {
            $content = @file_get_contents($file);
            $data = @json_decode($content, true);
            
            if (!$data || $data['expires'] < time()) {
                if (@unlink($file)) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
