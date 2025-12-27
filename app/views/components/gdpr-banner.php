<?php
/**
 * GDPR Consent Banner Component
 * Minimal cookie consent for GDPR compliance
 */

// Check if consent already given
$consentGiven = isset($_COOKIE['gdpr_consent']);
if ($consentGiven) return;
?>

<div class="gdpr-banner" id="gdprBanner">
    <div class="gdpr-content">
        <p class="gdpr-text">
            We use cookies to improve your experience. By continuing to use this site, you agree to our 
            <a href="/privacy" class="gdpr-link">Privacy Policy</a>.
        </p>
        
        <div class="gdpr-actions">
            <button class="gdpr-btn gdpr-btn-accept" id="gdprAccept">Accept</button>
            <button class="gdpr-btn gdpr-btn-decline" id="gdprDecline">Decline</button>
        </div>
    </div>
</div>

<style>
.gdpr-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--bg-card);
    border-top: 1px solid var(--border);
    box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
    z-index: 9999;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

.gdpr-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem 1.5rem;
}

@media (max-width: 640px) {
    .gdpr-content {
        flex-direction: column;
        text-align: center;
    }
}

.gdpr-text {
    font-size: 0.875rem;
    color: var(--text);
    line-height: 1.5;
}

.gdpr-link {
    color: var(--primary);
    text-decoration: underline;
}

.gdpr-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}

.gdpr-btn {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: var(--radius);
    cursor: pointer;
    transition: background 0.15s;
}

.gdpr-btn-accept {
    background: var(--primary);
    color: white;
    border: none;
}

.gdpr-btn-accept:hover {
    background: var(--primary-dark);
}

.gdpr-btn-decline {
    background: transparent;
    color: var(--text-muted);
    border: 1px solid var(--border);
}

.gdpr-btn-decline:hover {
    background: var(--bg);
}
</style>

<script>
(function() {
    const banner = document.getElementById('gdprBanner');
    const acceptBtn = document.getElementById('gdprAccept');
    const declineBtn = document.getElementById('gdprDecline');
    
    function setConsent(value) {
        const expires = new Date();
        expires.setFullYear(expires.getFullYear() + 1);
        document.cookie = `gdpr_consent=${value}; expires=${expires.toUTCString()}; path=/; SameSite=Strict`;
        banner.style.display = 'none';
    }
    
    acceptBtn?.addEventListener('click', () => setConsent('accepted'));
    declineBtn?.addEventListener('click', () => setConsent('declined'));
})();
</script>
