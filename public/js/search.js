/**
 * Bookmark Manager - Search Module
 * Fast AJAX-powered search with debouncing and caching
 * 
 * @package BookmarkManager
 */

'use strict';

const BookmarkSearch = {
    searchInput: null,
    resultsContainer: null,
    cache: new Map(),
    debounceTimer: null,
    focusedIndex: -1,
    isLoading: false,
    
    config: {
        minChars: 2,
        debounceMs: 300,
        cacheExpiry: 300000, // 5 minutes
        maxResults: 10
    },
    
    /**
     * Initialize search functionality
     */
    init() {
        // Support both ID naming conventions
        this.searchInput = document.getElementById('searchInput') || document.getElementById('global-search');
        this.resultsContainer = document.getElementById('searchResults') || document.getElementById('search-results');
        
        if (!this.searchInput || !this.resultsContainer) return;
        
        // Find inner container or use main container
        this.resultsInner = this.resultsContainer.querySelector('.search-results-inner') || this.resultsContainer;
        
        this.bindEvents();
        console.log('BookmarkSearch initialized');
    },
    
    /**
     * Bind all event listeners
     */
    bindEvents() {
        // Input handling
        this.searchInput.addEventListener('input', (e) => {
            this.handleInput(e.target.value);
        });
        
        // Keyboard navigation
        this.searchInput.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });
        
        // Focus/blur
        this.searchInput.addEventListener('focus', () => {
            if (this.searchInput.value.length >= this.config.minChars) {
                this.showResults();
            }
        });
        
        // Click outside to close
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                this.hideResults();
            }
        });
        
        // Result item clicks
        this.resultsContainer.addEventListener('click', (e) => {
            const item = e.target.closest('.search-result-item');
            if (item) {
                this.selectResult(item);
            }
        });
    },
    
    /**
     * Handle input changes with debouncing
     */
    handleInput(value) {
        clearTimeout(this.debounceTimer);
        
        const query = value.trim();
        
        if (query.length < this.config.minChars) {
            this.hideResults();
            return;
        }
        
        // Check cache first
        const cached = this.getFromCache(query);
        if (cached) {
            this.renderResults(cached);
            return;
        }
        
        // Debounce API call
        this.debounceTimer = setTimeout(() => {
            this.search(query);
        }, this.config.debounceMs);
    },
    
    /**
     * Perform search API call
     */
    async search(query) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            const response = await fetch(`/api/search?q=${encodeURIComponent(query)}&limit=${this.config.maxResults}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('Search failed');
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.addToCache(query, data.data);
                this.renderResults(data.data);
            } else {
                this.showError(data.error || 'Search failed');
            }
        } catch (error) {
            console.error('Search error:', error);
            this.showError('Failed to perform search');
        } finally {
            this.isLoading = false;
        }
    },
    
    /**
     * Render search results
     */
    renderResults(results) {
        this.focusedIndex = -1;
        
        if (!results || results.length === 0) {
            this.resultsInner.innerHTML = `
                <div class="search-no-results">
                    <p>No bookmarks found</p>
                </div>
            `;
            this.showResults();
            return;
        }
        
        const html = results.map((item, index) => `
            <div class="search-result-item" 
                 data-index="${index}" 
                 data-id="${item.id}"
                 data-url="${this.escapeAttr(item.url)}">
                <img class="search-result-favicon" 
                     src="${item.favicon || '/img/default-favicon.svg'}" 
                     alt="" 
                     loading="lazy"
                     onerror="this.onerror=null; this.src='/img/default-favicon.svg'">
                <div class="search-result-content">
                    <div class="search-result-title">${this.highlight(item.title, this.searchInput.value)}</div>
                    <div class="search-result-url">${this.escapeHtml(item.url)}</div>
                </div>
            </div>
        `).join('');
        
        this.resultsInner.innerHTML = html;
        this.showResults();
    },
    
    /**
     * Handle keyboard navigation
     */
    handleKeydown(e) {
        const items = this.resultsContainer.querySelectorAll('.search-result-item');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.focusedIndex = Math.min(this.focusedIndex + 1, items.length - 1);
                this.updateFocus(items);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.focusedIndex = Math.max(this.focusedIndex - 1, -1);
                this.updateFocus(items);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (this.focusedIndex >= 0 && items[this.focusedIndex]) {
                    this.selectResult(items[this.focusedIndex]);
                }
                break;
                
            case 'Escape':
                this.hideResults();
                this.searchInput.blur();
                break;
        }
    },
    
    /**
     * Update focused item styling
     */
    updateFocus(items) {
        items.forEach((item, index) => {
            item.classList.toggle('focused', index === this.focusedIndex);
        });
        
        // Scroll into view
        if (this.focusedIndex >= 0 && items[this.focusedIndex]) {
            items[this.focusedIndex].scrollIntoView({ block: 'nearest' });
        }
    },
    
    /**
     * Select a result item - navigate to bookmark detail page
     */
    selectResult(item) {
        const id = item.dataset.id;
        if (id) {
            window.location.href = `/bookmarks/${id}`;
        }
    },
    
    /**
     * Show/hide results container
     */
    showResults() {
        this.resultsContainer.classList.add('active');
    },
    
    hideResults() {
        this.resultsContainer.classList.remove('active');
        this.focusedIndex = -1;
    },
    
    /**
     * Show loading state
     */
    showLoading() {
        this.resultsInner.innerHTML = `
            <div class="search-loading">
                <span>Searching...</span>
            </div>
        `;
        this.showResults();
    },
    
    /**
     * Show error message
     */
    showError(message) {
        this.resultsInner.innerHTML = `
            <div class="search-error">
                <p>${this.escapeHtml(message)}</p>
            </div>
        `;
        this.showResults();
    },
    
    // ============================================
    // Cache Management
    // ============================================
    getFromCache(query) {
        const key = query.toLowerCase();
        const cached = this.cache.get(key);
        
        if (cached && Date.now() - cached.time < this.config.cacheExpiry) {
            return cached.data;
        }
        
        return null;
    },
    
    addToCache(query, data) {
        const key = query.toLowerCase();
        this.cache.set(key, {
            data: data,
            time: Date.now()
        });
        
        // Limit cache size
        if (this.cache.size > 50) {
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }
    },
    
    // ============================================
    // Utilities
    // ============================================
    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },
    
    escapeAttr(str) {
        if (!str) return '';
        return str.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    },
    
    highlight(text, query) {
        if (!text || !query) return this.escapeHtml(text);
        
        const escaped = this.escapeHtml(text);
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return escaped.replace(regex, '<mark>$1</mark>');
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => BookmarkSearch.init());
