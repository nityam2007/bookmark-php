<?php
/**
 * Edit Category Page
 */

use App\Core\View;
use App\Helpers\Csrf;
?>

<div class="category-form-page">
    <div class="page-header">
        <h1>Edit Category</h1>
    </div>

    <div class="form-card">
        <form action="/categories/<?= $category['id'] ?>" method="POST">
            <?= Csrf::field() ?>
            
            <div class="form-group">
                <label for="name">Category Name <span class="required">*</span></label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       required 
                       maxlength="100"
                       value="<?= View::escape($category['name']) ?>"
                       placeholder="Enter category name">
            </div>
            
            <div class="form-group">
                <label for="parent_id">Parent Category</label>
                <select id="parent_id" name="parent_id">
                    <option value="">None (Top Level)</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category['parent_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= str_repeat('— ', $cat['level']) ?><?= View::escape($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" 
                          name="description" 
                          rows="3"
                          placeholder="Optional description"><?= View::escape($category['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="sort_order">Sort Order</label>
                <input type="number" 
                       id="sort_order" 
                       name="sort_order" 
                       value="<?= $category['sort_order'] ?? 0 ?>"
                       min="0">
                <small>Lower numbers appear first</small>
            </div>
            
            <div class="form-actions">
                <a href="/categories" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Category</button>
            </div>
        </form>
    </div>
    
    <div class="danger-zone">
        <h3>Danger Zone</h3>
        <p>Deleting this category will move all its bookmarks to Uncategorized.</p>
        <form action="/categories/<?= $category['id'] ?>/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete this category?')">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-danger">Delete Category</button>
        </form>
    </div>
</div>

<style>
.category-form-page {
    max-width: 600px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
}

.form-card {
    background: white;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.form-group .required {
    color: #dc2626;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.625rem 0.75rem;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary, #2563eb);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group small {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--text-muted, #64748b);
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
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
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
    background: white;
    color: var(--text, #1e293b);
    border: 1px solid var(--border, #e2e8f0);
}

.btn-secondary:hover {
    background: #f8fafc;
}

.btn-danger {
    background: #dc2626;
    color: white;
    border: none;
}

.btn-danger:hover {
    background: #b91c1c;
}

.danger-zone {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 0.5rem;
}

.danger-zone h3 {
    color: #dc2626;
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.danger-zone p {
    color: #991b1b;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}
</style>
