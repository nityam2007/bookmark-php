<?php
/**
 * Sanitizer Helper
 * Input sanitization and validation utilities
 * 
 * @package BookmarkManager\Helpers
 */

declare(strict_types=1);

namespace App\Helpers;

class Sanitizer
{
    /**
     * Sanitize string input for storage (no HTML encoding - that's for display)
     */
    public static function string(?string $input, int $maxLength = 255): string
    {
        if ($input === null) {
            return '';
        }

        $input = trim($input);
        $input = strip_tags($input);
        // Remove control characters but keep newlines and tabs
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);
        
        if (mb_strlen($input) > $maxLength) {
            $input = mb_substr($input, 0, $maxLength);
        }

        return $input;
    }

    /**
     * Sanitize for HTML output (XSS prevention)
     */
    public static function html(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize URL
     */
    public static function url(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $url = trim($url);
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Only allow http/https
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array(strtolower($scheme ?? ''), ['http', 'https'])) {
            return null;
        }

        return $url;
    }

    /**
     * Sanitize email
     */
    public static function email(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }

        $email = trim(strtolower($email));
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * Sanitize integer
     */
    public static function int(?string $input, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): ?int
    {
        if ($input === null || $input === '') {
            return null;
        }

        $value = filter_var($input, FILTER_VALIDATE_INT);
        
        if ($value === false) {
            return null;
        }

        return max($min, min($max, $value));
    }

    /**
     * Sanitize boolean
     */
    public static function bool(mixed $input): bool
    {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Generate URL-safe slug
     */
    public static function slug(string $text): string
    {
        // Convert to lowercase
        $slug = mb_strtolower($text, 'UTF-8');
        
        // Replace non-alphanumeric characters with dashes
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug);
        
        // Remove duplicate dashes
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Trim dashes from ends
        $slug = trim($slug, '-');
        
        // Limit length
        if (strlen($slug) > 100) {
            $slug = substr($slug, 0, 100);
            $slug = rtrim($slug, '-');
        }

        return $slug ?: 'untitled';
    }

    /**
     * Sanitize array of tags
     */
    public static function tags(mixed $input): array
    {
        if (is_string($input)) {
            $input = explode(',', $input);
        }

        if (!is_array($input)) {
            return [];
        }

        $tags = [];
        foreach ($input as $tag) {
            $tag = self::string(trim((string)$tag), 50);
            if (!empty($tag) && !in_array($tag, $tags)) {
                $tags[] = $tag;
            }
        }

        return array_slice($tags, 0, 20); // Max 20 tags
    }

    /**
     * Sanitize filename
     */
    public static function filename(string $filename): string
    {
        // Remove path information
        $filename = basename($filename);
        
        // Remove dangerous characters
        $filename = preg_replace('/[^\w\-\.]/', '_', $filename);
        
        // Prevent double extensions
        $parts = explode('.', $filename);
        if (count($parts) > 2) {
            $ext = array_pop($parts);
            $filename = implode('_', $parts) . '.' . $ext;
        }

        return $filename;
    }

    /**
     * Validate and sanitize pagination parameters
     */
    public static function pagination(mixed $page, mixed $perPage = null): array
    {
        $page = max(1, self::int((string)$page) ?? 1);
        $perPage = $perPage !== null 
            ? max(1, min(100, self::int((string)$perPage) ?? ITEMS_PER_PAGE))
            : ITEMS_PER_PAGE;

        return [
            'page'     => $page,
            'per_page' => $perPage,
            'offset'   => ($page - 1) * $perPage
        ];
    }

    /**
     * Remove script tags and event handlers
     */
    public static function removeScripts(string $html): string
    {
        // Remove script tags
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        
        // Remove event handlers
        $html = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\bon\w+\s*=\s*\S+/i', '', $html);
        
        // Remove javascript: urls
        $html = preg_replace('/javascript\s*:/i', '', $html);

        return $html;
    }
}
