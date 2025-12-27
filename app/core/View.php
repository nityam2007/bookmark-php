<?php
/**
 * View Renderer
 * Handles template rendering with layout support
 * 
 * @package BookmarkManager\Core
 */

declare(strict_types=1);

namespace App\Core;

class View
{
    private static array $shared = [];
    private static string $layout = 'layout';

    /**
     * Share data across all views
     */
    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * Set layout template
     */
    public static function setLayout(string $layout): void
    {
        self::$layout = $layout;
    }

    /**
     * Render a view with layout
     */
    public static function render(string $view, array $data = [], bool $useLayout = true): string
    {
        $data = array_merge(self::$shared, $data);
        
        // Render the view content
        $content = self::renderPartial($view, $data);
        
        if (!$useLayout) {
            return $content;
        }

        // Render with layout
        $data['content'] = $content;
        return self::renderPartial(self::$layout, $data);
    }

    /**
     * Render a partial/component
     */
    public static function renderPartial(string $view, array $data = []): string
    {
        $viewPath = self::resolvePath($view);
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        extract($data, EXTR_SKIP);
        
        ob_start();
        include $viewPath;
        $output = ob_get_clean();
        return $output !== false ? $output : '';
    }

    /**
     * Include a component
     */
    public static function component(string $name, array $data = []): void
    {
        echo self::renderPartial("components/{$name}", array_merge(self::$shared, $data));
    }

    /**
     * Resolve view path
     */
    private static function resolvePath(string $view): string
    {
        $view = str_replace('.', '/', $view);
        
        // Check if it's a page view
        if (strpos($view, 'pages/') === 0 || strpos($view, 'components/') === 0) {
            return VIEWS_PATH . '/' . $view . '.php';
        }

        // Check in views root
        return VIEWS_PATH . '/' . $view . '.php';
    }

    /**
     * Escape output for XSS prevention
     */
    public static function escape(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Shorthand for escape
     */
    public static function e(mixed $value): string
    {
        return self::escape($value);
    }

    /**
     * Render JSON response
     */
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $status = 302): never
    {
        header("Location: {$url}", true, $status);
        exit;
    }
}

/**
 * Helper functions for views
 */
function e(mixed $value): string
{
    return View::escape($value);
}

function component(string $name, array $data = []): void
{
    View::component($name, $data);
}
