 <?php
/**
 * Search Bar Component
 * Instant AJAX search with debouncing
 */
?>

<div class="search-container">
    <form class="search-form" id="searchForm" action="/api/search" method="GET">
        <div class="search-input-wrapper">
            <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            
            <input 
                type="search" 
                name="q" 
                id="searchInput"
                class="search-input" 
                placeholder="Search bookmarks..." 
                autocomplete="off"
                aria-label="Search bookmarks"
            >
            
            <kbd class="search-kbd">Ctrl+K</kbd>
            
            <button type="button" class="search-clear" id="searchClear" hidden>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </form>
    
    <div class="search-results" id="searchResults">
        <div class="search-results-inner">
            <!-- Results populated by JS -->
        </div>
    </div>
</div>

<style>
.search-container {
    position: relative;
    width: 100%;
}

.search-form {
    width: 100%;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    color: var(--text-muted);
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 0.625rem 2.5rem 0.625rem 2.5rem;
    font-size: 0.9375rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--bg);
    color: var(--text);
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.search-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-input::placeholder {
    color: var(--text-muted);
}

.search-kbd {
    position: absolute;
    right: 0.75rem;
    padding: 0.125rem 0.375rem;
    font-size: 0.6875rem;
    font-family: inherit;
    color: var(--text-muted);
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 0.25rem;
}

@media (max-width: 640px) {
    .search-kbd { display: none; }
}

.search-clear {
    position: absolute;
    right: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    padding: 0;
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    border-radius: 50%;
}

.search-clear:hover {
    background: var(--border);
    color: var(--text);
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    margin-top: 0.5rem;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    max-height: 400px;
    overflow-y: auto;
    z-index: 100;
    display: none;
}

.search-results.active {
    display: block;
}

.search-results-inner {
    padding: 0.5rem;
}

.search-result-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    text-decoration: none;
    color: var(--text);
    border-radius: var(--radius);
    transition: background 0.15s;
}

.search-result-item:hover,
.search-result-item.active {
    background: var(--bg);
}

.search-result-favicon {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    object-fit: contain;
    background: var(--bg);
}

.search-result-content {
    flex: 1;
    min-width: 0;
}

.search-result-title {
    font-weight: 500;
    font-size: 0.875rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.search-result-url {
    font-size: 0.75rem;
    color: var(--text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.search-no-results {
    padding: 2rem;
    text-align: center;
    color: var(--text-muted);
}

.search-error {
    padding: 2rem;
    text-align: center;
    color: var(--danger);
}

.search-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.search-loading::after {
    content: '';
    width: 20px;
    height: 20px;
    border: 2px solid var(--border);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
