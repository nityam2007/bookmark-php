/**
 * Bookmark Manager - Category Tree Module
 * Interactive category tree with expand/collapse and drag support
 * 
 * @package BookmarkManager
 */

'use strict';

const CategoryTree = {
    container: null,
    
    /**
     * Initialize category tree
     */
    init() {
        this.container = document.querySelector('.category-tree');
        if (!this.container) return;
        
        this.bindEvents();
        this.restoreState();
        
        console.log('CategoryTree initialized');
    },
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Toggle expand/collapse
        this.container.addEventListener('click', (e) => {
            const toggle = e.target.closest('.category-toggle');
            if (toggle) {
                e.preventDefault();
                this.toggleCategory(toggle);
            }
        });
        
        // Keyboard navigation
        this.container.addEventListener('keydown', (e) => {
            const link = e.target.closest('.category-link');
            if (!link) return;
            
            switch (e.key) {
                case 'ArrowRight':
                    e.preventDefault();
                    this.expandCategory(link);
                    break;
                    
                case 'ArrowLeft':
                    e.preventDefault();
                    this.collapseCategory(link);
                    break;
                    
                case 'ArrowDown':
                    e.preventDefault();
                    this.focusNext(link);
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    this.focusPrev(link);
                    break;
            }
        });
    },
    
    /**
     * Toggle category expand/collapse
     */
    toggleCategory(toggle) {
        const item = toggle.closest('.category-item');
        const children = item.querySelector('.category-children');
        
        if (!children) return;
        
        const isOpen = toggle.classList.contains('open');
        
        if (isOpen) {
            toggle.classList.remove('open');
            children.style.display = 'none';
        } else {
            toggle.classList.add('open');
            children.style.display = 'block';
        }
        
        this.saveState();
    },
    
    /**
     * Expand a category
     */
    expandCategory(link) {
        const item = link.closest('.category-item');
        const toggle = item.querySelector('.category-toggle');
        const children = item.querySelector('.category-children');
        
        if (toggle && children && !toggle.classList.contains('open')) {
            toggle.classList.add('open');
            children.style.display = 'block';
            this.saveState();
        }
    },
    
    /**
     * Collapse a category
     */
    collapseCategory(link) {
        const item = link.closest('.category-item');
        const toggle = item.querySelector('.category-toggle');
        const children = item.querySelector('.category-children');
        
        if (toggle && children && toggle.classList.contains('open')) {
            toggle.classList.remove('open');
            children.style.display = 'none';
            this.saveState();
        }
    },
    
    /**
     * Focus next visible category
     */
    focusNext(current) {
        const links = Array.from(this.container.querySelectorAll('.category-link'))
            .filter(link => this.isVisible(link));
        
        const index = links.indexOf(current);
        if (index < links.length - 1) {
            links[index + 1].focus();
        }
    },
    
    /**
     * Focus previous visible category
     */
    focusPrev(current) {
        const links = Array.from(this.container.querySelectorAll('.category-link'))
            .filter(link => this.isVisible(link));
        
        const index = links.indexOf(current);
        if (index > 0) {
            links[index - 1].focus();
        }
    },
    
    /**
     * Check if element is visible
     */
    isVisible(el) {
        let parent = el.parentElement;
        while (parent) {
            if (parent.classList.contains('category-children') && 
                parent.style.display === 'none') {
                return false;
            }
            parent = parent.parentElement;
        }
        return true;
    },
    
    /**
     * Save expanded state to localStorage
     */
    saveState() {
        const expanded = [];
        this.container.querySelectorAll('.category-toggle.open').forEach(toggle => {
            const item = toggle.closest('.category-item');
            const id = item.dataset.id;
            if (id) expanded.push(id);
        });
        
        localStorage.setItem('category_expanded', JSON.stringify(expanded));
    },
    
    /**
     * Restore expanded state from localStorage
     */
    restoreState() {
        try {
            const expanded = JSON.parse(localStorage.getItem('category_expanded') || '[]');
            
            expanded.forEach(id => {
                const item = this.container.querySelector(`[data-id="${id}"]`);
                if (item) {
                    const toggle = item.querySelector('.category-toggle');
                    const children = item.querySelector('.category-children');
                    
                    if (toggle && children) {
                        toggle.classList.add('open');
                        children.style.display = 'block';
                    }
                }
            });
        } catch (error) {
            console.error('Failed to restore category state:', error);
        }
    },
    
    /**
     * Expand all categories
     */
    expandAll() {
        this.container.querySelectorAll('.category-toggle').forEach(toggle => {
            const item = toggle.closest('.category-item');
            const children = item.querySelector('.category-children');
            
            if (children) {
                toggle.classList.add('open');
                children.style.display = 'block';
            }
        });
        
        this.saveState();
    },
    
    /**
     * Collapse all categories
     */
    collapseAll() {
        this.container.querySelectorAll('.category-toggle').forEach(toggle => {
            const item = toggle.closest('.category-item');
            const children = item.querySelector('.category-children');
            
            if (children) {
                toggle.classList.remove('open');
                children.style.display = 'none';
            }
        });
        
        this.saveState();
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => CategoryTree.init());
