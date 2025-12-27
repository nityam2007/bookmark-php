/**
 * Bookmark Manager - Bookmarks Module
 * Bookmark CRUD operations and interactions
 * 
 * @package BookmarkManager
 */

'use strict';

const BookmarkActions = {
    csrfToken: null,
    
    /**
     * Initialize bookmark actions
     */
    init() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        this.bindEvents();
        this.initUrlFetcher();
        this.initTagInput();
        
        console.log('BookmarkActions initialized');
    },
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Delete bookmark
        document.querySelectorAll('[data-action="delete"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.dataset.id;
                this.confirmDelete(id);
            });
        });
        
        // Toggle favorite
        document.querySelectorAll('[data-action="toggle-favorite"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.dataset.id;
                this.toggleFavorite(id, btn);
            });
        });
        
        // Copy URL
        document.querySelectorAll('[data-action="copy-url"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const url = btn.dataset.url;
                this.copyToClipboard(url, btn);
            });
        });
        
        // Open in new tab
        document.querySelectorAll('[data-action="open-bookmark"]').forEach(link => {
            link.addEventListener('click', (e) => {
                // Track click
                const id = link.dataset.id;
                if (id) this.trackClick(id);
            });
        });
    },
    
    /**
     * URL Meta Fetcher - Auto-fill title and description
     */
    initUrlFetcher() {
        const urlInput = document.getElementById('bookmark-url');
        const titleInput = document.getElementById('bookmark-title');
        const descInput = document.getElementById('bookmark-description');
        const fetchBtn = document.getElementById('fetch-meta');
        
        if (!urlInput) return;
        
        // Fetch on button click
        if (fetchBtn) {
            fetchBtn.addEventListener('click', () => {
                this.fetchMeta(urlInput.value, titleInput, descInput);
            });
        }
        
        // Auto-fetch on paste
        urlInput.addEventListener('paste', () => {
            setTimeout(() => {
                if (!titleInput.value) {
                    this.fetchMeta(urlInput.value, titleInput, descInput);
                }
            }, 100);
        });
    },
    
    /**
     * Fetch URL metadata
     */
    async fetchMeta(url, titleInput, descInput) {
        if (!url) return;
        
        try {
            const response = await fetch(`/api/meta?url=${encodeURIComponent(url)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (titleInput && !titleInput.value && data.data.title) {
                    titleInput.value = data.data.title;
                }
                if (descInput && !descInput.value && data.data.description) {
                    descInput.value = data.data.description;
                }
            }
        } catch (error) {
            console.error('Failed to fetch meta:', error);
        }
    },
    
    /**
     * Tag Input Component
     */
    initTagInput() {
        const tagInput = document.getElementById('tag-input');
        const tagsContainer = document.getElementById('tags-container');
        const hiddenInput = document.getElementById('bookmark-tags');
        
        if (!tagInput || !tagsContainer) return;
        
        let tags = [];
        
        // Initialize existing tags
        if (hiddenInput && hiddenInput.value) {
            tags = hiddenInput.value.split(',').filter(t => t.trim());
            this.renderTags(tags, tagsContainer, hiddenInput);
        }
        
        // Add tag on Enter or comma
        tagInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const tag = tagInput.value.trim().replace(/,/g, '');
                
                if (tag && !tags.includes(tag)) {
                    tags.push(tag);
                    this.renderTags(tags, tagsContainer, hiddenInput);
                }
                
                tagInput.value = '';
            }
            
            // Remove last tag on backspace
            if (e.key === 'Backspace' && !tagInput.value && tags.length > 0) {
                tags.pop();
                this.renderTags(tags, tagsContainer, hiddenInput);
            }
        });
        
        // Remove tag on click
        tagsContainer.addEventListener('click', (e) => {
            if (e.target.closest('.tag-remove')) {
                const tagEl = e.target.closest('.tag');
                const tagText = tagEl.dataset.tag;
                tags = tags.filter(t => t !== tagText);
                this.renderTags(tags, tagsContainer, hiddenInput);
            }
        });
    },
    
    /**
     * Render tag elements
     */
    renderTags(tags, container, hiddenInput) {
        container.innerHTML = tags.map(tag => `
            <span class="tag tag-primary" data-tag="${this.escapeAttr(tag)}">
                ${this.escapeHtml(tag)}
                <span class="tag-remove" role="button" aria-label="Remove tag">&times;</span>
            </span>
        `).join('');
        
        if (hiddenInput) {
            hiddenInput.value = tags.join(',');
        }
    },
    
    /**
     * Delete bookmark with confirmation
     */
    confirmDelete(id) {
        if (confirm('Are you sure you want to delete this bookmark?')) {
            this.deleteBookmark(id);
        }
    },
    
    /**
     * Delete bookmark API call
     */
    async deleteBookmark(id) {
        try {
            const formData = new FormData();
            formData.append('csrf_token', this.csrfToken);
            
            const response = await fetch(`/bookmarks/${id}/delete`, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Remove from DOM
                const card = document.querySelector(`[data-bookmark-id="${id}"]`);
                if (card) {
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 300);
                }
                BookmarkApp.showAlert('success', 'Bookmark deleted successfully');
            } else {
                BookmarkApp.showAlert('error', data.error || 'Failed to delete bookmark');
            }
        } catch (error) {
            console.error('Delete error:', error);
            BookmarkApp.showAlert('error', 'Failed to delete bookmark');
        }
    },
    
    /**
     * Toggle favorite status
     */
    async toggleFavorite(id, button) {
        try {
            const formData = new FormData();
            formData.append('csrf_token', this.csrfToken);
            
            const response = await fetch(`/api/bookmarks/${id}/favorite`, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                button.classList.toggle('active');
                const icon = button.querySelector('svg');
                if (icon) {
                    icon.setAttribute('fill', data.is_favorite ? 'currentColor' : 'none');
                }
            }
        } catch (error) {
            console.error('Favorite toggle error:', error);
        }
    },
    
    /**
     * Track bookmark click
     */
    async trackClick(id) {
        try {
            const formData = new FormData();
            formData.append('csrf_token', this.csrfToken);
            
            await fetch(`/api/bookmarks/${id}/click`, {
                method: 'POST',
                body: formData
            });
        } catch (error) {
            // Silently fail - not critical
        }
    },
    
    /**
     * Copy URL to clipboard
     */
    async copyToClipboard(text, button) {
        try {
            await navigator.clipboard.writeText(text);
            
            // Show feedback
            const originalText = button.innerHTML;
            button.innerHTML = '✓ Copied!';
            setTimeout(() => {
                button.innerHTML = originalText;
            }, 2000);
        } catch (error) {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
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
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => BookmarkActions.init());
