/**
 * Bookmark Manager Extension - Options/Settings Script
 * Handles saving and testing API configuration
 */

// DOM Elements
const form = document.getElementById('settings-form');
const apiUrlInput = document.getElementById('api-url');
const apiKeyInput = document.getElementById('api-key');
const toggleKeyBtn = document.getElementById('toggle-key');
const eyeIcon = document.getElementById('eye-icon');
const eyeOffIcon = document.getElementById('eye-off-icon');
const testBtn = document.getElementById('test-btn');
const saveBtn = document.getElementById('save-btn');
const connectionStatus = document.getElementById('connection-status');
const statusSuccess = document.getElementById('status-success');
const statusError = document.getElementById('status-error');
const statusErrorMessage = document.getElementById('status-error-message');

/**
 * Initialize the options page
 */
async function init() {
  // Load saved settings
  const settings = await chrome.storage.sync.get(['apiUrl', 'apiKey']);
  
  if (settings.apiUrl) {
    apiUrlInput.value = settings.apiUrl;
  }
  
  if (settings.apiKey) {
    apiKeyInput.value = settings.apiKey;
  }
}

/**
 * Toggle password visibility
 */
toggleKeyBtn.addEventListener('click', () => {
  const isPassword = apiKeyInput.type === 'password';
  apiKeyInput.type = isPassword ? 'text' : 'password';
  
  if (isPassword) {
    eyeIcon.classList.add('hidden');
    eyeOffIcon.classList.remove('hidden');
  } else {
    eyeIcon.classList.remove('hidden');
    eyeOffIcon.classList.add('hidden');
  }
});

/**
 * Normalize URL (remove trailing slash)
 */
function normalizeUrl(url) {
  return url.replace(/\/+$/, '');
}

/**
 * Test API connection
 */
async function testConnection() {
  const apiUrl = normalizeUrl(apiUrlInput.value.trim());
  const apiKey = apiKeyInput.value.trim();

  if (!apiUrl || !apiKey) {
    showStatus('error', 'Please enter both URL and API Key');
    return false;
  }

  // Show loading state
  testBtn.disabled = true;
  testBtn.innerHTML = `
    <span class="spinner-sm"></span>
    Testing...
  `;
  
  hideStatus();

  try {
    // Try to fetch categories as a connection test
    const response = await fetch(`${apiUrl}/api/categories.php`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Content-Type': 'application/json'
      }
    });

    if (response.status === 401) {
      showStatus('error', 'Invalid API key. Please check and try again.');
      return false;
    }

    if (!response.ok) {
      showStatus('error', `Server returned error: ${response.status}`);
      return false;
    }

    const result = await response.json();

    if (!result.success) {
      showStatus('error', result.error || 'Unknown error from server');
      return false;
    }

    showStatus('success');
    return true;

  } catch (err) {
    console.error('Connection test error:', err);
    
    if (err.message.includes('Failed to fetch')) {
      showStatus('error', 'Could not connect to server. Check the URL and try again.');
    } else {
      showStatus('error', err.message || 'Connection failed');
    }
    return false;

  } finally {
    // Reset button
    testBtn.disabled = false;
    testBtn.innerHTML = `
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
      Test Connection
    `;
  }
}

/**
 * Show connection status
 */
function showStatus(type, message = '') {
  connectionStatus.classList.remove('hidden');
  
  if (type === 'success') {
    statusSuccess.classList.remove('hidden');
    statusError.classList.add('hidden');
  } else {
    statusSuccess.classList.add('hidden');
    statusError.classList.remove('hidden');
    statusErrorMessage.textContent = message;
  }
}

/**
 * Hide connection status
 */
function hideStatus() {
  connectionStatus.classList.add('hidden');
  statusSuccess.classList.add('hidden');
  statusError.classList.add('hidden');
}

/**
 * Save settings
 */
async function saveSettings() {
  const apiUrl = normalizeUrl(apiUrlInput.value.trim());
  const apiKey = apiKeyInput.value.trim();

  if (!apiUrl) {
    showStatus('error', 'Please enter the server URL');
    apiUrlInput.focus();
    return;
  }

  if (!apiKey) {
    showStatus('error', 'Please enter your API key');
    apiKeyInput.focus();
    return;
  }

  // Validate API key format
  if (!apiKey.startsWith('bm_')) {
    showStatus('error', 'API key should start with "bm_"');
    apiKeyInput.focus();
    return;
  }

  // Show loading state
  saveBtn.disabled = true;
  saveBtn.innerHTML = `
    <span class="spinner-sm"></span>
    Saving...
  `;

  try {
    // Test connection first
    const isValid = await testConnection();

    if (!isValid) {
      // Status already shown by testConnection
      return;
    }

    // Save to Chrome storage
    await chrome.storage.sync.set({
      apiUrl: apiUrl,
      apiKey: apiKey
    });

    // Show success
    showStatus('success');

    // Brief delay then show saved message
    setTimeout(() => {
      showStatus('success');
    }, 100);

  } catch (err) {
    console.error('Save error:', err);
    showStatus('error', 'Failed to save settings');

  } finally {
    // Reset button
    saveBtn.disabled = false;
    saveBtn.innerHTML = `
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
        <polyline points="17 21 17 13 7 13 7 21"></polyline>
        <polyline points="7 3 7 8 15 8"></polyline>
      </svg>
      Save Settings
    `;
  }
}

// Event Listeners
testBtn.addEventListener('click', testConnection);

form.addEventListener('submit', (e) => {
  e.preventDefault();
  saveSettings();
});

// Initialize on load
document.addEventListener('DOMContentLoaded', init);
