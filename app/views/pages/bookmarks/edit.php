<?php
/**
 * Edit Bookmark Page
 */

use App\Core\View;
use App\Helpers\Csrf;

$bookmark = $bookmark ?? [];
$categories = $categories ?? [];
$tags = $tags ?? [];
$bookmarkTags = $bookmark['tags'] ?? [];
?>

<div class="form-page">
    <div class="form-card">
        <form action="/bookmarks/<?= $bookmark['id'] ?>" method="POST" class="bookmark-form">
            <?= Csrf::field() ?>
            
            <div class="form-group">
                <label for="url" class="form-label">URL *</label>
                <input 
                    type="url" 
                    name="url" 
                    id="url" 
                    class="form-input" 
                    value="<?= View::e($bookmark['url'] ?? '') ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="title" class="form-label">Title</label>
                <input 
                    type="text" 
                    name="title" 
                    id="title" 
                    class="form-input" 
                    value="<?= View::e($bookmark['title'] ?? '') ?>"
                    maxlength="255"
                >
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea 
                    name="description" 
                    id="description" 
                    class="form-textarea" 
                    rows="3"
                    maxlength="1000"
                ><?= View::e($bookmark['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id" class="form-label">Category</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">Uncategorized</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= ($bookmark['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
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
                        value="<?= View::e(implode(', ', array_column($bookmarkTags, 'name'))) ?>"
                        placeholder="Comma separated tags"
                    >
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="is_favorite" value="1" <?= !empty($bookmark['is_favorite']) ? 'checked' : '' ?>>
                    <span>Favorite</span>
                </label>
                <label class="form-checkbox">
                    <input type="checkbox" name="is_archived" value="1" <?= !empty($bookmark['is_archived']) ? 'checked' : '' ?>>
                    <span>Archived</span>
                </label>
            </div>
            
            <div class="form-actions">
                <a href="/bookmarks" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Bookmark</button>
            </div>
        </form>
    </div>
</div>

<style>
.form-page {
    max-width: 700px;
    margin: 0 auto;
}

.form-card {
    background: var(--bg-card, #fff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 0.5rem;
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.9375rem;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 0.375rem;
    outline: none;
    transition: border-color 0.15s;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    border-color: var(--primary, #2563eb);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    margin-right: 1rem;
}

.form-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary, #2563eb);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border, #e2e8f0);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background 0.15s;
}

.btn-primary {
    background: var(--primary, #2563eb);
    color: white;
    border: none;
}

.btn-primary:hover {
    background: var(--primary-dark, #1d4ed8);
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #e2e8f0;
}
</style>
