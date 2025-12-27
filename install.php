<?php
/**
 * Bookmark Manager - Web Installer
 * For shared hosting environments
 * 
 * ⚠️ DELETE THIS FILE AFTER INSTALLATION!
 * 
 * @package BookmarkManager
 * @version 1.0.0
 */

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Error display for installation
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Installation steps
$steps = [
    1 => 'Requirements Check',
    2 => 'Database Configuration', 
    3 => 'Admin Account Setup',
    4 => 'Installation Complete'
];

$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = [];

// Check if already installed
if (file_exists(__DIR__ . '/app/config/config.json') && $currentStep === 1) {
    $config = json_decode(file_get_contents(__DIR__ . '/app/config/config.json'), true);
    if (!empty($config['database']['host'])) {
        $alreadyInstalled = true;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Step 2: Database Configuration
    if ($currentStep === 2 && isset($_POST['db_host'])) {
        $dbConfig = [
            'host' => trim($_POST['db_host']),
            'name' => trim($_POST['db_name']),
            'user' => trim($_POST['db_user']),
            'pass' => $_POST['db_pass'],
            'port' => (int)($_POST['db_port'] ?: 3306),
            'charset' => 'utf8mb4'
        ];
        
        // Test database connection
        try {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbConfig['name']}`");
            
            // Run schema
            $schema = file_get_contents(__DIR__ . '/database/schema.sql');
            
            // Split by semicolon but handle the DELIMITER issue
            $schema = preg_replace('/--.*$/m', '', $schema); // Remove comments
            $schema = preg_replace('/\/\*.*?\*\//s', '', $schema); // Remove block comments
            
            // Execute each statement
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            foreach ($statements as $stmt) {
                if (!empty($stmt) && stripos($stmt, 'INSERT INTO `users`') === false) {
                    try {
                        $pdo->exec($stmt);
                    } catch (PDOException $e) {
                        // Ignore "already exists" errors
                        if (strpos($e->getMessage(), 'already exists') === false && 
                            strpos($e->getMessage(), 'Duplicate') === false) {
                            // Log but continue
                        }
                    }
                }
            }
            
            // Run migrations
            $migrationFiles = glob(__DIR__ . '/database/migrations/*.sql');
            sort($migrationFiles);
            foreach ($migrationFiles as $migrationFile) {
                $migration = file_get_contents($migrationFile);
                $migration = preg_replace('/--.*$/m', '', $migration);
                $statements = array_filter(array_map('trim', explode(';', $migration)));
                foreach ($statements as $stmt) {
                    if (!empty($stmt)) {
                        try {
                            $pdo->exec($stmt);
                        } catch (PDOException $e) {
                            // Ignore errors for migrations (columns may already exist)
                        }
                    }
                }
            }
            
            // Save database config to session
            $_SESSION['install_db'] = $dbConfig;
            
            // Redirect to next step
            header('Location: install.php?step=3');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Database connection failed: " . $e->getMessage();
        }
    }
    
    // Step 3: Admin Account
    if ($currentStep === 3 && isset($_POST['admin_user'])) {
        $adminUser = trim($_POST['admin_user']);
        $adminEmail = trim($_POST['admin_email']);
        $adminPass = $_POST['admin_pass'];
        $adminPassConfirm = $_POST['admin_pass_confirm'];
        $appUrl = rtrim(trim($_POST['app_url']), '/');
        
        // Validate
        if (strlen($adminUser) < 3) {
            $errors[] = "Username must be at least 3 characters";
        }
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email address";
        }
        if (strlen($adminPass) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }
        if ($adminPass !== $adminPassConfirm) {
            $errors[] = "Passwords do not match";
        }
        
        if (empty($errors)) {
            $dbConfig = $_SESSION['install_db'];
            
            try {
                $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
                $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // Create admin user
                $passwordHash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
                
                // Check if admin already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$adminUser, $adminEmail]);
                if ($stmt->fetch()) {
                    // Update existing
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, email = ? WHERE username = ?");
                    $stmt->execute([$passwordHash, $adminEmail, $adminUser]);
                } else {
                    // Insert new
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
                    $stmt->execute([$adminUser, $adminEmail, $passwordHash]);
                }
                
                // Create config.json
                $config = [
                    'database' => $dbConfig,
                    'app' => [
                        'name' => 'Bookmark Manager',
                        'url' => $appUrl,
                        'debug' => false,
                        'timezone' => 'UTC'
                    ],
                    'security' => [
                        'session_lifetime' => 86400,
                        'csrf_token_lifetime' => 3600,
                        'bcrypt_cost' => 12
                    ],
                    'meta_fetcher' => [
                        'enabled' => true,
                        'timeout' => 10,
                        'user_agent' => 'BookmarkManager/1.0',
                        'max_retries' => 3
                    ],
                    'pagination' => [
                        'per_page' => 24,
                        'max_per_page' => 100
                    ],
                    'cache' => [
                        'enabled' => true,
                        'ttl' => 3600
                    ],
                    'gdpr' => [
                        'enabled' => false,
                        'cookie_lifetime' => 365
                    ]
                ];
                
                $configPath = __DIR__ . '/app/config/config.json';
                if (file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                    // Create necessary directories with proper permissions
                    $dirs = [
                        __DIR__ . '/cache',
                        __DIR__ . '/cache/images',
                        __DIR__ . '/logs'
                    ];
                    foreach ($dirs as $dir) {
                        if (!is_dir($dir)) {
                            mkdir($dir, 0755, true);
                        }
                        // Create .htaccess to deny access
                        file_put_contents($dir . '/.htaccess', "Deny from all\n");
                    }
                    
                    // Clear session
                    unset($_SESSION['install_db']);
                    
                    // Redirect to complete
                    header('Location: install.php?step=4');
                    exit;
                } else {
                    $errors[] = "Could not write config file. Check permissions on app/config/";
                }
                
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Requirements check
function checkRequirements() {
    $requirements = [];
    
    // PHP Version
    $requirements['PHP Version (8.1+)'] = [
        'status' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'current' => PHP_VERSION
    ];
    
    // Required extensions
    $extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl', 'openssl'];
    foreach ($extensions as $ext) {
        $requirements["PHP Extension: $ext"] = [
            'status' => extension_loaded($ext),
            'current' => extension_loaded($ext) ? 'Loaded' : 'Not loaded'
        ];
    }
    
    // Writable directories
    $writableDirs = [
        'app/config' => __DIR__ . '/app/config',
        'cache' => __DIR__ . '/cache',
        'logs' => __DIR__ . '/logs'
    ];
    foreach ($writableDirs as $name => $path) {
        $writable = is_dir($path) ? is_writable($path) : is_writable(dirname($path));
        $requirements["Writable: $name"] = [
            'status' => $writable,
            'current' => $writable ? 'Writable' : 'Not writable'
        ];
    }
    
    // Schema file exists
    $requirements['Schema file exists'] = [
        'status' => file_exists(__DIR__ . '/database/schema.sql'),
        'current' => file_exists(__DIR__ . '/database/schema.sql') ? 'Found' : 'Missing'
    ];
    
    return $requirements;
}

$requirements = checkRequirements();
$canProceed = !in_array(false, array_column($requirements, 'status'));

// Auto-detect app URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['SCRIPT_NAME']);
$appUrl = $protocol . '://' . $host . ($path !== '/' ? $path : '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Bookmark Manager</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .installer {
            width: 100%;
            max-width: 600px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo svg {
            width: 48px;
            height: 48px;
            color: var(--primary);
        }
        
        .logo h1 {
            font-size: 1.5rem;
            margin-top: 0.5rem;
        }
        
        .steps {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 2rem;
            font-size: 0.875rem;
            color: var(--muted);
        }
        
        .step.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .step.complete {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }
        
        .step-num {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .alert-warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #d97706;
        }
        
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #059669;
        }
        
        .requirements {
            margin-bottom: 1.5rem;
        }
        
        .req-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }
        
        .req-item:last-child {
            border-bottom: none;
        }
        
        .req-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .req-status.pass { color: var(--success); }
        .req-status.fail { color: var(--danger); }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            background: var(--bg);
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-hint {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 0.25rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; }
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-primary:disabled {
            background: var(--muted);
            cursor: not-allowed;
        }
        
        .btn-block {
            width: 100%;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .success-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1.5rem;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .success-icon svg {
            width: 32px;
            height: 32px;
            color: white;
        }
        
        .success-text {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .success-text h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .success-text p {
            color: var(--muted);
        }
        
        .warning-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 1.5rem 0;
        }
        
        .warning-box strong {
            color: #b45309;
        }
        
        code {
            background: #f1f5f9;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-family: monospace;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"></path>
            </svg>
            <h1>Bookmark Manager</h1>
            <p style="color: var(--muted); font-size: 0.875rem;">Installation Wizard</p>
        </div>
        
        <?php if (!isset($alreadyInstalled)): ?>
        <div class="steps">
            <?php foreach ($steps as $num => $label): ?>
                <div class="step <?= $num < $currentStep ? 'complete' : ($num === $currentStep ? 'active' : '') ?>">
                    <span class="step-num"><?= $num < $currentStep ? '✓' : $num ?></span>
                    <span class="step-label"><?= $label ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <?php if (isset($alreadyInstalled)): ?>
                <div class="alert alert-warning">
                    <strong>Already Installed!</strong><br>
                    Bookmark Manager appears to be already installed. If you want to reinstall, 
                    delete or rename <code>app/config/config.json</code> first.
                </div>
                <div class="warning-box">
                    <strong>⚠️ Security Warning</strong><br>
                    Please delete this <code>install.php</code> file to prevent unauthorized access.
                </div>
                <a href="/" class="btn btn-primary btn-block">Go to Application</a>
                
            <?php elseif ($currentStep === 1): ?>
                <!-- Step 1: Requirements -->
                <h2 class="card-title">System Requirements</h2>
                
                <div class="requirements">
                    <?php foreach ($requirements as $name => $req): ?>
                        <div class="req-item">
                            <span><?= $name ?></span>
                            <span class="req-status <?= $req['status'] ? 'pass' : 'fail' ?>">
                                <?= $req['status'] ? '✓' : '✗' ?>
                                <?= $req['current'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!$canProceed): ?>
                    <div class="alert alert-danger">
                        Please fix the requirements marked with ✗ before continuing.
                    </div>
                <?php endif; ?>
                
                <a href="install.php?step=2" class="btn btn-primary btn-block" <?= !$canProceed ? 'style="pointer-events:none;opacity:0.5"' : '' ?>>
                    Continue →
                </a>
                
            <?php elseif ($currentStep === 2): ?>
                <!-- Step 2: Database -->
                <h2 class="card-title">Database Configuration</h2>
                
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Database Host</label>
                            <input type="text" name="db_host" class="form-input" value="<?= $_POST['db_host'] ?? 'localhost' ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Port</label>
                            <input type="number" name="db_port" class="form-input" value="<?= $_POST['db_port'] ?? '3306' ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Database Name</label>
                        <input type="text" name="db_name" class="form-input" value="<?= $_POST['db_name'] ?? 'bookmark_manager' ?>" required>
                        <p class="form-hint">Will be created if it doesn't exist</p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Database Username</label>
                            <input type="text" name="db_user" class="form-input" value="<?= $_POST['db_user'] ?? '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Database Password</label>
                            <input type="password" name="db_pass" class="form-input">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        Test Connection & Create Tables →
                    </button>
                </form>
                
            <?php elseif ($currentStep === 3): ?>
                <!-- Step 3: Admin Account -->
                <h2 class="card-title">Create Admin Account</h2>
                
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Application URL</label>
                        <input type="url" name="app_url" class="form-input" value="<?= $_POST['app_url'] ?? $appUrl ?>" required>
                        <p class="form-hint">The full URL where your app will be accessible</p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Admin Username</label>
                            <input type="text" name="admin_user" class="form-input" value="<?= $_POST['admin_user'] ?? 'admin' ?>" required minlength="3">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Admin Email</label>
                            <input type="email" name="admin_email" class="form-input" value="<?= $_POST['admin_email'] ?? '' ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="admin_pass" class="form-input" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="admin_pass_confirm" class="form-input" required minlength="6">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        Complete Installation →
                    </button>
                </form>
                
            <?php elseif ($currentStep === 4): ?>
                <!-- Step 4: Complete -->
                <div class="success-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                
                <div class="success-text">
                    <h2>Installation Complete!</h2>
                    <p>Bookmark Manager has been successfully installed.</p>
                </div>
                
                <div class="warning-box">
                    <strong>⚠️ IMPORTANT: Delete this file!</strong><br>
                    For security, please delete <code>install.php</code> immediately:<br><br>
                    <code>rm <?= __FILE__ ?></code>
                </div>
                
                <a href="/" class="btn btn-primary btn-block">
                    Go to Bookmark Manager →
                </a>
                
            <?php endif; ?>
        </div>
        
        <p style="text-align: center; margin-top: 1.5rem; font-size: 0.75rem; color: var(--muted);">
            Bookmark Manager v1.1.0
        </p>
    </div>
</body>
</html>
