/**
 * Bookmark Manager Extension - Popup Script
 * Handles the popup UI for saving bookmarks
 */

// DOM Elements
const states = {
  notConfigured: document.getElementById('not-configured'),
  loading: document.getElementById('loading'),
  mainForm: document.getElementById('main-form'),
  success: document.getElementById('success'),
  error: document.getElementById('error'),
  duplicate: document.getElementById('duplicate')
};

const form = document.getElementById('bookmark-form');
const urlInput = document.getElementById('url');
const titleInput = document.getElementById('title');
const categorySelect = document.getElementById('category');
const tagsInput = document.getElementById('tags');
const isFavoriteCheckbox = document.getElementById('is_favorite');
const saveBtn = document.getElementById('save-btn');
const errorMessage = document.getElementById('error-message');

// Settings
let settings = {
  apiUrl: '',
  apiKey: ''
};

/**
 * Initialize the popup
 */
async function init() {
  // Load settings
  const stored = await chrome.storage.sync.get(['apiUrl', 'apiKey']);
  settings.apiUrl = stored.apiUrl || '';
  settings.apiKey = stored.apiKey || '';

  // Check if configured
  if (!settings.apiUrl || !settings.apiKey) {
    showState('notConfigured');
    return;
  }

  // Show loading and fetch data
  showState('loading');

  try {
    // Get current tab info
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    urlInput.value = tab.url || '';
    titleInput.value = tab.title || '';

    // Fetch categories with timeout
    await Promise.race([
      fetchCategories(),
      new Promise((_, reject) => setTimeout(() => reject(new Error('Request timeout')), 10000))
    ]);

    showState('mainForm');
  } catch (err) {
    console.error('Init error:', err);
    showError(err.message || 'Failed to load. Please check your settings.');
  }
}

/**
 * Show a specific state, hide all others
 */
function showState(stateName) {
  Object.keys(states).forEach(key => {
    if (key === stateName) {
      states[key].classList.remove('hidden');
    } else {
      states[key].classList.add('hidden');
    }
  });
}

/**
 * Show error state with message
 */
function showError(message) {
  errorMessage.textContent = message;
  showState('error');
}

/**
 * Fetch categories from API
 */
async function fetchCategories() {
  console.log('Fetching categories from:', settings.apiUrl);
  
  const response = await fetch(`${settings.apiUrl}/api/categories.php`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${settings.apiKey}`,
      'Content-Type': 'application/json'
    }
  });

  console.log('Response status:', response.status);

  if (!response.ok) {
    const text = await response.text();
    console.error('Error response:', text.substring(0, 200));
    throw new Error(`Server error (${response.status})`);
  }

  // Check if response is JSON
  const contentType = response.headers.get('content-type');
  if (!contentType || !contentType.includes('application/json')) {
    const text = await response.text();
    console.error('Non-JSON response:', text.substring(0, 200));
    throw new Error('Server returned non-JSON response. Check server configuration.');
  }

  const result = await response.json();
  console.log('Categories loaded:', result.data?.length || 0);

  if (!result.success) {
    throw new Error(result.error || 'Failed to fetch categories');
  }

  // Clear existing options (except default)
  categorySelect.innerHTML = '<option value="">Uncategorized</option>';

  // Add categories with depth indicators
  if (result.data && result.data.length > 0) {
    result.data.forEach(category => {
      const option = document.createElement('option');
      option.value = category.id;
      
      // Add indent based on depth
      const indent = '—'.repeat(category.depth || 0);
      option.textContent = indent + (indent ? ' ' : '') + category.name;
      
      categorySelect.appendChild(option);
    });
  }
}

/**
 * Save bookmark via API
 */
async function saveBookmark(data) {
  const response = await fetch(`${settings.apiUrl}/api/external.php`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${settings.apiKey}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
  });

  // Check if response is JSON
  const contentType = response.headers.get('content-type');
  if (!contentType || !contentType.includes('application/json')) {
    const text = await response.text();
    console.error('Non-JSON response:', text.substring(0, 200));
    throw new Error('Server returned non-JSON response. Check server configuration.');
  }

  const result = await response.json();

  if (response.status === 409) {
    // Duplicate bookmark
    showState('duplicate');
    return;
  }

  if (!response.ok || !result.success) {
    throw new Error(result.error || 'Failed to save bookmark');
  }

  showState('success');
}

/**
 * Handle form submission
 */
form.addEventListener('submit', async (e) => {
  e.preventDefault();

  // Disable button and show loading state
  saveBtn.disabled = true;
  saveBtn.innerHTML = `
    <span class="spinner" style="width:16px;height:16px;border-width:2px;margin:0;"></span>
    Saving...
  `;

  try {
    // Build request data
    const data = {
      url: urlInput.value,
      title: titleInput.value || undefined,
      is_favorite: isFavoriteCheckbox.checked
    };

    // Add category if selected
    if (categorySelect.value) {
      data.category_id = parseInt(categorySelect.value, 10);
    }

    // Add tags if provided
    if (tagsInput.value.trim()) {
      data.tags = tagsInput.value
        .split(',')
        .map(tag => tag.trim())
        .filter(tag => tag.length > 0);
    }

    await saveBookmark(data);
  } catch (err) {
    console.error('Save error:', err);
    showError(err.message || 'Failed to save bookmark');
  } finally {
    // Reset button
    saveBtn.disabled = false;
    saveBtn.innerHTML = `
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
      </svg>
      Save Bookmark
    `;
  }
});

// Event Listeners for buttons
document.getElementById('open-settings').addEventListener('click', () => {
  chrome.runtime.openOptionsPage();
});

document.getElementById('settings-link').addEventListener('click', (e) => {
  e.preventDefault();
  chrome.runtime.openOptionsPage();
});

document.getElementById('close-success').addEventListener('click', () => {
  window.close();
});

document.getElementById('close-duplicate').addEventListener('click', () => {
  window.close();
});

document.getElementById('retry-btn').addEventListener('click', () => {
  init();
});

// Initialize on load
document.addEventListener('DOMContentLoaded', init);
