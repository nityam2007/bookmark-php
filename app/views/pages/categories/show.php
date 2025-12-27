<?php
/**
 * Category Show Page - Display bookmarks in a category
 */

use App\Core\View;
use App\Helpers\Csrf;

$category = $category ?? [];
$categoryPath = $categoryPath ?? '';
$subcategories = $subcategories ?? [];
$bookmarks = $bookmarks ?? [];
$pagination = $pagination ?? [];
$sort = $sort ?? 'newest';
$filters = $filters ?? [];
?>

<div class="category-show-page">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="/categories">Categories</a>
        <span class="separator">/</span>
        <span class="current"><?= View::e($categoryPath) ?></span>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h1>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"></path>
                </svg>
                <?= View::e($category['name']) ?>
            </h1>
            <?php if (!empty($category['description'])): ?>
                <p class="category-description"><?= View::e($category['description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="header-actions">
            <a href="/bookmarks/create?category=<?= $category['id'] ?>" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Bookmark
            </a>
            <a href="/categories/<?= $category['id'] ?>/edit" class="btn btn-ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit
            </a>
        </div>
    </div>

    <!-- Subcategories -->
    <?php if (!empty($subcategories)): ?>
        <div class="subcategories-section">
            <h3>Subcategories</h3>
            <div class="subcategories-grid">
                <?php foreach ($subcategories as $sub): ?>
                    <a href="/categories/<?= $sub['id'] ?>" class="subcategory-card">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"></path>
                        </svg>
                        <span class="name"><?= View::e($sub['name']) ?></span>
                        <span class="count"><?= $sub['bookmark_count'] ?? 0 ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <form method="GET" action="/categories/<?= $category['id'] ?>" class="filter-bar">
        <div class="filter-group">
            <label for="sort">Sort by:</label>
            <select name="sort" id="sort" onchange="this.form.submit()">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Title A-Z</option>
                <option value="visited" <?= $sort === 'visited' ? 'selected' : '' ?>>Most Visited</option>
                <option value="recent_visit" <?= $sort === 'recent_visit' ? 'selected' : '' ?>>Recently Visited</option>
            </select>
        </div>
        
        <div class="filter-toggles">
            <label class="toggle-label">
                <input type="checkbox" name="favorites" <?= isset($filters['is_favorite']) ? 'checked' : '' ?> onchange="this.form.submit()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                </svg>
                Favorites
            </label>
            <label class="toggle-label">
                <input type="checkbox" name="archived" <?= isset($filters['is_archived']) ? 'checked' : '' ?> onchange="this.form.submit()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="21 8 21 21 3 21 3 8"></polyline>
                    <rect x="1" y="3" width="22" height="5"></rect>
                </svg>
                Archived
            </label>
        </div>

        <div class="filter-stats">
            <span class="count"><?= $pagination['total'] ?? 0 ?> bookmark<?= ($pagination['total'] ?? 0) !== 1 ? 's' : '' ?></span>
        </div>
    </form>

    <!-- Bookmarks Grid -->
    <?php if (empty($bookmarks)): ?>
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
            </svg>
            <h3>No bookmarks in this category</h3>
            <p>Add your first bookmark to this category.</p>
            <a href="/bookmarks/create?category=<?= $category['id'] ?>" class="btn btn-primary">Add Bookmark</a>
        </div>
    <?php else: ?>
        <div class="bookmarks-grid">
            <?php foreach ($bookmarks as $bookmark): ?>
                <?php include dirname(__DIR__, 2) . '/components/bookmark-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
            <?php 
            $currentPage = $pagination['page'] ?? 1;
            $totalPages = $pagination['total_pages'] ?? 1;
            $baseUrl = '/categories/' . $category['id'] . '?' . http_build_query(array_filter([
                'sort' => $sort !== 'newest' ? $sort : null,
                'favorites' => isset($filters['is_favorite']) ? '1' : null,
                'archived' => isset($filters['is_archived']) ? '1' : null,
            ]));
            ?>
            <nav class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="<?= $baseUrl ?>&page=<?= $currentPage - 1 ?>" class="page-link">← Previous</a>
                <?php endif; ?>
                
                <span class="page-info">Page <?= $currentPage ?> of <?= $totalPages ?></span>
                
                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= $baseUrl ?>&page=<?= $currentPage + 1 ?>" class="page-link">Next →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.category-show-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1.5rem;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.breadcrumb a {
    color: var(--primary);
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.breadcrumb .separator {
    color: var(--border);
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.page-header h1 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
    font-size: 1.75rem;
}

.page-header h1 svg {
    color: var(--primary);
}

.category-description {
    margin: 0.5rem 0 0;
    color: var(--text-muted);
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

/* Subcategories */
.subcategories-section {
    margin-bottom: 2rem;
}

.subcategories-section h3 {
    font-size: 1rem;
    margin-bottom: 0.75rem;
    color: var(--text-muted);
}

.subcategories-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.subcategory-card {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    text-decoration: none;
    color: var(--text);
    transition: all 0.2s ease;
}

.subcategory-card:hover {
    border-color: var(--primary);
    background: var(--hover-bg);
}

.subcategory-card svg {
    color: var(--primary);
    flex-shrink: 0;
}

.subcategory-card .name {
    font-weight: 500;
}

.subcategory-card .count {
    font-size: 0.75rem;
    color: var(--text-muted);
    background: var(--bg);
    padding: 0.125rem 0.5rem;
    border-radius: 999px;
}

/* Filter Bar */
.filter-bar {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1rem;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-group label {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.filter-group select {
    padding: 0.375rem 0.75rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--bg);
    color: var(--text);
    font-size: 0.875rem;
}

.filter-toggles {
    display: flex;
    gap: 1rem;
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    cursor: pointer;
    color: var(--text-muted);
}

.toggle-label input:checked + svg {
    color: var(--primary);
}

.toggle-label input:checked ~ span,
.toggle-label:has(input:checked) {
    color: var(--text);
}

.filter-stats {
    margin-left: auto;
    font-size: 0.875rem;
    color: var(--text-muted);
}

/* Bookmarks Grid */
.bookmarks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--card-bg);
    border: 1px dashed var(--border);
    border-radius: var(--radius);
}

.empty-state svg {
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0 0 0.5rem;
}

.empty-state p {
    color: var(--text-muted);
    margin-bottom: 1.5rem;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border);
}

.page-link {
    padding: 0.5rem 1rem;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    text-decoration: none;
    color: var(--text);
    font-size: 0.875rem;
}

.page-link:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.page-info {
    font-size: 0.875rem;
    color: var(--text-muted);
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
    }
    
    .header-actions {
        width: 100%;
    }
    
    .header-actions .btn {
        flex: 1;
        justify-content: center;
    }
    
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-stats {
        margin-left: 0;
        text-align: center;
    }
    
    .bookmarks-grid {
        grid-template-columns: 1fr;
    }
}
</style>
