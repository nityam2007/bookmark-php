<?php
/**
 * External API Entry Point
 * Proxies requests to the main API handler in /app/api/
 * 
 * @package BookmarkManager\API
 */

declare(strict_types=1);

// Define root path
define('APP_ROOT', dirname(__DIR__));

// Load and execute the external API
require_once APP_ROOT . '/app/api/external.php';
