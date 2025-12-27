/**
 * Bookmark Manager - Main Application JavaScript
 * Core utilities and initialization
 * 
 * @package BookmarkManager
 */

'use strict';

// ============================================
// Application Namespace
// ============================================
const BookmarkApp = {
    config: {
        searchDelay: 300,
        cacheExpiry: 300000, // 5 minutes
        apiBase: '/api'
    },
    cache: new Map(),
    
    /**
     * Initialize the application
     */
    init() {
        this.initSidebar();
        this.initAlerts();
        this.initDropdowns();
        this.initModals();
        this.initGDPR();
        this.initKeyboardShortcuts();
        
        console.log('BookmarkApp initialized');
    },
    
    // ============================================
    // Sidebar Management
    // ============================================
    initSidebar() {
        const toggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (!toggle || !sidebar) return;
        
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        
        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
            });
        }
        
        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
    },
    
    // ============================================
    // Alert/Flash Messages
    // ============================================
    initAlerts() {
        document.querySelectorAll('.alert-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.alert').remove();
            });
        });
        
        // Auto-dismiss after 5 seconds
        document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    },
    
    showAlert(type, message, container = '.content-wrapper') {
        const alertHTML = `
            <div class="alert alert-${type}" data-auto-dismiss>
                <div class="alert-content">${this.escapeHtml(message)}</div>
                <button class="alert-close" type="button">&times;</button>
            </div>
        `;
        
        const containerEl = document.querySelector(container);
        if (containerEl) {
            containerEl.insertAdjacentHTML('afterbegin', alertHTML);
            this.initAlerts();
        }
    },
    
    // ============================================
    // Dropdowns
    // ============================================
    initDropdowns() {
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            const trigger = dropdown.querySelector('.dropdown-trigger');
            if (!trigger) return;
            
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                
                // Close other dropdowns
                document.querySelectorAll('.dropdown.active').forEach(d => {
                    if (d !== dropdown) d.classList.remove('active');
                });
                
                dropdown.classList.toggle('active');
            });
        });
        
        // Close on outside click
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown.active').forEach(d => {
                d.classList.remove('active');
            });
        });
    },
    
    // ============================================
    // Modals
    // ============================================
    initModals() {
        // Open modal
        document.querySelectorAll('[data-modal-open]').forEach(trigger => {
            trigger.addEventListener('click', () => {
                const modalId = trigger.getAttribute('data-modal-open');
                this.openModal(modalId);
            });
        });
        
        // Close modal
        document.querySelectorAll('[data-modal-close]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.modal-overlay');
                if (modal) this.closeModal(modal.id);
            });
        });
        
        // Close on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    this.closeModal(overlay.id);
                }
            });
        });
    },
    
    openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },
    
    closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },
    
    // ============================================
    // GDPR Banner
    // ============================================
    initGDPR() {
        const banner = document.querySelector('.gdpr-banner');
        if (!banner) return;
        
        const accepted = localStorage.getItem('gdpr_accepted');
        if (!accepted) {
            setTimeout(() => banner.classList.add('visible'), 1000);
        }
        
        banner.querySelector('.gdpr-accept')?.addEventListener('click', () => {
            localStorage.setItem('gdpr_accepted', Date.now().toString());
            banner.classList.remove('visible');
        });
        
        banner.querySelector('.gdpr-decline')?.addEventListener('click', () => {
            banner.classList.remove('visible');
        });
    },
    
    // ============================================
    // Keyboard Shortcuts
    // ============================================
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Only if not in input
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
                return;
            }
            
            // Ctrl/Cmd + K = Focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                // Support both ID naming conventions
                const searchInput = document.getElementById('searchInput') || document.getElementById('global-search');
                searchInput?.focus();
            }
            
            // N = New bookmark
            if (e.key === 'n' && !e.ctrlKey && !e.metaKey) {
                const newBtn = document.querySelector('[data-action="new-bookmark"]');
                if (newBtn) newBtn.click();
            }
            
            // ? = Show shortcuts help
            if (e.key === '?' && e.shiftKey) {
                this.openModal('shortcuts-modal');
            }
        });
    },
    
    // ============================================
    // Utilities
    // ============================================
    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    async fetchJSON(url, options = {}) {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        return response.json();
    },
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('default', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }).format(date);
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => BookmarkApp.init());
