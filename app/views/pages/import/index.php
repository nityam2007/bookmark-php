<?php
/**
 * Import/Export Page
 */

use App\Core\View;
use App\Helpers\Csrf;
?>

<div class="import-export-page">
    <div class="page-grid">
        <!-- Import Section -->
        <div class="section-card">
            <div class="section-header">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <h2>Import Bookmarks</h2>
            </div>
            
            <form action="/import" method="POST" enctype="multipart/form-data" class="import-form">
                <?= Csrf::field() ?>
                
                <div class="upload-zone" id="dropZone">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    <p class="upload-text">Drop file here or click to upload</p>
                    <p class="upload-hint">Supports: JSON, HTML, CSV (max 10MB)</p>
                    <input type="file" name="file" id="importFile" accept=".json,.html,.htm,.csv" required>
                </div>
                
                <div class="format-info">
                    <h4>Supported Formats:</h4>
                    <ul>
                        <li><strong>JSON</strong> - Standard JSON format with bookmarks array</li>
                        <li><strong>HTML</strong> - Browser bookmark export format (Chrome, Firefox, etc.)</li>
                        <li><strong>CSV</strong> - Spreadsheet format with url,title,description,tags columns</li>
                    </ul>
                </div>
                
                <div class="duplicate-note">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span>Duplicates are detected by <strong>exact URL match</strong> (including https://, www., and trailing slash)</span>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Import Bookmarks
                </button>
            </form>
        </div>
        
        <!-- Export Section -->
        <div class="section-card">
            <div class="section-header">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <h2>Export Bookmarks</h2>
            </div>
            
            <p class="section-desc">Download all your bookmarks in your preferred format.</p>
            
            <div class="export-formats">
                <a href="/export?format=json" class="export-option">
                    <div class="export-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <div class="export-details">
                        <strong>JSON</strong>
                        <span>Portable data format</span>
                    </div>
                    <svg class="export-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
                
                <a href="/export?format=html" class="export-option">
                    <div class="export-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <div class="export-details">
                        <strong>HTML</strong>
                        <span>Browser compatible format</span>
                    </div>
                    <svg class="export-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
                
                <a href="/export?format=csv" class="export-option">
                    <div class="export-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <div class="export-details">
                        <strong>CSV</strong>
                        <span>Spreadsheet format</span>
                    </div>
                    <svg class="export-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.import-export-page {
    max-width: 100%;
    margin: 0;
}

.page-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    align-items: start;
}

@media (max-width: 1100px) {
    .page-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .page-grid {
        grid-template-columns: 1fr;
    }
}

.section-card {
    background: white;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    color: var(--primary, #2563eb);
}

.section-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text, #1e293b);
}

.section-desc {
    color: var(--text-muted, #64748b);
    margin-bottom: 1.5rem;
}

/* Upload Zone */
.upload-zone {
    position: relative;
    border: 2px dashed var(--border, #e2e8f0);
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    margin-bottom: 1rem;
}

.upload-zone:hover,
.upload-zone.dragover {
    border-color: var(--primary, #2563eb);
    background: #f8fafc;
}

.upload-zone svg {
    color: var(--text-muted, #64748b);
    margin-bottom: 0.75rem;
}

.upload-zone input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.upload-text {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.upload-hint {
    font-size: 0.875rem;
    color: var(--text-muted, #64748b);
}

/* Format Info */
.format-info {
    background: #f8fafc;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.format-info h4 {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.format-info ul {
    list-style: none;
    font-size: 0.875rem;
    color: var(--text-muted, #64748b);
}

.format-info li {
    margin-bottom: 0.25rem;
}

.format-info strong {
    color: var(--text, #1e293b);
}

/* Duplicate Note */
.duplicate-note {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #fef3c7;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    color: #92400e;
    margin-bottom: 1rem;
}

.duplicate-note svg {
    flex-shrink: 0;
    margin-top: 2px;
}

/* Export Options */
.export-formats {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.export-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 0.5rem;
    text-decoration: none;
    color: var(--text, #1e293b);
    transition: border-color 0.2s, background 0.2s;
}

.export-option:hover {
    border-color: var(--primary, #2563eb);
    background: white;
}

.export-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: white;
    border-radius: 0.375rem;
    color: var(--primary, #2563eb);
}

.export-details {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.export-details strong {
    font-weight: 600;
}

.export-details span {
    font-size: 0.875rem;
    color: var(--text-muted, #64748b);
}

.export-arrow {
    color: var(--text-muted, #64748b);
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-primary {
    background: var(--primary, #2563eb);
    color: white;
    border: none;
}

.btn-primary:hover {
    background: var(--primary-dark, #1d4ed8);
}

.btn-block {
    width: 100%;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('importFile');
    
    if (!dropZone || !fileInput) return;
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'));
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'));
    });
    
    dropZone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length) {
            fileInput.files = files;
            document.querySelector('.upload-text').textContent = files[0].name;
        }
    });
    
    fileInput.addEventListener('change', function() {
        if (this.files.length) {
            document.querySelector('.upload-text').textContent = this.files[0].name;
        }
    });
});
</script>
