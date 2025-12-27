/**
 * Bookmark Manager Extension - Background Service Worker
 * Handles background tasks and context menu (optional)
 */

// Listen for installation
chrome.runtime.onInstalled.addListener((details) => {
  if (details.reason === 'install') {
    // Open options page on first install
    chrome.runtime.openOptionsPage();
  }
});

// Optional: Add context menu for right-click saving
chrome.runtime.onInstalled.addListener(() => {
  // Create context menu item
  chrome.contextMenus?.create({
    id: 'save-bookmark',
    title: 'Save to Bookmark Manager',
    contexts: ['page', 'link']
  });
});

// Handle context menu clicks
chrome.contextMenus?.onClicked.addListener(async (info, tab) => {
  if (info.menuItemId === 'save-bookmark') {
    const url = info.linkUrl || info.pageUrl;
    const title = info.linkUrl ? '' : tab?.title || '';

    // Get settings
    const settings = await chrome.storage.sync.get(['apiUrl', 'apiKey']);

    if (!settings.apiUrl || !settings.apiKey) {
      // Open options if not configured
      chrome.runtime.openOptionsPage();
      return;
    }

    try {
      const response = await fetch(`${settings.apiUrl}/api/external.php`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${settings.apiKey}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ url, title })
      });

      const result = await response.json();

      if (result.success) {
        // Show success notification (optional - requires notifications permission)
        console.log('Bookmark saved successfully:', url);
      } else if (response.status === 409) {
        console.log('Bookmark already exists:', url);
      } else {
        console.error('Failed to save bookmark:', result.error);
      }
    } catch (err) {
      console.error('Error saving bookmark:', err);
    }
  }
});

// Listen for messages from popup or content scripts
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  if (message.type === 'SAVE_BOOKMARK') {
    handleSaveBookmark(message.data)
      .then(result => sendResponse(result))
      .catch(err => sendResponse({ success: false, error: err.message }));
    return true; // Keep channel open for async response
  }
});

/**
 * Handle bookmark saving from messages
 */
async function handleSaveBookmark(data) {
  const settings = await chrome.storage.sync.get(['apiUrl', 'apiKey']);

  if (!settings.apiUrl || !settings.apiKey) {
    return { success: false, error: 'Not configured' };
  }

  const response = await fetch(`${settings.apiUrl}/api/external.php`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${settings.apiKey}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
  });

  return response.json();
}
