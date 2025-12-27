<?php
/**
 * Bookmarks Index Page
 * Displays paginated list of bookmarks with filters
 */

use App\Core\View;

$bookmarks = $bookmarks ?? ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
$categories = $categories ?? [];
$tags = $tags ?? [];
$stats = $stats ?? [];
$filters = $filters ?? [];

// Current sort/filter values
$currentSort = $_GET['sort'] ?? 'newest';
$currentCategory = $_GET['category'] ?? '';
$currentFavorites = isset($_GET['favorites']);
$currentArchived = isset($_GET['archived']);

// Build query string helper
function buildQueryString(array $params): string {
    $current = $_GET;
    foreach ($params as $key => $value) {
        if ($value === null) {
            unset($current[$key]);
        } else {
            $current[$key] = $value;
        }
    }
    unset($current['page']); // Reset page on filter change
    return http_build_query($current);
}
?>

<div class="bookmarks-page">
    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stat-item">
            <span class="stat-value"><?= number_format($stats['total'] ?? 0) ?></span>
            <span class="stat-label">Total</span>
        </div>
        <div class="stat-item">
            <span class="stat-value"><?= number_format($stats['favorites'] ?? 0) ?></span>
            <span class="stat-label">Favorites</span>
        </div>
        <div class="stat-item">
            <span class="stat-value"><?= number_format($stats['archived'] ?? 0) ?></span>
            <span class="stat-label">Archived</span>
        </div>
        
        <div class="stats-actions">
            <a href="/bookmarks/create" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Bookmark
            </a>
        </div>
    </div>
    
    <!-- Filter & Sort Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <!-- Sort Dropdown -->
            <div class="filter-item">
                <label for="sort-select">Sort by:</label>
                <select id="sort-select" onchange="window.location.href='?'+this.value">
                    <option value="<?= buildQueryString(['sort' => 'newest']) ?>" <?= $currentSort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="<?= buildQueryString(['sort' => 'oldest']) ?>" <?= $currentSort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="<?= buildQueryString(['sort' => 'title']) ?>" <?= $currentSort === 'title' ? 'selected' : '' ?>>Title A-Z</option>
                    <option value="<?= buildQueryString(['sort' => 'title_desc']) ?>" <?= $currentSort === 'title_desc' ? 'selected' : '' ?>>Title Z-A</option>
                    <option value="<?= buildQueryString(['sort' => 'visited']) ?>" <?= $currentSort === 'visited' ? 'selected' : '' ?>>Most Visited</option>
                    <option value="<?= buildQueryString(['sort' => 'recent_visit']) ?>" <?= $currentSort === 'recent_visit' ? 'selected' : '' ?>>Recently Visited</option>
                </select>
            </div>
            
            <!-- Category Filter -->
            <?php if (!empty($categories)): ?>
            <div class="filter-item">
                <label for="category-select">Category:</label>
                <select id="category-select" onchange="window.location.href='?'+this.value">
                    <option value="<?= buildQueryString(['category' => null]) ?>">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= buildQueryString(['category' => $cat['id']]) ?>" <?= $currentCategory == $cat['id'] ? 'selected' : '' ?>><?= View::e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="filter-toggles">
            <!-- Favorites Toggle -->
            <a href="?<?= buildQueryString(['favorites' => $currentFavorites ? null : '1']) ?>" 
               class="filter-toggle <?= $currentFavorites ? 'active' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $currentFavorites ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                </svg>
                Favorites
            </a>
            
            <!-- Archived Toggle -->
            <a href="?<?= buildQueryString(['archived' => $currentArchived ? null : '1']) ?>" 
               class="filter-toggle <?= $currentArchived ? 'active' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="21 8 21 21 3 21 3 8"></polyline>
                    <rect x="1" y="3" width="22" height="5"></rect>
                    <line x1="10" y1="12" x2="14" y2="12"></line>
                </svg>
                Archived
            </a>
            
            <!-- Search Page Link -->
            <a href="/search" class="filter-toggle">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                Advanced Search
            </a>
        </div>
    </div>
    
    <!-- Active Filters -->
    <?php if (!empty($filters) || $currentCategory || $currentFavorites || $currentArchived): ?>
        <div class="active-filters">
            <span class="filters-label">Active filters:</span>
            <?php if (!empty($filters['category_id'])): ?>
                <a href="?" class="filter-tag">
                    Category
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </a>
            <?php endif; ?>
            <?php if (!empty($filters['is_favorite'])): ?>
                <a href="?" class="filter-tag">
                    Favorites
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </a>
            <?php endif; ?>
            <a href="/bookmarks" class="filter-clear">Clear all</a>
        </div>
    <?php endif; ?>
    
    <!-- Bookmarks Grid -->
    <?php if (!empty($bookmarks['items'])): ?>
        <div class="bookmarks-grid">
            <?php foreach ($bookmarks['items'] as $bookmark): ?>
                <?php View::component('bookmark-card', ['bookmark' => $bookmark]); ?>
            <?php endforeach; ?>
        </div>
        
        <?php View::component('pagination', [
            'page'       => $bookmarks['page'],
            'totalPages' => $bookmarks['total_pages'],
            'baseUrl'    => '/bookmarks'
        ]); ?>
    <?php else: ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"></path>
            </svg>
            <h3>No bookmarks yet</h3>
            <p>Start by adding your first bookmark</p>
            <a href="/bookmarks/create" class="btn btn-primary">Add Bookmark</a>
        </div>
    <?php endif; ?>
</div>

<style>
.bookmarks-page {
    max-width: 1400px;
}

.stats-bar {
    display: flex;
    align-items: center;
    gap: 2rem;
    padding: 1rem 1.25rem;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    margin-bottom: 1rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text);
}

.stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.stats-actions {
    margin-left: auto;
}

/* Filter Bar */
.filter-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.filter-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-item label {
    font-size: 0.8125rem;
    color: var(--text-muted);
    white-space: nowrap;
}

.filter-item select {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--bg);
    color: var(--text);
    cursor: pointer;
    min-width: 140px;
}

.filter-item select:focus {
    outline: none;
    border-color: var(--primary);
}

.filter-toggles {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    color: var(--text-muted);
    text-decoration: none;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--bg);
    transition: all 0.15s;
}

.filter-toggle:hover {
    background: var(--bg-card);
    border-color: var(--primary);
    color: var(--primary);
}

.filter-toggle.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: var(--radius);
    cursor: pointer;
    transition: background 0.15s;
}

.btn-primary {
    background: var(--primary);
    color: white;
    border: none;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.active-filters {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.filters-label {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.filter-tag {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    background: var(--primary);
    color: white;
    border-radius: 9999px;
    text-decoration: none;
}

.filter-clear {
    font-size: 0.75rem;
    color: var(--primary);
    text-decoration: none;
    margin-left: 0.5rem;
}

.bookmarks-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 640px) {
    .bookmarks-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .bookmarks-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 640px) {
    .bookmarks-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .stats-bar {
        flex-wrap: wrap;
        gap: 1rem;
    }
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    color: var(--text-muted);
}

.empty-state svg {
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 0.5rem;
}

.empty-state p {
    margin-bottom: 1.5rem;
}
</style>
