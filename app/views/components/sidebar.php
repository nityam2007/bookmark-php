<?php
/**
 * Sidebar Component
 * Navigation sidebar with categories
 */

use App\Core\View;
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="/" class="logo">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"></path>
            </svg>
            <span class="logo-text"><?= View::e(APP_NAME) ?></span>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="/bookmarks" class="nav-link <?= ($_SERVER['REQUEST_URI'] ?? '') === '/bookmarks' ? 'active' : '' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"></path>
                    </svg>
                    All Bookmarks
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/bookmarks?favorites=1" class="nav-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                    </svg>
                    Favorites
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/bookmarks?archived=1" class="nav-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 8v13H3V8M1 3h22v5H1zM10 12h4"></path>
                    </svg>
                    Archived
                </a>
            </li>
        </ul>
        
        <div class="nav-section">
            <div class="nav-section-header">
                <span>Categories</span>
                <a href="/categories/create" class="btn-icon" title="Add Category">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </a>
            </div>
            
            <ul class="category-tree" id="categoryTree">
                <!-- Categories loaded dynamically -->
                <li class="nav-item">
                    <a href="/categories" class="nav-link nav-link-muted">
                        Manage Categories
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-header">
                <span>Tools</span>
            </div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="/bookmarks/create" class="nav-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add Bookmark
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/import" class="nav-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"></path>
                        </svg>
                        Import
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/export?format=json" class="nav-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"></path>
                        </svg>
                        Export
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/settings" class="nav-link <?= (($_SERVER['REQUEST_URI'] ?? '') === '/settings') ? 'active' : '' ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        Settings
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>

<style>
.sidebar {
    width: 260px;
    min-width: 260px;
    max-width: 260px;
    height: 100vh;
    position: sticky;
    top: 0;
    background: var(--bg-card, #ffffff);
    border-right: 1px solid var(--border, #e2e8f0);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    flex-shrink: 0; /* Prevent sidebar from shrinking */
    margin-right: 0; /* No margin */
}

@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        left: -260px;
        z-index: 1000;
        transition: left 0.3s ease;
    }
    
    .sidebar.open {
        left: 0;
    }
}

.sidebar-header {
    padding: 1.25rem;
    border-bottom: 1px solid var(--border);
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: var(--text);
}

.logo svg {
    color: var(--primary);
}

.logo-text {
    font-weight: 700;
    font-size: 1.125rem;
}

.sidebar-nav {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
}

.nav-list {
    list-style: none;
    padding: 0 0.75rem;
}

.nav-item {
    margin-bottom: 0.25rem;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 0.75rem;
    color: var(--text);
    text-decoration: none;
    border-radius: var(--radius);
    font-size: 0.875rem;
    transition: background 0.15s;
}

.nav-link:hover {
    background: var(--bg);
}

.nav-link.active {
    background: var(--primary);
    color: white;
}

.nav-link-muted {
    color: var(--text-muted);
    font-size: 0.8125rem;
}

.nav-section {
    margin-top: 1.5rem;
}

.nav-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: 0.05em;
}

.btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: var(--radius);
    color: var(--text-muted);
    text-decoration: none;
}

.btn-icon:hover {
    background: var(--border);
    color: var(--text);
}

.category-tree {
    list-style: none;
    padding: 0 0.75rem;
}
</style>
