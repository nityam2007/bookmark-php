<?php
/**
 * Pagination Component
 * Reusable pagination with keyboard navigation support
 * 
 * @param int $page - Current page
 * @param int $totalPages - Total number of pages
 * @param string $baseUrl - Base URL for pagination links
 */

use App\Core\View;

$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$baseUrl = $baseUrl ?? '?';

if ($totalPages <= 1) return;

// Determine visible page range
$maxVisible = MAX_PAGES_SHOWN;
$half = floor($maxVisible / 2);
$start = max(1, $page - $half);
$end = min($totalPages, $start + $maxVisible - 1);

if ($end - $start < $maxVisible - 1) {
    $start = max(1, $end - $maxVisible + 1);
}

$separator = strpos($baseUrl, '?') !== false ? '&' : '?';
?>

<nav class="pagination" aria-label="Pagination">
    <a 
        href="<?= $page > 1 ? View::e($baseUrl . $separator . 'page=' . ($page - 1)) : '#' ?>" 
        class="pagination-btn pagination-prev <?= $page <= 1 ? 'disabled' : '' ?>"
        <?= $page <= 1 ? 'aria-disabled="true"' : '' ?>
    >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        <span class="pagination-btn-text">Previous</span>
    </a>
    
    <div class="pagination-pages">
        <?php if ($start > 1): ?>
            <a href="<?= View::e($baseUrl . $separator . 'page=1') ?>" class="pagination-page">1</a>
            <?php if ($start > 2): ?>
                <span class="pagination-ellipsis">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a 
                href="<?= View::e($baseUrl . $separator . 'page=' . $i) ?>" 
                class="pagination-page <?= $i === $page ? 'active' : '' ?>"
                <?= $i === $page ? 'aria-current="page"' : '' ?>
            >
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
                <span class="pagination-ellipsis">...</span>
            <?php endif; ?>
            <a href="<?= View::e($baseUrl . $separator . 'page=' . $totalPages) ?>" class="pagination-page"><?= $totalPages ?></a>
        <?php endif; ?>
    </div>
    
    <a 
        href="<?= $page < $totalPages ? View::e($baseUrl . $separator . 'page=' . ($page + 1)) : '#' ?>" 
        class="pagination-btn pagination-next <?= $page >= $totalPages ? 'disabled' : '' ?>"
        <?= $page >= $totalPages ? 'aria-disabled="true"' : '' ?>
    >
        <span class="pagination-btn-text">Next</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
    </a>
</nav>

<style>
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
    padding: 1rem 0;
}

.pagination-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: var(--text);
    text-decoration: none;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    transition: background 0.15s, border-color 0.15s;
}

.pagination-btn:hover:not(.disabled) {
    background: var(--bg);
    border-color: var(--primary);
}

.pagination-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.pagination-btn-text {
    display: none;
}

@media (min-width: 640px) {
    .pagination-btn-text {
        display: inline;
    }
}

.pagination-pages {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.pagination-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 0.5rem;
    font-size: 0.875rem;
    color: var(--text);
    text-decoration: none;
    border-radius: var(--radius);
    transition: background 0.15s;
}

.pagination-page:hover {
    background: var(--bg);
}

.pagination-page.active {
    background: var(--primary);
    color: white;
}

.pagination-ellipsis {
    padding: 0 0.25rem;
    color: var(--text-muted);
}
</style>
