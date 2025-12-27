<?php
/**
 * Flash Message Component
 * Display session flash messages
 */

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if (!$flash) return;

$type = $flash['type'] ?? 'info';
$message = $flash['message'] ?? '';

$icons = [
    'success' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
    'error'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
    'warning' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
    'info'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
];
?>

<div class="flash-message flash-<?= htmlspecialchars($type) ?>" role="alert">
    <span class="flash-icon"><?= $icons[$type] ?? $icons['info'] ?></span>
    <span class="flash-text"><?= htmlspecialchars($message) ?></span>
    <button class="flash-close" onclick="this.parentElement.remove()" aria-label="Close">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>
</div>

<style>
.flash-message {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    margin-bottom: 1.5rem;
    border-radius: var(--radius);
    animation: flashIn 0.3s ease;
}

@keyframes flashIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.flash-success {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.flash-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.flash-warning {
    background: #fffbeb;
    color: #92400e;
    border: 1px solid #fde68a;
}

.flash-info {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

.flash-icon {
    flex-shrink: 0;
    display: flex;
}

.flash-text {
    flex: 1;
    font-size: 0.875rem;
}

.flash-close {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    padding: 0;
    background: none;
    border: none;
    cursor: pointer;
    opacity: 0.6;
    border-radius: 4px;
}

.flash-close:hover {
    opacity: 1;
    background: rgba(0,0,0,0.05);
}
</style>
