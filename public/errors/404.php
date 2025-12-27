<?php
/**
 * 404 Not Found Error Page
 * 
 * @package BookmarkManager
 */

declare(strict_types=1);

http_response_code(404);

$pageTitle = 'Page Not Found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - <?= $pageTitle ?></title>
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/utilities.css">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: var(--space-lg);
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: var(--color-primary);
            line-height: 1;
            margin-bottom: var(--space-md);
        }
        .error-title {
            font-size: var(--text-2xl);
            font-weight: 600;
            margin-bottom: var(--space-sm);
        }
        .error-message {
            color: var(--color-text-muted);
            margin-bottom: var(--space-xl);
            max-width: 400px;
        }
        .error-actions {
            display: flex;
            gap: var(--space-md);
            flex-wrap: wrap;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">
            Sorry, the page you're looking for doesn't exist or has been moved.
        </p>
        <div class="error-actions">
            <a href="/" class="btn btn-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Go Home
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                Go Back
            </button>
        </div>
    </div>
</body>
</html>
