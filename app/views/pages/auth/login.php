<?php
/**
 * Login Page
 */

use App\Core\View;
use App\Helpers\Csrf;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME ?></title>
    <?= Csrf::meta() ?>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg: #f8fafc;
            --bg-card: #ffffff;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --danger: #ef4444;
            --radius: 0.5rem;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            font-weight: 600;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: background 0.15s;
        }
        
        .btn-login:hover {
            background: var(--primary-dark);
        }
        
        .flash-error {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: var(--radius);
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"></path>
                </svg>
            </div>
            <h1 class="login-title"><?= View::e(APP_NAME) ?></h1>
        </div>
        
        <div class="login-card">
            <?php 
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);
            if ($flash && $flash['type'] === 'error'): 
            ?>
                <div class="flash-error"><?= View::e($flash['message']) ?></div>
            <?php endif; ?>
            
            <form action="/login" method="POST">
                <?= Csrf::field() ?>
                
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        class="form-input" 
                        required 
                        autofocus
                        autocomplete="username"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-input" 
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="btn-login">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
