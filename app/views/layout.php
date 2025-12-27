<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\View::e($title ?? 'Bookmark Manager') ?> | <?= APP_NAME ?></title>
    
    <!-- CSRF Token for AJAX -->
    <?= \App\Helpers\Csrf::meta() ?>
    
    <!-- Minimal CSS - loaded conditionally -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/utilities.css">
    
    <?php if (!empty($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?= \App\Core\View::e($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        /* Critical CSS inlined for fast first paint */
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg: #f8fafc;
            --bg-card: #ffffff;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --radius: 0.5rem;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        
        .layout {
            display: flex;
            min-height: 100vh;
            gap: 0; /* Remove any gap */
        }
        
        .main-content {
            flex: 1;
            padding: 1.5rem;
            max-width: calc(100% - 260px); /* Subtract sidebar width */
            margin-left: 0; /* No margin */
            display: flex;
            flex-direction: column;
        }
        
        @media (min-width: 768px) {
            .main-content { padding: 2rem; }
        }
        
        @media (max-width: 768px) {
            .main-content { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php \App\Core\View::component('sidebar', ['currentUser' => $currentUser ?? null]); ?>
        
        <div class="main-content">
            <?php \App\Core\View::component('header', ['title' => $title ?? '', 'currentUser' => $currentUser ?? null]); ?>
            
            <?php \App\Core\View::component('flash'); ?>
            
            <main id="content">
                <?= $content ?? '' ?>
            </main>
            
            <?php \App\Core\View::component('footer'); ?>
        </div>
    </div>
    
    <!-- Core JS -->
    <script src="/js/app.js" defer></script>
    <script src="/js/search.js" defer></script>
    
    <?php if (!empty($extraJs)): ?>
        <?php foreach ($extraJs as $js): ?>
            <script src="<?= \App\Core\View::e($js) ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- GDPR Consent Banner -->
    <?php \App\Core\View::component('gdpr-banner'); ?>
</body>
</html>
</body>
</html>
