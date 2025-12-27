<?php
/**
 * Enhanced Meta Fetcher Service
 * Production-grade metadata extraction from URLs
 * 
 * Extracts:
 * - Open Graph meta (og:title, og:description, og:image, etc.)
 * - Twitter Card meta (twitter:title, twitter:description, twitter:image)
 * - Standard meta tags (title, description, keywords, author)
 * - Favicon and Apple touch icons
 * - HTTP status and content type
 * 
 * @package BookmarkManager\Services
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Bookmark;

class EnhancedMetaFetcher
{
    private int $timeout;
    private string $userAgent;
    private int $maxRedirects;
    private bool $verifySSL;
    
    // Common favicon paths to try
    private const FAVICON_PATHS = [
        '/favicon.ico',
        '/favicon.png',
        '/apple-touch-icon.png',
        '/apple-touch-icon-precomposed.png'
    ];

    public function __construct()
    {
        $this->timeout = defined('META_FETCH_TIMEOUT') ? META_FETCH_TIMEOUT : 15;
        $this->userAgent = defined('META_USER_AGENT') 
            ? META_USER_AGENT 
            : 'Mozilla/5.0 (compatible; BookmarkManager/2.0; +https://github.com/bookmark-manager)';
        $this->maxRedirects = 5;
        $this->verifySSL = !defined('APP_DEBUG') || !APP_DEBUG;
    }

    /**
     * Fetch comprehensive metadata from URL
     */
    public function fetch(string $url): array
    {
        $startTime = microtime(true);
        
        $result = [
            'url'               => $url,
            'final_url'         => $url,
            'success'           => false,
            'error'             => null,
            'http_status'       => null,
            'content_type'      => null,
            'fetch_time_ms'     => 0,
            // Meta fields
            'meta_title'        => null,
            'meta_description'  => null,
            'meta_site_name'    => null,
            'meta_type'         => null,
            'meta_author'       => null,
            'meta_keywords'     => null,
            'meta_locale'       => null,
            'meta_twitter_card' => null,
            'meta_twitter_site' => null,
            // Legacy fields for compatibility
            'title'             => null,
            'description'       => null,
            'meta_image'        => null,
            'favicon'           => null,
            // All images found
            'images'            => []
        ];

        // Validate URL
        if (!$this->isValidUrl($url)) {
            $result['error'] = 'Invalid URL format';
            return $this->finalizeResult($result, $startTime);
        }

        try {
            // Fetch with cURL for better control
            $response = $this->fetchWithCurl($url);
            
            if ($response === null) {
                $result['error'] = 'Failed to fetch URL (timeout or connection error)';
                return $this->finalizeResult($result, $startTime);
            }

            $result['http_status'] = $response['http_status'];
            $result['content_type'] = $response['content_type'];
            $result['final_url'] = $response['final_url'];

            // Check for successful response
            if ($response['http_status'] >= 400) {
                $result['error'] = "HTTP {$response['http_status']} error";
                return $this->finalizeResult($result, $startTime);
            }

            // Check content type - only parse HTML
            if (!$this->isHtmlContent($response['content_type'])) {
                $result['error'] = 'Not HTML content: ' . ($response['content_type'] ?? 'unknown');
                // Still try to get basic info
                $result['meta_title'] = $this->getTitleFromUrl($url);
                $result['title'] = $result['meta_title'];
                return $this->finalizeResult($result, $startTime);
            }

            // Parse HTML content
            $parsed = $this->parseHtml($response['body'], $response['final_url']);
            $result = array_merge($result, $parsed);
            $result['success'] = true;

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $this->finalizeResult($result, $startTime);
    }

    /**
     * Fetch URL using cURL
     */
    private function fetchWithCurl(string $url): ?array
    {
        if (!function_exists('curl_init')) {
            return $this->fetchWithStream($url);
        }

        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => $this->maxRedirects,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => $this->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->verifySSL ? 2 : 0,
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1'
            ],
            CURLOPT_ENCODING       => '', // Accept all encodings
            CURLOPT_HEADER         => true,
        ]);

        $response = curl_exec($ch);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return null;
        }

        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        curl_close($ch);

        $body = substr($response, $headerSize);
        
        // Handle encoding
        $body = $this->ensureUtf8($body, $contentType);

        return [
            'http_status'  => $httpStatus,
            'content_type' => $contentType,
            'final_url'    => $finalUrl,
            'body'         => $body
        ];
    }

    /**
     * Fallback to stream context (if cURL not available)
     */
    private function fetchWithStream(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method'           => 'GET',
                'timeout'          => $this->timeout,
                'follow_location'  => true,
                'max_redirects'    => $this->maxRedirects,
                'header'           => [
                    "User-Agent: {$this->userAgent}",
                    "Accept: text/html,application/xhtml+xml",
                    "Accept-Language: en-US,en;q=0.5"
                ],
                'ignore_errors'    => true
            ],
            'ssl' => [
                'verify_peer'      => $this->verifySSL,
                'verify_peer_name' => $this->verifySSL
            ]
        ]);

        $body = @file_get_contents($url, false, $context);
        
        if ($body === false) {
            return null;
        }

        // Parse response headers
        $httpStatus = 200;
        $contentType = null;
        
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\d+\.\d+\s+(\d+)/', $header, $m)) {
                    $httpStatus = (int)$m[1];
                }
                if (preg_match('/^Content-Type:\s*(.+)$/i', $header, $m)) {
                    $contentType = trim($m[1]);
                }
            }
        }

        return [
            'http_status'  => $httpStatus,
            'content_type' => $contentType,
            'final_url'    => $url,
            'body'         => $this->ensureUtf8($body, $contentType)
        ];
    }

    /**
     * Parse HTML and extract all metadata
     */
    private function parseHtml(string $html, string $baseUrl): array
    {
        $result = [];

        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        // ============================================
        // OPEN GRAPH METADATA
        // ============================================
        $result['meta_title'] = $this->getMetaContent($xpath, [
            '//meta[@property="og:title"]/@content',
            '//meta[@name="og:title"]/@content'
        ]);
        
        $result['meta_description'] = $this->getMetaContent($xpath, [
            '//meta[@property="og:description"]/@content',
            '//meta[@name="og:description"]/@content'
        ]);
        
        $result['meta_site_name'] = $this->getMetaContent($xpath, [
            '//meta[@property="og:site_name"]/@content'
        ]);
        
        $result['meta_type'] = $this->getMetaContent($xpath, [
            '//meta[@property="og:type"]/@content'
        ]);
        
        $result['meta_locale'] = $this->getMetaContent($xpath, [
            '//meta[@property="og:locale"]/@content'
        ]);

        // ============================================
        // TWITTER CARD METADATA
        // ============================================
        $result['meta_twitter_card'] = $this->getMetaContent($xpath, [
            '//meta[@name="twitter:card"]/@content',
            '//meta[@property="twitter:card"]/@content'
        ]);
        
        $result['meta_twitter_site'] = $this->getMetaContent($xpath, [
            '//meta[@name="twitter:site"]/@content',
            '//meta[@property="twitter:site"]/@content'
        ]);

        // Twitter title/description as fallback
        if (!$result['meta_title']) {
            $result['meta_title'] = $this->getMetaContent($xpath, [
                '//meta[@name="twitter:title"]/@content'
            ]);
        }
        
        if (!$result['meta_description']) {
            $result['meta_description'] = $this->getMetaContent($xpath, [
                '//meta[@name="twitter:description"]/@content'
            ]);
        }

        // ============================================
        // STANDARD HTML META TAGS
        // ============================================
        // Fallback title from <title> tag
        if (!$result['meta_title']) {
            $nodes = $xpath->query('//title');
            if ($nodes->length > 0) {
                $result['meta_title'] = $this->cleanText($nodes->item(0)->nodeValue, 500);
            }
        }
        
        // Fallback description from meta description
        if (!$result['meta_description']) {
            $result['meta_description'] = $this->getMetaContent($xpath, [
                '//meta[@name="description"]/@content'
            ]);
        }
        
        $result['meta_author'] = $this->getMetaContent($xpath, [
            '//meta[@name="author"]/@content',
            '//meta[@property="article:author"]/@content'
        ]);
        
        $result['meta_keywords'] = $this->getMetaContent($xpath, [
            '//meta[@name="keywords"]/@content'
        ]);

        // ============================================
        // LEGACY FIELDS (for backward compatibility)
        // ============================================
        $result['title'] = $result['meta_title'];
        $result['description'] = $result['meta_description'];

        // ============================================
        // IMAGES
        // ============================================
        $images = [];
        
        // OG Images
        $ogImages = $this->extractImages($xpath, [
            '//meta[@property="og:image"]/@content',
            '//meta[@property="og:image:url"]/@content',
            '//meta[@property="og:image:secure_url"]/@content'
        ], $baseUrl, 'og_image');
        $images = array_merge($images, $ogImages);
        
        // Twitter Images
        $twitterImages = $this->extractImages($xpath, [
            '//meta[@name="twitter:image"]/@content',
            '//meta[@name="twitter:image:src"]/@content'
        ], $baseUrl, 'twitter_image');
        $images = array_merge($images, $twitterImages);

        // Schema.org images
        $schemaImages = $this->extractImages($xpath, [
            '//script[@type="application/ld+json"]'
        ], $baseUrl, 'schema_image');
        // TODO: Parse JSON-LD for images
        
        $result['images'] = $images;
        $result['meta_image'] = !empty($images) ? $images[0]['url'] : null;

        // ============================================
        // FAVICON & ICONS
        // ============================================
        $result['favicon'] = $this->extractFavicon($xpath, $baseUrl);

        return $result;
    }

    /**
     * Get meta content from XPath queries
     */
    private function getMetaContent(\DOMXPath $xpath, array $queries): ?string
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes->length > 0) {
                $value = $this->cleanText($nodes->item(0)->nodeValue, 1000);
                if ($value) return $value;
            }
        }
        return null;
    }

    /**
     * Extract images from XPath queries
     */
    private function extractImages(\DOMXPath $xpath, array $queries, string $baseUrl, string $type): array
    {
        $images = [];
        
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            for ($i = 0; $i < $nodes->length; $i++) {
                $url = $this->resolveUrl($nodes->item($i)->nodeValue, $baseUrl);
                if ($url && $this->isValidImageUrl($url)) {
                    $images[] = [
                        'url'  => $url,
                        'type' => $type,
                        'alt'  => null
                    ];
                }
            }
        }
        
        return $images;
    }

    /**
     * Extract favicon URL
     */
    private function extractFavicon(\DOMXPath $xpath, string $baseUrl): ?string
    {
        // Try various favicon link relations (ordered by preference)
        $queries = [
            '//link[@rel="icon" and @sizes="32x32"]/@href',
            '//link[@rel="icon" and @sizes="16x16"]/@href',
            '//link[@rel="icon"]/@href',
            '//link[@rel="shortcut icon"]/@href',
            '//link[contains(@rel, "apple-touch-icon")]/@href',
            '//link[@rel="apple-touch-icon-precomposed"]/@href',
            '//link[@rel="mask-icon"]/@href'
        ];

        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes->length > 0) {
                $url = $this->resolveUrl($nodes->item(0)->nodeValue, $baseUrl);
                if ($url) return $url;
            }
        }

        // Fallback: try common paths
        $parsed = parse_url($baseUrl);
        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        
        foreach (self::FAVICON_PATHS as $path) {
            $faviconUrl = $base . $path;
            // We don't actually check if it exists here - that would be slow
            // The first one is most likely to exist
            if ($path === '/favicon.ico') {
                return $faviconUrl;
            }
        }

        return $base . '/favicon.ico';
    }

    /**
     * Resolve relative URLs to absolute
     */
    private function resolveUrl(?string $url, string $baseUrl): ?string
    {
        if (!$url) return null;
        
        $url = trim($url);
        if (!$url) return null;

        // Already absolute
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        
        // Data URL
        if (str_starts_with($url, 'data:')) {
            return null; // Skip data URLs
        }

        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $base = "{$scheme}://{$host}";

        // Protocol-relative
        if (str_starts_with($url, '//')) {
            return $scheme . ':' . $url;
        }

        // Absolute path
        if (str_starts_with($url, '/')) {
            return $base . $url;
        }

        // Relative path
        $path = $parsed['path'] ?? '/';
        $path = dirname($path);
        if ($path === '.') $path = '';
        
        return $base . $path . '/' . $url;
    }

    /**
     * Validate URL format
     */
    private function isValidUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array(strtolower($scheme ?? ''), ['http', 'https']);
    }

    /**
     * Check if URL points to an image
     */
    private function isValidImageUrl(string $url): bool
    {
        // Basic check - could be enhanced with HEAD request
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if content type is HTML
     */
    private function isHtmlContent(?string $contentType): bool
    {
        if (!$contentType) return false;
        return (
            stripos($contentType, 'text/html') !== false ||
            stripos($contentType, 'application/xhtml') !== false
        );
    }

    /**
     * Ensure content is UTF-8 encoded
     */
    private function ensureUtf8(string $content, ?string $contentType): string
    {
        // Try to detect encoding from content-type header
        $encoding = null;
        if ($contentType && preg_match('/charset=([^\s;]+)/i', $contentType, $m)) {
            $encoding = trim($m[1], '"\'');
        }
        
        // Try to detect from HTML meta
        if (!$encoding && preg_match('/<meta[^>]+charset=["\']?([^"\'\s>]+)/i', $content, $m)) {
            $encoding = $m[1];
        }
        
        // Fallback to detection
        if (!$encoding) {
            $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        }
        
        if ($encoding && strtoupper($encoding) !== 'UTF-8') {
            $converted = @mb_convert_encoding($content, 'UTF-8', $encoding);
            if ($converted !== false) {
                return $converted;
            }
        }
        
        return $content;
    }

    /**
     * Clean extracted text
     */
    private function cleanText(?string $text, int $maxLength = 255): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Truncate if needed
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength - 3) . '...';
        }

        return $text ?: null;
    }

    /**
     * Get title from URL as fallback
     */
    private function getTitleFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? $url;
    }

    /**
     * Finalize result with timing
     */
    private function finalizeResult(array $result, float $startTime): array
    {
        $result['fetch_time_ms'] = (int)((microtime(true) - $startTime) * 1000);
        return $result;
    }

    /**
     * Batch fetch metadata for multiple bookmarks
     * Designed for cron job usage
     */
    public function batchFetch(array $bookmarks, callable $onProgress = null): array
    {
        $results = [
            'processed' => 0,
            'success'   => 0,
            'failed'    => 0,
            'errors'    => []
        ];

        foreach ($bookmarks as $bookmark) {
            $results['processed']++;
            
            $meta = $this->fetch($bookmark['url']);
            
            if ($meta['success']) {
                // Update bookmark with meta data
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
                    'meta_image'        => $meta['meta_image'],
                    'favicon'           => $meta['favicon'],
                    'http_status'       => $meta['http_status'],
                    'content_type'      => $meta['content_type'],
                    'meta_fetch_error'  => null,
                    'meta_fetched_at'   => date('Y-m-d H:i:s')
                ]);
                
                // Save images
                if (!empty($meta['images'])) {
                    Bookmark::saveMetaImages($bookmark['id'], $meta['images']);
                }
                
                // Log success
                Bookmark::logMetaFetch($bookmark['id'], $bookmark['url'], $meta);
                
                $results['success']++;
            } else {
                // Update with error
                Bookmark::updateMeta($bookmark['id'], [
                    'meta_fetch_error' => mb_substr($meta['error'] ?? 'Unknown error', 0, 255),
                    'http_status'      => $meta['http_status'],
                    'meta_fetched_at'  => date('Y-m-d H:i:s')
                ]);
                
                // Log failure
                Bookmark::logMetaFetch($bookmark['id'], $bookmark['url'], $meta);
                
                $results['failed']++;
                $results['errors'][] = [
                    'id'    => $bookmark['id'],
                    'url'   => $bookmark['url'],
                    'error' => $meta['error']
                ];
            }

            // Call progress callback if provided
            if ($onProgress) {
                $onProgress($bookmark, $meta, $results);
            }
            
            // Small delay to be nice to servers
            usleep(100000); // 100ms
        }

        return $results;
    }
}
