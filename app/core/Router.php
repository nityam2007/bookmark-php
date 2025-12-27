<?php
/**
 * Simple Router
 * Lightweight routing for MVC architecture
 * 
 * @package BookmarkManager\Core
 */

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $prefix = '';

    /**
     * Add GET route
     */
    public function get(string $path, array|callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Add POST route
     */
    public function post(string $path, array|callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Add PUT route
     */
    public function put(string $path, array|callable $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Add DELETE route
     */
    public function delete(string $path, array|callable $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Group routes with prefix
     */
    public function group(string $prefix, callable $callback): self
    {
        $previousPrefix = $this->prefix;
        $this->prefix = $previousPrefix . $prefix;
        $callback($this);
        $this->prefix = $previousPrefix;
        return $this;
    }

    /**
     * Add middleware
     */
    public function middleware(string $name, callable $handler): self
    {
        $this->middleware[$name] = $handler;
        return $this;
    }

    /**
     * Add route internally
     */
    private function addRoute(string $method, string $path, array|callable $handler): self
    {
        $fullPath = $this->prefix . $path;
        $pattern = $this->pathToPattern($fullPath);
        
        $this->routes[] = [
            'method'  => $method,
            'path'    => $fullPath,
            'pattern' => $pattern,
            'handler' => $handler
        ];
        
        return $this;
    }

    /**
     * Convert path to regex pattern
     */
    private function pathToPattern(string $path): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = preg_replace('/\{(\w+):(\w+)\}/', '(?P<$1>$2)', $pattern);
        return '#^' . $pattern . '$#';
    }

    /**
     * Dispatch request
     */
    public function dispatch(string $method, string $uri): mixed
    {
        // Remove query string
        $uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                return $this->callHandler($route['handler'], $params);
            }
        }

        // 404 Not Found
        http_response_code(404);
        return $this->render404();
    }

    /**
     * Call route handler
     */
    private function callHandler(array|callable $handler, array $params): mixed
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        [$controllerClass, $method] = $handler;
        
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller {$controllerClass} not found");
        }

        $controller = new $controllerClass();
        
        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method {$method} not found in {$controllerClass}");
        }

        return call_user_func_array([$controller, $method], $params);
    }

    /**
     * Render 404 page
     */
    private function render404(): string
    {
        // Check views path first, then public errors
        $errorPage = VIEWS_PATH . '/pages/404.php';
        
        if (!file_exists($errorPage)) {
            $errorPage = APP_ROOT . '/public/errors/404.php';
        }
        
        if (file_exists($errorPage)) {
            ob_start();
            include $errorPage;
            return ob_get_clean();
        }

        return '<h1>404 - Page Not Found</h1>';
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
