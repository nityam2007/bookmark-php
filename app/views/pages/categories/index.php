<?php
/**
 * Categories Index Page
 */

use App\Core\View;
use App\Helpers\Csrf;
?>

<div class="categories-page">
    <div class="page-header">
        <h1>Categories</h1>
        <a href="/categories/create" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add Category
        </a>
    </div>

    <?php if (empty($categories)): ?>
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"></path>
            </svg>
            <h3>No categories yet</h3>
            <p>Organize your bookmarks by creating categories.</p>
            <a href="/categories/create" class="btn btn-primary">Create First Category</a>
        </div>
    <?php else: ?>
        <div class="categories-tree">
            <?php function renderCategory($category, $level = 0) { ?>
                <div class="category-item" style="margin-left: <?= $level * 24 ?>px">
                    <div class="category-content">
                        <div class="category-info">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"></path>
                            </svg>
                            <a href="/categories/<?= $category['id'] ?>" class="category-name"><?= View::escape($category['name']) ?></a>
                            <?php if (!empty($category['description'])): ?>
                                <span class="category-desc">— <?= View::escape($category['description']) ?></span>
                            <?php endif; ?>
                            <span class="category-count">(<?= $category['bookmark_count'] ?? 0 ?> bookmarks)</span>
                        </div>
                        <div class="category-actions">
                            <a href="/categories/<?= $category['id'] ?>" class="btn btn-sm btn-ghost" title="View bookmarks">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                            <a href="/categories/create?parent=<?= $category['id'] ?>" class="btn btn-sm btn-ghost" title="Add subcategory">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </a>
                            <a href="/categories/<?= $category['id'] ?>/edit" class="btn btn-sm btn-ghost" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>
                            <form action="/categories/<?= $category['id'] ?>/delete" method="POST" class="inline-form" onsubmit="return confirm('Delete this category? Bookmarks will be moved to Uncategorized.')">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn btn-sm btn-ghost text-danger" title="Delete">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php if (!empty($category['children'])): ?>
                        <?php foreach ($category['children'] as $child): ?>
                            <?php renderCategory($child, $level + 1); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php } ?>
            
            <?php foreach ($categories as $category): ?>
                <?php renderCategory($category); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.categories-page {
    max-width: 800px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background: white;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 0.5rem;
}

.empty-state svg {
    color: var(--text-muted, #64748b);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: var(--text-muted, #64748b);
    margin-bottom: 1.5rem;
}

.categories-tree {
    background: white;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 0.5rem;
    padding: 1rem;
}

.category-item {
    padding: 0.5rem 0;
}

.category-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    transition: background 0.2s;
}

.category-content:hover {
    background: #f8fafc;
}

.category-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.category-info svg {
    color: var(--primary, #2563eb);
}

.category-name {
    font-weight: 500;
}

.category-desc {
    color: var(--text-muted, #64748b);
    font-size: 0.875rem;
}

.category-count {
    font-size: 0.75rem;
    color: var(--text-muted, #64748b);
}

.category-actions {
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.category-content:hover .category-actions {
    opacity: 1;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.btn-ghost {
    background: transparent;
    border: 1px solid transparent;
    color: var(--text-muted, #64748b);
}

.btn-ghost:hover {
    background: #f1f5f9;
    color: var(--text, #1e293b);
}

.text-danger {
    color: #dc2626 !important;
}

.inline-form {
    display: inline;
}
</style>
