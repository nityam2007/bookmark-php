<?php
/**
 * Image Cache Service
 * Caches external images locally to improve performance and reliability
 * 
 * @package BookmarkManager\Services
 */

declare(strict_types=1);

namespace App\Services;

class ImageCacheService
{
    private string $cacheDir;
    private int $maxAge = 86400 * 7; // 7 days
    private int $maxWidth = 800;
    private int $maxHeight = 600;
    private int $faviconSize = 64;
    private int $timeout = 10;
    
    // Supported image types
    private array $supportedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
    ];

    public function __construct()
    {
        $this->cacheDir = APP_ROOT . '/cache/images';
        $this->ensureCacheDir();
    }

    /**
     * Get cached image URL (caches if needed)
     */
    public function getCachedUrl(string $url, string $type = 'image'): ?string
    {
        if (empty($url)) {
            return null;
        }

        $hash = $this->getHash($url, $type);
        $cachedFile = $this->getCachedFile($hash);
        
        // Return cached if exists and not expired
        if ($cachedFile && $this->isValid($cachedFile)) {
            return '/img/cache.php?file=' . urlencode(basename($cachedFile));
        }
        
        // Download and cache
        $cached = $this->downloadAndCache($url, $hash, $type);
        
        return $cached ? '/img/cache.php?file=' . urlencode(basename($cached)) : null;
    }

    /**
     * Cache image for a bookmark (both favicon and meta_image)
     */
    public function cacheBookmarkImages(int $bookmarkId, ?string $faviconUrl, ?string $metaImageUrl): array
    {
        $result = [
            'favicon_cached'    => null,
            'meta_image_cached' => null,
        ];

        if ($faviconUrl) {
            $result['favicon_cached'] = $this->getCachedUrl($faviconUrl, 'favicon');
        }

        if ($metaImageUrl) {
            $result['meta_image_cached'] = $this->getCachedUrl($metaImageUrl, 'image');
        }

        return $result;
    }

    /**
     * Download and cache an image
     */
    private function downloadAndCache(string $url, string $hash, string $type): ?string
    {
        try {
            // Validate URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return null;
            }

            // Download with cURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; BookmarkBot/1.0)',
                CURLOPT_HTTPHEADER     => [
                    'Accept: image/*',
                ],
            ]);

            $data = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if ($httpCode !== 200 || empty($data)) {
                return null;
            }

            // Determine extension from content type
            $contentType = explode(';', $contentType)[0];
            $ext = $this->supportedTypes[$contentType] ?? null;
            
            if (!$ext) {
                // Try to detect from data
                $ext = $this->detectExtension($data);
            }

            if (!$ext) {
                return null;
            }

            $filename = $hash . '.' . $ext;
            $filepath = $this->cacheDir . '/' . $filename;

            // Process image (resize if needed, except SVG and ICO)
            if ($type === 'image' && !in_array($ext, ['svg', 'ico'])) {
                $data = $this->processImage($data, $this->maxWidth, $this->maxHeight);
            } elseif ($type === 'favicon' && !in_array($ext, ['svg', 'ico'])) {
                $data = $this->processImage($data, $this->faviconSize, $this->faviconSize);
            }

            if ($data === null) {
                return null;
            }

            // Save
            file_put_contents($filepath, $data);

            return $filepath;
        } catch (\Throwable $e) {
            error_log("ImageCache error for {$url}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process/resize image
     */
    private function processImage(string $data, int $maxWidth, int $maxHeight): ?string
    {
        try {
            $image = @imagecreatefromstring($data);
            if (!$image) {
                return $data; // Return original if can't process
            }

            $width = imagesx($image);
            $height = imagesy($image);

            // Only resize if larger than max
            if ($width > $maxWidth || $height > $maxHeight) {
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                
                // Preserve transparency for PNG
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }

            // Output to string
            ob_start();
            imagepng($image, null, 8);
            $result = ob_get_clean();
            imagedestroy($image);

            return $result ?: null;
        } catch (\Throwable $e) {
            return $data; // Return original on error
        }
    }

    /**
     * Detect image extension from binary data
     */
    private function detectExtension(string $data): ?string
    {
        $signatures = [
            'jpg'  => ["\xFF\xD8\xFF"],
            'png'  => ["\x89PNG\r\n\x1a\n"],
            'gif'  => ["GIF87a", "GIF89a"],
            'webp' => ["RIFF"],
            'ico'  => ["\x00\x00\x01\x00"],
            'svg'  => ["<svg", "<?xml"],
        ];

        foreach ($signatures as $ext => $sigs) {
            foreach ($sigs as $sig) {
                if (str_starts_with($data, $sig)) {
                    return $ext;
                }
            }
        }

        // Check for SVG with whitespace
        if (preg_match('/^\s*(<\?xml|<svg)/i', $data)) {
            return 'svg';
        }

        return null;
    }

    /**
     * Get hash for URL
     */
    private function getHash(string $url, string $type): string
    {
        return $type . '_' . md5($url);
    }

    /**
     * Get cached file if exists
     */
    private function getCachedFile(string $hash): ?string
    {
        $pattern = $this->cacheDir . '/' . $hash . '.*';
        $files = glob($pattern);
        
        return $files[0] ?? null;
    }

    /**
     * Check if cached file is still valid
     */
    private function isValid(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            return false;
        }

        $age = time() - filemtime($filepath);
        return $age < $this->maxAge;
    }

    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDir(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        // Create .htaccess to allow direct access
        $htaccess = $this->cacheDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Allow from all\n");
        }
    }

    /**
     * Clean old cached images
     */
    public function cleanOldCache(): int
    {
        $count = 0;
        $files = glob($this->cacheDir . '/*');

        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.htaccess') {
                if ((time() - filemtime($file)) > $this->maxAge) {
                    unlink($file);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Get placeholder image URL based on type
     */
    public static function getPlaceholder(string $type = 'image'): string
    {
        if ($type === 'favicon') {
            return '/img/default-favicon.svg';
        }
        return '/img/default-image.svg';
    }

    /**
     * Batch cache images for multiple bookmarks
     */
    public function batchCache(array $bookmarks): void
    {
        foreach ($bookmarks as $bookmark) {
            $this->cacheBookmarkImages(
                (int)($bookmark['id'] ?? 0),
                $bookmark['favicon'] ?? null,
                $bookmark['meta_image'] ?? null
            );
        }
    }
}
