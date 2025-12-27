<?php
/**
 * Header Component
 * Reusable page header with search and user menu
 */

use App\Core\View;
?>

<header class="page-header">
    <div class="header-left">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        
        <h1 class="page-title"><?= View::e($title ?? 'Dashboard') ?></h1>
    </div>
    
    <div class="header-center">
        <?php View::component('searchbar'); ?>
    </div>
    
    <div class="header-right">
        <?php if (!empty($currentUser)): ?>
            <div class="user-menu">
                <button class="user-menu-toggle" id="userMenuToggle">
                    <span class="user-avatar">
                        <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)) ?>
                    </span>
                    <span class="user-name"><?= View::e($currentUser['username'] ?? 'User') ?></span>
                </button>
                
                <div class="user-dropdown" id="userDropdown">
                    <a href="/settings" class="dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"></path>
                        </svg>
                        Settings
                    </a>
                    <hr class="dropdown-divider">
                    <form action="/logout" method="POST" class="dropdown-form">
                        <?= \App\Helpers\Csrf::field() ?>
                        <button type="submit" class="dropdown-item dropdown-item-danger">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"></path>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</header>

<style>
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 0;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid var(--border);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.sidebar-toggle {
    display: none;
    padding: 0.5rem;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text);
}

@media (max-width: 768px) {
    .sidebar-toggle { display: block; }
}

.page-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text);
}

.header-center {
    flex: 1;
    max-width: 500px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-menu {
    position: relative;
}

.user-menu-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    background: none;
    border: none;
    cursor: pointer;
    border-radius: var(--radius);
}

.user-menu-toggle:hover {
    background: var(--border);
}

.user-avatar {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    font-weight: 600;
    font-size: 0.875rem;
}

.user-name {
    font-weight: 500;
}

@media (max-width: 640px) {
    .user-name { display: none; }
}

.user-dropdown {
    position: absolute;
    right: 0;
    top: 100%;
    min-width: 180px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    display: none;
    z-index: 100;
}

.user-dropdown.active {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    color: var(--text);
    text-decoration: none;
    width: 100%;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
}

.dropdown-item:hover {
    background: var(--bg);
}

.dropdown-item-danger {
    color: var(--danger);
}

.dropdown-divider {
    border: none;
    border-top: 1px solid var(--border);
    margin: 0.25rem 0;
}

.dropdown-form {
    margin: 0;
}
</style>
