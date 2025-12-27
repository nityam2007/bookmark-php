<?php
/**
 * Settings Page
 * User profile and account settings
 * 
 * @var array $user - Current user data
 * @var array $apiKeys - User's API keys
 */

use App\Core\View;
use App\Helpers\Csrf;

$user = $user ?? [];
$apiKeys = $apiKeys ?? [];

// Check for newly generated API key
$newApiKey = $_SESSION['new_api_key'] ?? null;
$newApiKeyName = $_SESSION['new_api_key_name'] ?? null;
unset($_SESSION['new_api_key'], $_SESSION['new_api_key_name']);
?>

<div class="settings-page">
    <div class="page-header">
        <h1>Account Settings</h1>
        <p class="text-muted">Manage your account information and security settings</p>
    </div>

    <div class="settings-grid">
        <!-- Profile Information -->
        <section class="settings-card">
            <div class="card-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Profile Information
                </h2>
            </div>
            <form action="/settings/profile" method="POST" class="settings-form">
                <?= Csrf::field() ?>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?= View::e($user['username'] ?? '') ?>"
                        class="form-input"
                        required
                        minlength="3"
                        maxlength="50"
                    >
                    <small class="form-hint">Your unique username for login</small>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= View::e($user['email'] ?? '') ?>"
                        class="form-input"
                        required
                    >
                    <small class="form-hint">Used for account recovery</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </section>

        <!-- Change Password -->
        <section class="settings-card">
            <div class="card-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Change Password
                </h2>
            </div>
            <form action="/settings/password" method="POST" class="settings-form">
                <?= Csrf::field() ?>
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        class="form-input"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        class="form-input"
                        required
                        minlength="<?= PASSWORD_MIN_LENGTH ?>"
                        autocomplete="new-password"
                    >
                    <small class="form-hint">Minimum <?= PASSWORD_MIN_LENGTH ?> characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input"
                        required
                        minlength="<?= PASSWORD_MIN_LENGTH ?>"
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Update Password
                    </button>
                </div>
            </form>
        </section>

        <!-- Account Information -->
        <section class="settings-card">
            <div class="card-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    Account Information
                </h2>
            </div>
            <div class="account-info">
                <div class="info-row">
                    <span class="info-label">Account ID</span>
                    <span class="info-value">#<?= View::e($user['id'] ?? '-') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Role</span>
                    <span class="info-value">
                        <span class="badge badge-<?= ($user['role'] ?? '') === 'admin' ? 'primary' : 'secondary' ?>">
                            <?= View::e(ucfirst($user['role'] ?? 'user')) ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Account Created</span>
                    <span class="info-value"><?= isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : '-' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Login</span>
                    <span class="info-value"><?= isset($user['last_login_at']) ? date('F j, Y \a\t g:i A', strtotime($user['last_login_at'])) : 'Never' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <span class="badge badge-<?= !empty($user['is_active']) ? 'success' : 'danger' ?>">
                            <?= !empty($user['is_active']) ? 'Active' : 'Inactive' ?>
                        </span>
                    </span>
                </div>
            </div>
        </section>

        <!-- API Keys -->
        <section class="settings-card">
            <div class="card-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
                    </svg>
                    API Keys
                </h2>
            </div>
            <div class="api-keys-section">
                <p class="api-description">
                    API keys allow external apps (Chrome extensions, mobile apps) to add bookmarks to your account.
                    <a href="/api/" target="_blank">View API documentation</a>
                </p>

                <?php if ($newApiKey): ?>
                    <div class="api-key-new">
                        <div class="alert alert-success">
                            <strong>New API Key Generated!</strong>
                            <p>Copy this key now. It won't be shown again.</p>
                        </div>
                        <div class="api-key-display">
                            <code id="newApiKey"><?= View::e($newApiKey) ?></code>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="copyApiKey()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                                Copy
                            </button>
                        </div>
                        <p class="api-key-name">Name: <strong><?= View::e($newApiKeyName) ?></strong></p>
                    </div>
                <?php endif; ?>

                <!-- Generate New Key -->
                <form action="/settings/api-key/generate" method="POST" class="api-key-form">
                    <?= Csrf::field() ?>
                    <div class="form-row">
                        <input 
                            type="text" 
                            name="key_name" 
                            class="form-input"
                            placeholder="Key name (e.g., Chrome Extension)"
                            maxlength="100"
                        >
                        <button type="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Generate Key
                        </button>
                    </div>
                </form>

                <!-- Existing Keys -->
                <?php if (!empty($apiKeys)): ?>
                    <div class="api-keys-list">
                        <h4>Your API Keys</h4>
                        <table class="api-keys-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Key</th>
                                    <th>Created</th>
                                    <th>Last Used</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apiKeys as $key): ?>
                                    <tr class="<?= $key['is_active'] ? '' : 'revoked' ?>">
                                        <td><?= View::e($key['name']) ?></td>
                                        <td><code><?= View::e($key['key_prefix']) ?>...****</code></td>
                                        <td><?= date('M j, Y', strtotime($key['created_at'])) ?></td>
                                        <td><?= $key['last_used_at'] ? date('M j, Y', strtotime($key['last_used_at'])) : 'Never' ?></td>
                                        <td>
                                            <span class="badge badge-<?= $key['is_active'] ? 'success' : 'danger' ?>">
                                                <?= $key['is_active'] ? 'Active' : 'Revoked' ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <?php if ($key['is_active']): ?>
                                                <form action="/settings/api-key/revoke" method="POST" style="display:inline;">
                                                    <?= Csrf::field() ?>
                                                    <input type="hidden" name="key_id" value="<?= $key['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('Revoke this API key?')">Revoke</button>
                                                </form>
                                            <?php endif; ?>
                                            <form action="/settings/api-key/delete" method="POST" style="display:inline;">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="key_id" value="<?= $key['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this API key permanently?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-keys">No API keys yet. Generate one to use with external apps.</p>
                <?php endif; ?>

                <!-- API Usage Example -->
                <details class="api-docs">
                    <summary>API Usage Examples</summary>
                    <div class="api-example">
                        <h5>Add a bookmark (cURL)</h5>
                        <pre><code>curl -X POST <?= View::e(APP_URL) ?>/api/external.php \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://example.com", "title": "Example"}'</code></pre>

                        <h5>Add a bookmark (JavaScript/Fetch)</h5>
                        <pre><code>fetch('<?= View::e(APP_URL) ?>/api/external.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_API_KEY',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    url: 'https://example.com',
    title: 'Example Site',
    tags: ['work', 'reference']
  })
});</code></pre>
                    </div>
                </details>
            </div>
        </section>

        <!-- Danger Zone -->
        <section class="settings-card settings-card-danger">
            <div class="card-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    Danger Zone
                </h2>
            </div>
            <div class="danger-content">
                <p>Once you delete your account, there is no going back. All your bookmarks and data will be permanently deleted.</p>
                <button type="button" class="btn btn-danger" onclick="showDeleteModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Delete Account
                </button>
            </div>
        </section>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal-overlay" id="deleteModal" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Delete Account</h3>
            <button type="button" class="modal-close" onclick="hideDeleteModal()">&times;</button>
        </div>
        <form action="/settings/delete-account" method="POST">
            <?= Csrf::field() ?>
            <div class="modal-body">
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone!</p>
                <p>Please enter your password to confirm account deletion:</p>
                <div class="form-group">
                    <input 
                        type="password" 
                        name="password" 
                        class="form-input"
                        placeholder="Your password"
                        required
                    >
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete My Account</button>
            </div>
        </form>
    </div>
</div>

<style>
.settings-page {
    max-width: 900px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    margin-bottom: 0.5rem;
}

.settings-grid {
    display: grid;
    gap: 1.5rem;
}

.settings-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}

.settings-card-danger {
    border-color: var(--danger);
}

.settings-card-danger .card-header {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.card-header {
    padding: 1rem 1.5rem;
    background: var(--bg);
    border-bottom: 1px solid var(--border);
}

.card-header h2 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.settings-form {
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

.form-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 0.9375rem;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-hint {
    display: block;
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: var(--text-muted);
}

.form-actions {
    padding-top: 0.5rem;
}

.account-info {
    padding: 1.5rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: var(--text-muted);
    font-size: 0.875rem;
}

.info-value {
    font-weight: 500;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 9999px;
}

.badge-primary {
    background: var(--primary);
    color: white;
}

.badge-secondary {
    background: var(--secondary);
    color: white;
}

.badge-success {
    background: var(--success);
    color: white;
}

.badge-danger {
    background: var(--danger);
    color: white;
}

.danger-content {
    padding: 1.5rem;
}

.danger-content p {
    margin-bottom: 1rem;
    color: var(--text-muted);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    transition: background 0.15s;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.btn-secondary {
    background: var(--bg);
    color: var(--text);
    border: 1px solid var(--border);
}

.btn-secondary:hover {
    background: var(--border);
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal {
    background: var(--bg-card);
    border-radius: var(--radius);
    width: 100%;
    max-width: 400px;
    margin: 1rem;
    box-shadow: var(--shadow);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
}

.modal-header h3 {
    margin: 0;
    font-size: 1.125rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-muted);
}

.modal-body {
    padding: 1.5rem;
}

.modal-body p {
    margin-bottom: 1rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border);
    background: var(--bg);
}

.text-danger {
    color: var(--danger);
}

.text-muted {
    color: var(--text-muted);
}

@media (max-width: 640px) {
    .settings-form,
    .account-info,
    .danger-content {
        padding: 1rem;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}

/* API Keys Styles */
.api-keys-section {
    padding: 1.5rem;
}

.api-description {
    margin-bottom: 1.5rem;
    color: var(--text-muted);
    font-size: 0.875rem;
}

.api-description a {
    color: var(--primary);
}

.api-key-new {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid var(--success);
    border-radius: var(--radius);
}

.api-key-new .alert {
    margin-bottom: 1rem;
}

.api-key-new .alert strong {
    display: block;
    margin-bottom: 0.25rem;
}

.api-key-display {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    margin-bottom: 0.5rem;
}

.api-key-display code {
    flex: 1;
    padding: 0.75rem;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-family: monospace;
    font-size: 0.8rem;
    word-break: break-all;
}

.api-key-name {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.api-key-form {
    margin-bottom: 1.5rem;
}

.api-key-form .form-row {
    display: flex;
    gap: 0.75rem;
}

.api-key-form .form-input {
    flex: 1;
}

.api-keys-list {
    margin-top: 1.5rem;
}

.api-keys-list h4 {
    margin-bottom: 1rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.api-keys-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.api-keys-table th,
.api-keys-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--border);
}

.api-keys-table th {
    font-weight: 600;
    background: var(--bg);
}

.api-keys-table tr.revoked {
    opacity: 0.6;
}

.api-keys-table code {
    font-family: monospace;
    font-size: 0.8rem;
    background: var(--bg);
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
}

.api-keys-table .actions {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.no-keys {
    color: var(--text-muted);
    font-style: italic;
}

.api-docs {
    margin-top: 1.5rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
}

.api-docs summary {
    padding: 0.75rem 1rem;
    cursor: pointer;
    font-weight: 500;
    background: var(--bg);
}

.api-docs summary:hover {
    background: var(--border);
}

.api-example {
    padding: 1rem;
}

.api-example h5 {
    margin: 1rem 0 0.5rem;
    font-size: 0.875rem;
}

.api-example h5:first-child {
    margin-top: 0;
}

.api-example pre {
    background: #1e293b;
    color: #e2e8f0;
    padding: 1rem;
    border-radius: var(--radius);
    overflow-x: auto;
    font-size: 0.75rem;
}

.api-example code {
    font-family: 'Monaco', 'Consolas', monospace;
}

.alert-success {
    color: var(--success);
}

@media (max-width: 768px) {
    .api-key-form .form-row {
        flex-direction: column;
    }
    
    .api-keys-table {
        display: block;
        overflow-x: auto;
    }
}
</style>

<script>
function showDeleteModal() {
    document.getElementById('deleteModal').style.display = 'flex';
}

function hideDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function copyApiKey() {
    const keyElement = document.getElementById('newApiKey');
    const key = keyElement.textContent;
    
    navigator.clipboard.writeText(key).then(() => {
        const btn = keyElement.nextElementSibling;
        const originalText = btn.innerHTML;
        btn.innerHTML = '✓ Copied!';
        btn.style.background = 'var(--success)';
        btn.style.color = 'white';
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.style.background = '';
            btn.style.color = '';
        }, 2000);
    }).catch(() => {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = key;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('API key copied to clipboard!');
    });
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideDeleteModal();
    }
});

// Close modal on overlay click
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideDeleteModal();
    }
});
</script>
