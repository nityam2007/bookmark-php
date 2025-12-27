<?php
/**
 * CSRF Protection Helper
 * Token generation and validation
 * 
 * @package BookmarkManager\Helpers
 */

declare(strict_types=1);

namespace App\Helpers;

class Csrf
{
    private static string $tokenName = '_csrf_token';
    private static string $tokenTime = '_csrf_time';

    /**
     * Generate and store CSRF token
     */
    public static function token(): string
    {
        self::ensureSession();

        // Check if token is still valid
        $currentTime = $_SESSION[self::$tokenTime] ?? 0;
        $currentToken = $_SESSION[self::$tokenName] ?? null;

        if ($currentToken && (time() - $currentTime) < CSRF_TOKEN_LIFETIME) {
            return $currentToken;
        }

        // Generate new token
        $token = bin2hex(random_bytes(32));
        
        $_SESSION[self::$tokenName] = $token;
        $_SESSION[self::$tokenTime] = time();

        return $token;
    }

    /**
     * Validate CSRF token
     */
    public static function validate(?string $token): bool
    {
        self::ensureSession();

        if (empty($token)) {
            return false;
        }

        $storedToken = $_SESSION[self::$tokenName] ?? null;
        $tokenTime = $_SESSION[self::$tokenTime] ?? 0;

        if (!$storedToken) {
            return false;
        }

        // Check expiration
        if ((time() - $tokenTime) > CSRF_TOKEN_LIFETIME) {
            self::regenerate();
            return false;
        }

        // Timing-safe comparison
        return hash_equals($storedToken, $token);
    }

    /**
     * Regenerate token
     */
    public static function regenerate(): string
    {
        self::ensureSession();
        unset($_SESSION[self::$tokenName], $_SESSION[self::$tokenTime]);
        return self::token();
    }

    /**
     * Get hidden input field
     */
    public static function field(): string
    {
        $token = self::token();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Get meta tag for AJAX
     */
    public static function meta(): string
    {
        $token = self::token();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }

    /**
     * Ensure session is started
     */
    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
