<?php
/**
 * Meta Fetcher Service
 * Fetches meta information from URLs (title, description, images)
 * 
 * @package BookmarkManager\Services
 */

declare(strict_types=1);

namespace App\Services;

class MetaFetcher
{
    private int $timeout;
    private string $userAgent;

    public function __construct()
    {
        $this->timeout = META_FETCH_TIMEOUT;
        $this->userAgent = META_USER_AGENT;
    }

    /**
     * Fetch metadata from URL
     */
    public function fetch(string $url): array
    {
        $result = [
            'url'         => $url,
            'title'       => null,
            'description' => null,
            'meta_image'  => null,
            'favicon'     => null,
            'is_image'    => false,
            'success'     => false,
            'error'       => null
        ];

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $result['error'] = 'Invalid URL';
            return $result;
        }

        // Check if URL is a direct image link
        if ($this->isImageUrl($url)) {
            return $this->handleImageUrl($url, $result);
        }

        try {
            $html = $this->fetchHtml($url);
            
            if ($html === null) {
                $result['error'] = 'Failed to fetch URL';
                return $result;
            }

            // Parse HTML
            $parsed = $this->parseHtml($html, $url);
            
            $result = array_merge($result, $parsed);
            $result['success'] = true;

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Check if URL points directly to an image
     */
    private function isImageUrl(string $url): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif'];
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        // Check extension
        if (in_array($ext, $imageExtensions)) {
            return true;
        }
        
        // Check for common image hosting patterns
        $imageHosts = [
            'i.imgur.com',
            'i.redd.it', 
            'pbs.twimg.com',
            'cdn.discordapp.com/attachments',
            'images.unsplash.com',
            'upload.wikimedia.org'
        ];
        
        $host = $parsedUrl['host'] ?? '';
        foreach ($imageHosts as $imageHost) {
            if (str_contains($host, $imageHost) || str_contains($path, $imageHost)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Handle direct image URLs - cache them and generate metadata
     */
    private function handleImageUrl(string $url, array $result): array
    {
        $result['is_image'] = true;
        
        // Generate title from filename
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $filename = basename($path);
        $title = pathinfo($filename, PATHINFO_FILENAME);
        $title = preg_replace('/[-_]+/', ' ', $title); // Replace dashes/underscores with spaces
        $title = ucwords(trim($title));
        
        $result['title'] = $title ?: 'Image';
        $result['description'] = 'Image from ' . ($parsedUrl['host'] ?? 'unknown');
        $result['meta_image'] = $url; // The URL itself is the image
        
        // Try to cache the image locally for offloading
        try {
            $imageCacheService = new ImageCacheService();
            $cachedUrl = $imageCacheService->cacheImageForOffload($url);
            if ($cachedUrl) {
                $result['cached_image_path'] = $cachedUrl;
            }
        } catch (\Throwable $e) {
            // Caching failed, but we still have the original URL
            error_log("Failed to cache image {$url}: " . $e->getMessage());
        }
        
        $result['success'] = true;
        return $result;
    }

    /**
     * Fetch HTML content from URL
     */
    private function fetchHtml(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'timeout'         => $this->timeout,
                'follow_location' => true,
                'max_redirects'   => 5,
                'header'          => [
                    "User-Agent: {$this->userAgent}",
                    "Accept: text/html,application/xhtml+xml",
                    "Accept-Language: en-US,en;q=0.5"
                ]
            ],
            'ssl' => [
                // Note: In production, consider enabling SSL verification
                // and providing a valid CA bundle path
                'verify_peer'       => !APP_DEBUG,
                'verify_peer_name'  => !APP_DEBUG
            ]
        ]);

        $html = @file_get_contents($url, false, $context);
        
        if ($html === false) {
            return null;
        }

        // Handle encoding
        $encoding = mb_detect_encoding($html, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $html = mb_convert_encoding($html, 'UTF-8', $encoding);
        }

        return $html;
    }

    /**
     * Parse HTML and extract metadata
     */
    private function parseHtml(string $html, string $url): array
    {
        $result = [];

        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        // Title
        $result['title'] = $this->extractTitle($xpath);

        // Description
        $result['description'] = $this->extractDescription($xpath);

        // Meta Image (og:image or twitter:image)
        $result['meta_image'] = $this->extractMetaImage($xpath, $url);

        // Favicon
        $result['favicon'] = $this->extractFavicon($xpath, $url);

        return $result;
    }

    /**
     * Extract page title
     */
    private function extractTitle(\DOMXPath $xpath): ?string
    {
        // Try og:title first
        $nodes = $xpath->query('//meta[@property="og:title"]/@content');
        if ($nodes->length > 0) {
            return $this->cleanText($nodes->item(0)->nodeValue);
        }

        // Try twitter:title
        $nodes = $xpath->query('//meta[@name="twitter:title"]/@content');
        if ($nodes->length > 0) {
            return $this->cleanText($nodes->item(0)->nodeValue);
        }

        // Fallback to <title>
        $nodes = $xpath->query('//title');
        if ($nodes->length > 0) {
            return $this->cleanText($nodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Extract meta description
     */
    private function extractDescription(\DOMXPath $xpath): ?string
    {
        // Try og:description
        $nodes = $xpath->query('//meta[@property="og:description"]/@content');
        if ($nodes->length > 0) {
            return $this->cleanText($nodes->item(0)->nodeValue, 500);
        }

        // Try meta description
        $nodes = $xpath->query('//meta[@name="description"]/@content');
        if ($nodes->length > 0) {
            return $this->cleanText($nodes->item(0)->nodeValue, 500);
        }

        // Try twitter:description
        $nodes = $xpath->query('//meta[@name="twitter:description"]/@content');
        if ($nodes->length > 0) {
            return $this->cleanText($nodes->item(0)->nodeValue, 500);
        }

        return null;
    }

    /**
     * Extract meta image
     */
    private function extractMetaImage(\DOMXPath $xpath, string $baseUrl): ?string
    {
        // Try og:image
        $nodes = $xpath->query('//meta[@property="og:image"]/@content');
        if ($nodes->length > 0) {
            return $this->resolveUrl($nodes->item(0)->nodeValue, $baseUrl);
        }

        // Try twitter:image
        $nodes = $xpath->query('//meta[@name="twitter:image"]/@content');
        if ($nodes->length > 0) {
            return $this->resolveUrl($nodes->item(0)->nodeValue, $baseUrl);
        }

        return null;
    }

    /**
     * Extract favicon
     */
    private function extractFavicon(\DOMXPath $xpath, string $baseUrl): ?string
    {
        // Try various favicon link relations
        $queries = [
            '//link[@rel="icon"]/@href',
            '//link[@rel="shortcut icon"]/@href',
            '//link[@rel="apple-touch-icon"]/@href',
            '//link[@rel="apple-touch-icon-precomposed"]/@href'
        ];

        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes->length > 0) {
                return $this->resolveUrl($nodes->item(0)->nodeValue, $baseUrl);
            }
        }

        // Fallback to /favicon.ico
        $parsed = parse_url($baseUrl);
        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . '/favicon.ico';
    }

    /**
     * Resolve relative URLs
     */
    private function resolveUrl(string $url, string $baseUrl): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $parsed = parse_url($baseUrl);
        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

        if (str_starts_with($url, '//')) {
            return ($parsed['scheme'] ?? 'https') . ':' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $base . $url;
        }

        $path = $parsed['path'] ?? '/';
        $path = dirname($path);
        
        return $base . $path . '/' . $url;
    }

    /**
     * Clean extracted text
     */
    private function cleanText(?string $text, int $maxLength = 255): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength - 3) . '...';
        }

        return $text ?: null;
    }
}
