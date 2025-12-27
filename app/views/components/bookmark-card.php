<?php
/**
 * Bookmark Card Component
 * Vertical card with meta image at top
 * 
 * @param array $bookmark - The bookmark data
 * @param bool $showActions - Whether to show action buttons
 */

use App\Core\View;
use App\Models\Category;
use App\Services\ImageCacheService;

$bookmark = $bookmark ?? [];
$showActions = $showActions ?? true;

$url = $bookmark['url'] ?? '#';
$title = $bookmark['title'] ?: ($bookmark['meta_title'] ?? null) ?: parse_url($url, PHP_URL_HOST) ?: 'Untitled';
$description = $bookmark['description'] ?: ($bookmark['meta_description'] ?? '');
$favicon = $bookmark['favicon'] ?? null;
$metaImage = $bookmark['meta_image'] ?? null;
$isFavorite = !empty($bookmark['is_favorite']);
$tags = $bookmark['tags'] ?? [];
$categoryId = $bookmark['category_id'] ?? null;
$categoryName = $bookmark['category_name'] ?? null;
$id = $bookmark['id'] ?? 0;
$siteName = $bookmark['meta_site_name'] ?? parse_url($url, PHP_URL_HOST);
$createdAt = $bookmark['created_at'] ?? null;

// Get category path if category exists
$categoryPath = null;
if ($categoryId) {
    $categoryPath = Category::getPathString((int)$categoryId, ' / ');
}

// Use cached images if available, or use placeholders
$faviconUrl = $favicon ?: '/img/default-favicon.svg';
$imageUrl = $metaImage ?: null;
$hasImage = !empty($metaImage);
?>

<article class="bookmark-card" data-id="<?= $id ?>">
    <!-- Image Section -->
    <a href="/bookmarks/<?= $id ?>" class="card-image-link">
        <div class="card-image <?= !$hasImage ? 'no-image' : '' ?>">
            <?php if ($hasImage): ?>
                <img 
                    src="<?= View::e($imageUrl) ?>" 
                    alt="" 
                    loading="lazy" 
                    onerror="this.style.display='none'; this.parentElement.classList.add('no-image'); this.nextElementSibling.style.display='flex';"
                >
                <div class="card-image-placeholder" style="display:none;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                </div>
            <?php else: ?>
                <div class="card-image-placeholder">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Favorite Badge -->
        <?php if ($isFavorite): ?>
            <span class="card-favorite-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                </svg>
            </span>
        <?php endif; ?>
    </a>
    
    <!-- Content Section -->
    <div class="card-body">
        <!-- Site Info -->
        <div class="card-site">
            <img 
                src="<?= View::e($faviconUrl) ?>" 
                alt="" 
                class="card-favicon"
                loading="lazy"
                onerror="this.onerror=null; this.src='/img/default-favicon.svg';"
            >
            <span class="card-site-name"><?= View::e($siteName) ?></span>
            
            <?php if ($showActions): ?>
                <button 
                    class="card-star <?= $isFavorite ? 'active' : '' ?>" 
                    data-action="favorite"
                    data-id="<?= $id ?>"
                    aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $isFavorite ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                    </svg>
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Title -->
        <h3 class="card-title">
            <a href="/bookmarks/<?= $id ?>"><?= View::e($title) ?></a>
        </h3>
        
        <!-- Description -->
        <?php if ($description): ?>
            <p class="card-description">
                <?= View::e(mb_substr($description, 0, 120)) ?><?= strlen($description) > 120 ? '...' : '' ?>
            </p>
        <?php endif; ?>
        
        <!-- Category Path -->
        <?php if ($categoryPath): ?>
            <div class="card-category-path">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"></path>
                </svg>
                <a href="/bookmarks?category=<?= $categoryId ?>" class="card-category-link">
                    <?= View::e($categoryPath) ?>
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Tags -->
        <?php if (!empty($tags)): ?>
            <div class="card-tags">
                <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                    <a href="/bookmarks?tag=<?= $tag['id'] ?>" class="card-tag">
                        #<?= View::e($tag['name']) ?>
                    </a>
                <?php endforeach; ?>
                <?php if (count($tags) > 3): ?>
                    <span class="card-tag-more">+<?= count($tags) - 3 ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="card-footer">
            <a href="/bookmarks/<?= $id ?>/visit" class="card-visit" target="_blank" rel="noopener">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"></path>
                </svg>
                Visit
            </a>
            
            <?php if ($showActions): ?>
                <div class="card-actions">
                    <a href="/bookmarks/<?= $id ?>/edit" class="card-action" title="Edit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    
                    <button 
                        class="card-action card-action-danger" 
                        data-action="delete"
                        data-id="<?= $id ?>"
                        title="Delete"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                        </svg>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</article>

<style>
.bookmark-card {
    display: flex;
    flex-direction: column;
    background: var(--bg-card, #fff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    height: 100%;
}

.bookmark-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    transform: translateY(-3px);
    border-color: var(--primary, #2563eb);
}

/* Image Section */
.card-image-link {
    position: relative;
    display: block;
    text-decoration: none;
}

.card-image {
    aspect-ratio: 16 / 9;
    background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
    overflow: hidden;
    width: 100%;
}

.card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.bookmark-card:hover .card-image img {
    transform: scale(1.05);
}

.card-image.no-image,
.card-image-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-muted, #94a3b8);
}

.card-favorite-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(245, 158, 11, 0.9);
    color: white;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Body Section */
.card-body {
    display: flex;
    flex-direction: column;
    flex: 1;
    padding: 16px;
    gap: 10px;
}

/* Site Info */
.card-site {
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-favicon {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    flex-shrink: 0;
}

.card-site-name {
    flex: 1;
    font-size: 0.75rem;
    color: var(--text-muted, #64748b);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.card-star {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    padding: 0;
    background: none;
    border: none;
    color: var(--text-muted, #94a3b8);
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.15s;
}

.card-star:hover {
    color: #f59e0b;
    background: rgba(245, 158, 11, 0.1);
}

.card-star.active {
    color: #f59e0b;
}

/* Title */
.card-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.4;
}

.card-title a {
    color: var(--text, #1e293b);
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-title a:hover {
    color: var(--primary, #2563eb);
}

/* Description */
.card-description {
    margin: 0;
    font-size: 0.8125rem;
    line-height: 1.5;
    color: var(--text-muted, #64748b);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Category Path */
.card-category-path {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 10px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 8px;
    margin-top: auto;
}

.card-category-path svg {
    flex-shrink: 0;
    color: var(--primary, #2563eb);
}

.card-category-link {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text, #334155);
    text-decoration: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.card-category-link:hover {
    color: var(--primary, #2563eb);
}

/* Tags */
.card-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.card-category {
    font-size: 0.6875rem;
    font-weight: 500;
    padding: 4px 10px;
    background: var(--primary, #2563eb);
    color: white;
    border-radius: 9999px;
    text-decoration: none;
    transition: background 0.15s;
}

.card-category:hover {
    background: var(--primary-dark, #1d4ed8);
}

.card-tag {
    font-size: 0.6875rem;
    padding: 4px 8px;
    background: var(--bg, #f1f5f9);
    color: var(--text-muted, #64748b);
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.15s;
}

.card-tag:hover {
    background: var(--border, #e2e8f0);
    color: var(--text, #1e293b);
}

.card-tag-more {
    font-size: 0.6875rem;
    padding: 4px 6px;
    color: var(--text-muted, #94a3b8);
}

/* Footer */
.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 12px;
    border-top: 1px solid var(--border, #e2e8f0);
    margin-top: 4px;
}

.card-visit {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--primary, #2563eb);
    text-decoration: none;
    padding: 6px 12px;
    background: rgba(37, 99, 235, 0.08);
    border-radius: 6px;
    transition: all 0.15s;
}

.card-visit:hover {
    background: rgba(37, 99, 235, 0.15);
}

.card-actions {
    display: flex;
    gap: 4px;
}

.card-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    padding: 0;
    background: none;
    border: none;
    color: var(--text-muted, #94a3b8);
    cursor: pointer;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.15s;
}

.card-action:hover {
    background: var(--bg, #f1f5f9);
    color: var(--text, #1e293b);
}

.card-action-danger:hover {
    background: #fef2f2;
    color: #dc2626;
}

/* Grid Layout for cards container */
@media (min-width: 640px) {
    .card-image {
        height: 160px;
    }
}

@media (min-width: 1024px) {
    .card-image {
        height: 180px;
    }
}
</style>
