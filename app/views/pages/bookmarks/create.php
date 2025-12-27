<?php
/**
 * Create Bookmark Page
 */

use App\Core\View;
use App\Helpers\Csrf;

$categories = $categories ?? [];
$tags = $tags ?? [];
?>

<div class="form-page">
    <div class="form-card">
        <form action="/bookmarks" method="POST" class="bookmark-form" id="bookmarkForm">
            <?= Csrf::field() ?>
            
            <div class="form-group">
                <label for="url" class="form-label">URL *</label>
                <input 
                    type="url" 
                    name="url" 
                    id="url" 
                    class="form-input" 
                    placeholder="https://example.com"
                    required
                    autofocus
                >
                <button type="button" class="btn-fetch" id="fetchMeta">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 11-9-9"></path>
                        <polyline points="21 3 21 9 15 9"></polyline>
                    </svg>
                    Fetch Info
                </button>
            </div>
            
            <div class="form-group">
                <label for="title" class="form-label">Title</label>
                <input 
                    type="text" 
                    name="title" 
                    id="title" 
                    class="form-input" 
                    placeholder="Page title (auto-fetched if empty)"
                    maxlength="255"
                >
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea 
                    name="description" 
                    id="description" 
                    class="form-textarea" 
                    placeholder="Brief description..."
                    rows="3"
                    maxlength="1000"
                ></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id" class="form-label">Category</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">Uncategorized</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>">
                                <?= View::e($category['prefix'] . $category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tags" class="form-label">Tags</label>
                    <input 
                        type="text" 
                        name="tags" 
                        id="tags" 
                        class="form-input" 
                        placeholder="Comma separated tags"
                    >
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="is_favorite" value="1">
                    <span>Add to favorites</span>
                </label>
            </div>
            
            <div class="form-actions">
                <a href="/bookmarks" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Bookmark</button>
            </div>
        </form>
    </div>
    
    <!-- Popular Tags -->
    <?php if (!empty($tags)): ?>
        <div class="popular-tags">
            <h4>Popular Tags</h4>
            <div class="tag-cloud">
                <?php foreach ($tags as $tag): ?>
                    <button type="button" class="tag-btn" data-tag="<?= View::e($tag['name']) ?>">
                        <?= View::e($tag['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.form-page {
    max-width: 700px;
    margin: 0 auto;
}

.form-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.25rem;
    position: relative;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text);
}

.form-input,
.form-textarea,
.form-select {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.9375rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--bg);
    color: var(--text);
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
}

.form-checkbox input {
    width: 18px;
    height: 18px;
    accent-color: var(--primary);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
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

.btn-secondary {
    background: transparent;
    color: var(--text);
    border: 1px solid var(--border);
}

.btn-secondary:hover {
    background: var(--bg);
}

.btn-fetch {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.625rem;
    font-size: 0.75rem;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    cursor: pointer;
    color: var(--text-muted);
}

.btn-fetch:hover {
    color: var(--primary);
    border-color: var(--primary);
}

.popular-tags {
    margin-top: 1.5rem;
    padding: 1rem;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
}

.popular-tags h4 {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text-muted);
}

.tag-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}

.tag-btn {
    padding: 0.25rem 0.625rem;
    font-size: 0.75rem;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 9999px;
    cursor: pointer;
    color: var(--text);
}

.tag-btn:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fetchBtn = document.getElementById('fetchMeta');
    const urlInput = document.getElementById('url');
    const titleInput = document.getElementById('title');
    const descInput = document.getElementById('description');
    const tagsInput = document.getElementById('tags');
    
    // Fetch metadata
    fetchBtn?.addEventListener('click', async function() {
        const url = urlInput.value.trim();
        if (!url) return;
        
        fetchBtn.disabled = true;
        fetchBtn.innerHTML = '<span class="loading"></span> Fetching...';
        
        try {
            const res = await fetch('/api/meta?url=' + encodeURIComponent(url));
            const data = await res.json();
            
            if (data.success) {
                if (data.title && !titleInput.value) titleInput.value = data.title;
                if (data.description && !descInput.value) descInput.value = data.description;
            }
        } catch (e) {
            console.error('Fetch error:', e);
        } finally {
            fetchBtn.disabled = false;
            fetchBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-9-9"></path><polyline points="21 3 21 9 15 9"></polyline></svg> Fetch Info';
        }
    });
    
    // Tag cloud click
    document.querySelectorAll('.tag-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tag = this.dataset.tag;
            const current = tagsInput.value.split(',').map(t => t.trim()).filter(Boolean);
            
            if (!current.includes(tag)) {
                current.push(tag);
                tagsInput.value = current.join(', ');
            }
        });
    });
});
</script>
