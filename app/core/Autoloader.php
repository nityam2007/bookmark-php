<?php
/**
 * PSR-4 Compatible Autoloader
 * Lightweight class autoloading for shared hosting
 * 
 * @package BookmarkManager\Core
 */

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    // Namespace prefix mappings
    $prefixes = [
        'App\\Core\\'        => APP_ROOT . '/app/core/',
        'App\\Controllers\\' => APP_ROOT . '/app/controllers/',
        'App\\Models\\'      => APP_ROOT . '/app/models/',
        'App\\Services\\'    => APP_ROOT . '/app/services/',
        'App\\Helpers\\'     => APP_ROOT . '/app/helpers/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
