<?php
/**
 * Authentication Helper
 * Session-based authentication management
 * 
 * @package BookmarkManager\Helpers
 */

declare(strict_types=1);

namespace App\Helpers;

use App\Models\User;

class Auth
{
    private static ?array $user = null;
    private static string $sessionKey = 'user_id';

    /**
     * Attempt login
     */
    public static function attempt(string $username, string $password): bool
    {
        $user = User::authenticate($username, $password);
        
        if (!$user) {
            return false;
        }

        self::login($user);
        return true;
    }

    /**
     * Login user
     */
    public static function login(array $user): void
    {
        self::ensureSession();
        
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        
        $_SESSION[self::$sessionKey] = $user['id'];
        $_SESSION['login_time'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address'] = self::getClientIp();
        
        self::$user = $user;
    }

    /**
     * Logout user
     */
    public static function logout(): void
    {
        self::ensureSession();
        
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
        self::$user = null;
    }

    /**
     * Check if user is authenticated
     */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Check if user is guest
     */
    public static function guest(): bool
    {
        return !self::check();
    }

    /**
     * Get current user
     */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        self::ensureSession();

        $userId = $_SESSION[self::$sessionKey] ?? null;
        
        if (!$userId) {
            return null;
        }

        // Validate session
        if (!self::validateSession()) {
            self::logout();
            return null;
        }

        self::$user = User::getSafe($userId);
        return self::$user;
    }

    /**
     * Get user ID
     */
    public static function id(): ?int
    {
        $user = self::user();
        return $user ? $user['id'] : null;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user && $user['role'] === 'admin';
    }

    /**
     * Validate session integrity
     */
    private static function validateSession(): bool
    {
        // Check session lifetime
        $loginTime = $_SESSION['login_time'] ?? 0;
        if ((time() - $loginTime) > SESSION_LIFETIME) {
            return false;
        }

        // Optional: Check if user agent changed (security)
        $storedAgent = $_SESSION['user_agent'] ?? '';
        $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if ($storedAgent !== $currentAgent) {
            return false;
        }

        return true;
    }

    /**
     * Refresh session
     */
    public static function refresh(): void
    {
        self::ensureSession();
        $_SESSION['login_time'] = time();
    }

    /**
     * Get client IP address
     */
    public static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
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
