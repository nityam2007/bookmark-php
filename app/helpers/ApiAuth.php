<?php
/**
 * API Authentication Helper
 * Handles API key authentication for external requests
 * 
 * @package BookmarkManager\Helpers
 */

declare(strict_types=1);

namespace App\Helpers;

use App\Models\ApiKey;

class ApiAuth
{
    private static ?array $user = null;
    private static ?int $apiKeyId = null;

    /**
     * Authenticate request via API key
     * Checks Authorization header or X-API-Key header
     */
    public static function authenticate(): bool
    {
        $apiKey = self::extractApiKey();
        
        if (!$apiKey) {
            return false;
        }
        
        $user = ApiKey::validate($apiKey);
        
        if (!$user) {
            return false;
        }
        
        self::$user = $user;
        self::$apiKeyId = $user['api_key_id'] ?? null;
        
        return true;
    }

    /**
     * Check if API request is authenticated
     */
    public static function check(): bool
    {
        return self::$user !== null;
    }

    /**
     * Get authenticated user
     */
    public static function user(): ?array
    {
        return self::$user;
    }

    /**
     * Get user ID
     */
    public static function id(): ?int
    {
        return self::$user ? (int) self::$user['id'] : null;
    }

    /**
     * Get API key ID used for this request
     */
    public static function keyId(): ?int
    {
        return self::$apiKeyId;
    }

    /**
     * Extract API key from request headers
     */
    private static function extractApiKey(): ?string
    {
        // Method 1: Authorization: Bearer <api_key>
        // Check multiple possible header names (Apache can be tricky)
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] 
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] 
            ?? '';
        
        // Also try to get from apache_request_headers if available
        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if ($headers) {
                $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            }
        }
        
        if (!empty($authHeader) && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }
        
        // Method 2: X-API-Key header
        $apiKeyHeader = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if (!empty($apiKeyHeader)) {
            return trim($apiKeyHeader);
        }
        
        // Method 3: Query parameter (less secure, but convenient for testing)
        if (!empty($_GET['api_key'])) {
            return trim($_GET['api_key']);
        }
        
        return null;
    }

    /**
     * Send JSON error response and exit
     */
    public static function unauthorized(string $message = 'Unauthorized'): never
    {
        http_response_code(401);
        header('Content-Type: application/json');
        header('WWW-Authenticate: Bearer realm="API"');
        echo json_encode([
            'success' => false,
            'error'   => $message,
            'code'    => 401
        ]);
        exit;
    }

    /**
     * Require API authentication or exit
     */
    public static function require(): void
    {
        if (!self::authenticate()) {
            self::unauthorized('Invalid or missing API key');
        }
    }
}
