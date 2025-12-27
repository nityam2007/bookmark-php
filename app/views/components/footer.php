<?php
/**
 * Footer Component
 * Minimal footer with GDPR compliance
 */
?>

<footer class="site-footer">
    <div class="footer-content">
        <p class="footer-text">
            &copy; <?= date('Y') ?> <?= \App\Core\View::e(APP_NAME) ?>. 
            <span class="footer-version">v<?= APP_VERSION ?></span>
        </p>
        
        <nav class="footer-links">
            <a href="/privacy" class="footer-link">Privacy Policy</a>
            <a href="/terms" class="footer-link">Terms</a>
        </nav>
    </div>
</footer>

<style>
.site-footer {
    margin-top: auto;
    padding: 1.5rem 0;
    border-top: 1px solid var(--border);
}

.footer-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.footer-text {
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.footer-version {
    opacity: 0.7;
}

.footer-links {
    display: flex;
    gap: 1.5rem;
}

.footer-link {
    font-size: 0.8125rem;
    color: var(--text-muted);
    text-decoration: none;
}

.footer-link:hover {
    color: var(--primary);
}

@media (max-width: 480px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
    }
}
</style>
