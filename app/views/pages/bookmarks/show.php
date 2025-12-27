<?php
/**
 * Bookmark Detail Page
 */

use App\Core\View;
use App\Helpers\Csrf;

$bookmark = $bookmark ?? [];
$tags = $bookmark['tags'] ?? [];
$category = $bookmark['category'] ?? null;

// Check if we have meta data
$hasMeta = !empty($bookmark['meta_title']) || !empty($bookmark['meta_description']);
?>

<div class="bookmark-detail">
    <div class="detail-header">
        <a href="/bookmarks" class="back-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Bookmarks
        </a>
        
        <div class="detail-actions">
            <a href="/bookmarks/<?= $bookmark['id'] ?>/visit" class="btn btn-primary" target="_blank">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
                Visit
            </a>
            <a href="/bookmarks/<?= $bookmark['id'] ?>/edit" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit
            </a>
            <form action="/bookmarks/<?= $bookmark['id'] ?>/delete" method="POST" style="display:inline" onsubmit="return confirm('Delete this bookmark?')">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-danger">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                    </svg>
                    Delete
                </button>
            </form>
        </div>
    </div>
    
    <div class="detail-card">
        <div class="detail-main">
            <?php if (!empty($bookmark['favicon'])): ?>
                <img src="<?= View::e($bookmark['favicon']) ?>" alt="" class="detail-favicon" onerror="this.style.display='none'">
            <?php endif; ?>
            
            <h1 class="detail-title"><?= View::e($bookmark['title'] ?? 'Untitled') ?></h1>
            
            <a href="<?= View::e($bookmark['url']) ?>" class="detail-url" target="_blank">
                <?= View::e($bookmark['url']) ?>
            </a>
        </div>
        
        <?php if (!empty($bookmark['description'])): ?>
            <p class="detail-description"><?= View::e($bookmark['description']) ?></p>
        <?php endif; ?>
        
        <?php if (!empty($bookmark['meta_image'])): ?>
            <img src="<?= View::e($bookmark['meta_image']) ?>" alt="" class="detail-image" loading="lazy" onerror="this.style.display='none'">
        <?php endif; ?>
        
        <div class="detail-meta">
            <?php if ($category): ?>
                <span class="meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"></path>
                    </svg>
                    <?= View::e($category['name']) ?>
                </span>
            <?php endif; ?>
            
            <span class="meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <?= date('M j, Y', strtotime($bookmark['created_at'])) ?>
            </span>
            
            <?php if (!empty($bookmark['visit_count'])): ?>
                <span class="meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <?= $bookmark['visit_count'] ?> visits
                </span>
            <?php endif; ?>
            
            <?php if (!empty($bookmark['is_favorite'])): ?>
                <span class="meta-item meta-favorite">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                    </svg>
                    Favorite
                </span>
            <?php endif; ?>
            
            <?php if (!empty($bookmark['is_archived'])): ?>
                <span class="meta-item meta-archived">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 8v13H3V8M1 3h22v5H1zM10 12h4"></path>
                    </svg>
                    Archived
                </span>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($tags)): ?>
            <div class="detail-tags">
                <?php foreach ($tags as $tag): ?>
                    <a href="/bookmarks?tag=<?= $tag['id'] ?>" class="tag"><?= View::e($tag['name']) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Meta Information Section -->
    <?php if ($hasMeta || !empty($bookmark['meta_fetched_at'])): ?>
    <div class="detail-card meta-card">
        <h2 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            Page Metadata
            <?php if (!empty($bookmark['meta_fetched_at'])): ?>
                <span class="fetch-time">Last fetched: <?= date('M j, Y g:i A', strtotime($bookmark['meta_fetched_at'])) ?></span>
            <?php endif; ?>
        </h2>
        
        <?php if (!empty($bookmark['meta_fetch_error'])): ?>
            <div class="meta-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                Fetch error: <?= View::e($bookmark['meta_fetch_error']) ?>
            </div>
        <?php endif; ?>
        
        <div class="meta-grid">
            <?php if (!empty($bookmark['meta_title'])): ?>
            <div class="meta-row">
                <span class="meta-label">Meta Title</span>
                <span class="meta-value"><?= View::e($bookmark['meta_title']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($bookmark['meta_description'])): ?>
            <div class="meta-row">
                <span class="meta-label">Meta Description</span>
                <span class="meta-value"><?= View::e($bookmark['meta_description']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($bookmark['meta_site_name'])): ?>
            <div class="meta-row">
                <span class="meta-label">Site Name</span>
                <span class="meta-value"><?= View::e($bookmark['meta_site_name']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($bookmark['meta_type'])): ?>
            <div class="meta-row">
                <span class="meta-label">Type</span>
                <span class="meta-value"><code><?= View::e($bookmark['meta_type']) ?></code></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($bookmark['meta_author'])): ?>
            <div class="meta-row">
                <span class="meta-label">Author</span>
                <span class="meta-value"><?= View::e($bookmark['meta_author']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($bookmark['meta_keywords'])): ?>
            <div class="meta-row">
                <span class="meta-label">Keywords</span>
                <span class="meta-value"><?= View::e($bookmark['meta_keywords']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($bookmark['meta_twitter_card'])): ?>
            <div class="meta-row">
                <span class="meta-label">Twitter Card</span>
                <span class="meta-value"><code><?= View::e($bookmark['meta_twitter_card']) ?></code></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($bookmark['meta_twitter_site'])): ?>
            <div class="meta-row">
                <span class="meta-label">Twitter Site</span>
                <span class="meta-value"><?= View::e($bookmark['meta_twitter_site']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($bookmark['http_status'])): ?>
            <div class="meta-row">
                <span class="meta-label">HTTP Status</span>
                <span class="meta-value">
                    <span class="status-badge <?= $bookmark['http_status'] >= 400 ? 'status-error' : 'status-ok' ?>">
                        <?= $bookmark['http_status'] ?>
                    </span>
                </span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($bookmark['content_type'])): ?>
            <div class="meta-row">
                <span class="meta-label">Content Type</span>
                <span class="meta-value"><code><?= View::e($bookmark['content_type']) ?></code></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

<style>
.bookmark-detail {
    max-width: 800px;
    margin: 0 auto;
}

.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-muted, #64748b);
    text-decoration: none;
    font-size: 0.875rem;
}

.back-link:hover {
    color: var(--primary, #2563eb);
}

.detail-actions {
    display: flex;
    gap: 0.5rem;
}

.detail-card {
    background: white;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 0.5rem;
    padding: 2rem;
}

.detail-main {
    margin-bottom: 1.5rem;
}

.detail-favicon {
    width: 48px;
    height: 48px;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
}

.detail-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.detail-url {
    display: block;
    color: var(--primary, #2563eb);
    font-size: 0.875rem;
    word-break: break-all;
}

.detail-description {
    color: var(--text-muted, #64748b);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.detail-image {
    width: 100%;
    max-height: 300px;
    object-fit: cover;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
}

.detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border, #e2e8f0);
    margin-bottom: 1rem;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: var(--text-muted, #64748b);
}

.meta-favorite {
    color: #f59e0b;
}

.detail-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.tag {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    background: #f1f5f9;
    color: #475569;
    border-radius: 9999px;
    text-decoration: none;
}

.tag:hover {
    background: var(--primary, #2563eb);
    color: white;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: 0.375rem;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: var(--primary, #2563eb);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark, #1d4ed8);
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn-secondary:hover {
    background: #e2e8f0;
}

.btn-danger {
    background: #fef2f2;
    color: #dc2626;
}

.btn-danger:hover {
    background: #fee2e2;
}

/* Meta Card Styles */
.meta-card {
    margin-top: 1.5rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border, #e2e8f0);
}

.section-title svg {
    color: var(--primary, #2563eb);
}

.fetch-time {
    margin-left: auto;
    font-size: 0.75rem;
    font-weight: 400;
    color: var(--text-muted, #94a3b8);
}

.meta-error {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 0.375rem;
    color: #dc2626;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.meta-grid {
    display: grid;
    gap: 0.75rem;
}

.meta-row {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 1rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.meta-row:last-child {
    border-bottom: none;
}

.meta-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--text-muted, #64748b);
}

.meta-value {
    font-size: 0.875rem;
    color: var(--text, #334155);
    word-break: break-word;
}

.meta-value code {
    display: inline-block;
    padding: 0.125rem 0.375rem;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 0.75rem;
    background: #f1f5f9;
    border-radius: 0.25rem;
    color: #475569;
}

.status-badge {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 9999px;
}

.status-ok {
    background: #dcfce7;
    color: #16a34a;
}

.status-error {
    background: #fee2e2;
    color: #dc2626;
}

.meta-archived {
    color: #6b7280;
}

@media (max-width: 640px) {
    .meta-row {
        grid-template-columns: 1fr;
        gap: 0.25rem;
    }
    
    .section-title {
        flex-wrap: wrap;
    }
    
    .fetch-time {
        width: 100%;
        margin-left: 0;
        margin-top: 0.5rem;
    }
}
</style>
