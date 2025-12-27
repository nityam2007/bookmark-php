<?php
/**
 * Search Page
 * Dedicated search page with full results and AJAX support
 */

use App\Core\View;

$query = $query ?? '';
$results = $results ?? ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
$categories = $categories ?? [];
$currentCategory = $_GET['category'] ?? '';
$currentSort = $_GET['sort'] ?? 'relevance';
?>

<div class="search-page">
    <!-- Search Form -->
    <div class="search-header">
        <form class="search-form-full" method="GET" action="/search" id="searchForm">
            <div class="search-input-group">
                <svg class="search-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input 
                    type="search" 
                    name="q" 
                    id="searchQueryInput"
                    class="search-input-full" 
                    placeholder="Search bookmarks by title, URL, description..." 
                    value="<?= View::e($query) ?>"
                    autocomplete="off"
                    autofocus
                >
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
            
            <div class="search-filters">
                <!-- Category Filter -->
                <div class="filter-item">
                    <label for="category">Category:</label>
                    <select name="category" id="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $currentCategory == $cat['id'] ? 'selected' : '' ?>><?= View::e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Sort -->
                <div class="filter-item">
                    <label for="sort">Sort by:</label>
                    <select name="sort" id="sort">
                        <option value="relevance" <?= $currentSort === 'relevance' ? 'selected' : '' ?>>Relevance</option>
                        <option value="newest" <?= $currentSort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $currentSort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="title" <?= $currentSort === 'title' ? 'selected' : '' ?>>Title A-Z</option>
                    </select>
                </div>
                
                <!-- Favorites -->
                <label class="filter-checkbox">
                    <input type="checkbox" name="favorites" <?= isset($_GET['favorites']) ? 'checked' : '' ?>>
                    <span>Favorites Only</span>
                </label>
            </div>
        </form>
    </div>
    
    <!-- Results Count -->
    <?php if ($query): ?>
        <div class="search-meta">
            <span class="search-count">
                <?= number_format($results['total']) ?> result<?= $results['total'] !== 1 ? 's' : '' ?> 
                for "<strong><?= View::e($query) ?></strong>"
            </span>
            <?php if (!empty($results['method'])): ?>
                <span class="search-method">(<?= $results['method'] === 'fulltext' ? 'Full-text search' : 'Text search' ?>)</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Results Grid -->
    <div id="searchResultsContainer">
        <?php if (!empty($results['items'])): ?>
            <div class="search-results-grid">
                <?php foreach ($results['items'] as $bookmark): ?>
                    <?php View::component('bookmark-card', ['bookmark' => $bookmark]); ?>
                <?php endforeach; ?>
            </div>
            
            <?php View::component('pagination', [
                'page'       => $results['page'],
                'totalPages' => $results['total_pages'],
                'baseUrl'    => '/search',
                'queryParams' => array_filter([
                    'q' => $query,
                    'category' => $currentCategory,
                    'sort' => $currentSort !== 'relevance' ? $currentSort : null,
                    'favorites' => isset($_GET['favorites']) ? '1' : null
                ])
            ]); ?>
        <?php elseif ($query): ?>
            <div class="empty-search">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <h3>No results found</h3>
                <p>Try different keywords or remove filters</p>
            </div>
        <?php else: ?>
            <div class="search-tips">
                <h3>Search Tips</h3>
                <ul>
                    <li>Use specific keywords for better results</li>
                    <li>Search by URL domain (e.g., "github.com")</li>
                    <li>Filter by category to narrow results</li>
                    <li>Use Ctrl+K or Cmd+K for quick search anywhere</li>
                </ul>
                
                <h4>Recent Searches</h4>
                <div id="recentSearches" class="recent-searches">
                    <!-- Populated by JS -->
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.search-page {
    max-width: 1400px;
}

.search-header {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.search-form-full {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.search-input-group {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    position: relative;
}

.search-input-group .search-icon {
    position: absolute;
    left: 1rem;
    color: var(--text-muted);
    pointer-events: none;
}

.search-input-full {
    flex: 1;
    padding: 0.875rem 1rem 0.875rem 3rem;
    font-size: 1rem;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    background: var(--bg);
    color: var(--text);
    transition: border-color 0.15s, box-shadow 0.15s;
}

.search-input-full:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-filters {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1rem;
}

.search-filters .filter-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.search-filters label {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.search-filters select {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--bg);
    color: var(--text);
}

.filter-checkbox {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    cursor: pointer;
    font-size: 0.875rem;
}

.filter-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
}

.search-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--border);
}

.search-count {
    font-size: 0.9375rem;
    color: var(--text);
}

.search-method {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.search-results-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (min-width: 640px) {
    .search-results-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .search-results-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.empty-search {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    color: var(--text-muted);
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
}

.empty-search h3 {
    margin: 1rem 0 0.5rem;
    font-size: 1.25rem;
    color: var(--text);
}

.empty-search svg {
    opacity: 0.5;
}

.search-tips {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 2rem;
}

.search-tips h3 {
    margin-bottom: 1rem;
    color: var(--text);
}

.search-tips h4 {
    margin: 1.5rem 0 0.75rem;
    color: var(--text);
}

.search-tips ul {
    margin: 0;
    padding-left: 1.5rem;
    color: var(--text-muted);
}

.search-tips li {
    margin-bottom: 0.5rem;
}

.recent-searches {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.recent-search-item {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    color: var(--text);
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 9999px;
    text-decoration: none;
    transition: border-color 0.15s;
}

.recent-search-item:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-size: 0.9375rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: var(--radius);
    cursor: pointer;
    border: none;
    transition: background 0.15s;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
}
</style>

<script>
// Save search to recent
document.getElementById('searchForm').addEventListener('submit', function() {
    const query = document.getElementById('searchQueryInput').value.trim();
    if (query) {
        let recent = JSON.parse(localStorage.getItem('recentSearches') || '[]');
        recent = recent.filter(q => q !== query);
        recent.unshift(query);
        recent = recent.slice(0, 5);
        localStorage.setItem('recentSearches', JSON.stringify(recent));
    }
});

// Show recent searches
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('recentSearches');
    if (!container) return;
    
    const recent = JSON.parse(localStorage.getItem('recentSearches') || '[]');
    if (recent.length === 0) {
        container.innerHTML = '<p style="color: var(--text-muted); font-size: 0.875rem;">No recent searches</p>';
        return;
    }
    
    container.innerHTML = recent.map(q => 
        `<a href="/search?q=${encodeURIComponent(q)}" class="recent-search-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="1 4 1 10 7 10"></polyline>
                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
            </svg>
            ${q}
        </a>`
    ).join('');
});
</script>
