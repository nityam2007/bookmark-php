<?php
/**
 * Base Controller
 * Common functionality for all controllers
 * 
 * @package BookmarkManager\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Auth;

abstract class BaseController
{
    protected ?array $currentUser = null;

    public function __construct()
    {
        $this->currentUser = Auth::user();
    }

    /**
     * Render a view
     */
    protected function view(string $view, array $data = [], bool $useLayout = true): string
    {
        $data['currentUser'] = $this->currentUser;
        $data['csrfToken'] = Csrf::token();
        return View::render("pages/{$view}", $data, $useLayout);
    }

    /**
     * Render JSON response
     */
    protected function json(mixed $data, int $status = 200): never
    {
        View::json($data, $status);
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url, int $status = 302): never
    {
        View::redirect($url, $status);
    }

    /**
     * Get request input
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Get all request input
     */
    protected function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Validate CSRF token
     * Exits on failure (never returns false)
     */
    protected function validateCsrf(): true
    {
        $token = $this->input('_token') ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!Csrf::validate($token)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            // For regular form submissions, redirect back with error
            $this->flash('error', 'Invalid or expired form token. Please try again.');
            $referer = $_SERVER['HTTP_REFERER'] ?? '/login';
            // Validate referer is from same host to prevent open redirect
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $appHost = parse_url(APP_URL, PHP_URL_HOST);
            if ($refererHost !== $appHost) {
                $referer = '/login';
            }
            $this->redirect($referer);
        }
        
        return true;
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Unauthorized'], 401);
            }
            $this->redirect('/login');
        }
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get request method
     */
    protected function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Set flash message
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /**
     * Get and clear flash message
     */
    protected function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
