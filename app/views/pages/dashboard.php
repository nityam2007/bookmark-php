<?php
/**
 * Dashboard Page
 */

use App\Core\View;

$stats = $stats ?? [];
$recentBookmarks = $recentBookmarks ?? [];
$categoriesFlat = $categoriesFlat ?? [];
?>

<div class="dashboard">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon-primary">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($stats['total'] ?? 0) ?></span>
                <span class="stat-label">Total Bookmarks</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon stat-icon-warning">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($stats['favorites'] ?? 0) ?></span>
                <span class="stat-label">Favorites</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon stat-icon-secondary">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($stats['categories'] ?? 0) ?></span>
                <span class="stat-label">Categories</span>
            </div>
        </div>
        
        <div class="stat-card stat-card-action">
            <a href="/bookmarks/create" class="quick-add">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Add Bookmark</span>
            </a>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <!-- Recent Bookmarks -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Recent Bookmarks</h2>
                <a href="/bookmarks" class="card-link">View all</a>
            </div>
            
            <?php if (!empty($recentBookmarks)): ?>
                <ul class="bookmark-list">
                    <?php foreach (array_slice($recentBookmarks, 0, 8) as $bookmark): ?>
                        <li class="bookmark-list-item">
                            <img 
                                src="<?= View::e($bookmark['favicon'] ?? '/img/default-favicon.svg') ?>" 
                                alt=""
                                class="bookmark-list-favicon"
                                onerror="this.src='/img/default-favicon.svg'"
                            >
                            <div class="bookmark-list-content">
                                <a href="/bookmarks/<?= $bookmark['id'] ?>/visit" class="bookmark-list-title" target="_blank">
                                    <?= View::e($bookmark['title'] ?? 'Untitled') ?>
                                </a>
                                <span class="bookmark-list-url"><?= View::e(parse_url($bookmark['url'], PHP_URL_HOST)) ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="card-empty">No bookmarks yet</p>
            <?php endif; ?>
        </div>
        
        <!-- Categories -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Categories</h2>
                <a href="/categories" class="card-link">Manage</a>
            </div>
            
            <?php if (!empty($categoriesFlat)): ?>
                <div class="category-tree-list">
                    <?php foreach ($categoriesFlat as $cat): 
                        $indent = ($cat['depth'] ?? 0) * 16;
                        $bookmarkCount = $cat['bookmark_count'] ?? 0;
                    ?>
                        <a href="/bookmarks?category=<?= $cat['id'] ?>" class="category-tree-item" style="padding-left: <?= 12 + $indent ?>px;">
                            <span class="category-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                </svg>
                            </span>
                            <span class="category-name"><?= View::e($cat['name']) ?></span>
                            <?php if ($bookmarkCount > 0): ?>
                                <span class="category-count"><?= $bookmarkCount ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card-empty">
                    <p>No categories yet</p>
                    <a href="/categories/create" class="btn btn-sm btn-primary" style="margin-top: 0.5rem;">Create Category</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dashboard {
    max-width: 1200px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
}

.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: var(--radius);
}

.stat-icon-primary {
    background: #eff6ff;
    color: var(--primary);
}

.stat-icon-warning {
    background: #fffbeb;
    color: var(--warning);
}

.stat-icon-secondary {
    background: #f1f5f9;
    color: var(--secondary);
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
}

.stat-label {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.stat-card-action {
    border-style: dashed;
}

.quick-add {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
}

.quick-add:hover {
    color: var(--primary-dark);
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

.dashboard-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border);
}

.card-title {
    font-size: 1rem;
    font-weight: 600;
}

.card-link {
    font-size: 0.875rem;
    color: var(--primary);
    text-decoration: none;
}

.card-empty {
    padding: 2rem;
    text-align: center;
    color: var(--text-muted);
}

.bookmark-list {
    list-style: none;
}

.bookmark-list-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid var(--border);
}

.bookmark-list-item:last-child {
    border-bottom: none;
}

.bookmark-list-favicon {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    flex-shrink: 0;
}

.bookmark-list-content {
    flex: 1;
    min-width: 0;
}

.bookmark-list-title {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text);
    text-decoration: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bookmark-list-title:hover {
    color: var(--primary);
}

.bookmark-list-url {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.tag-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
}

.tag-item {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    background: var(--bg);
    color: var(--text);
    text-decoration: none;
    border-radius: 9999px;
    transition: background 0.15s;
}

.tag-item:hover {
    background: var(--primary);
    color: white;
}

.tag-count {
    font-size: 0.6875rem;
    opacity: 0.7;
}

.tag-size-1 { font-size: 0.75rem; }
.tag-size-2 { font-size: 0.8125rem; }
.tag-size-3 { font-size: 0.875rem; }
.tag-size-4 { font-size: 0.9375rem; }
.tag-size-5 { font-size: 1rem; font-weight: 500; }

/* Category Tree Styles */
.category-tree-list {
    max-height: 400px;
    overflow-y: auto;
}

.category-tree-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 0.75rem;
    color: var(--text);
    text-decoration: none;
    border-bottom: 1px solid var(--border);
    transition: background 0.15s;
}

.category-tree-item:last-child {
    border-bottom: none;
}

.category-tree-item:hover {
    background: var(--bg);
}

.category-icon {
    display: flex;
    align-items: center;
    color: var(--text-muted);
    flex-shrink: 0;
}

.category-name {
    flex: 1;
    font-size: 0.875rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.category-count {
    font-size: 0.75rem;
    color: var(--text-muted);
    background: var(--bg);
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
}

.btn-sm {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 500;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
}
</style>
