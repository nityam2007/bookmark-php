# Bookmark Manager Browser Extension

A browser extension to quickly save bookmarks to your Bookmark Manager with category selection.

## Features

- **Quick Save**: Click the extension icon to save the current page
- **Category Selection**: Choose a category from your existing categories
- **Tags Support**: Add comma-separated tags to your bookmarks
- **Favorite Toggle**: Mark bookmarks as favorites
- **Settings Page**: Configure your server URL and API key

## Installation

### Chrome / Edge / Brave (Chromium-based browsers)

1. Open your browser and go to:
   - **Chrome**: `chrome://extensions/`
   - **Edge**: `edge://extensions/`
   - **Brave**: `brave://extensions/`

2. Enable **Developer mode** (toggle in the top right)

3. Click **Load unpacked**

4. Select the `browser-extension` folder

5. The extension icon should appear in your toolbar

### Firefox

Firefox requires additional steps as it uses Manifest V2. This extension is built for Manifest V3 (Chrome).

## Setup

1. Click the extension icon or go to the extension options
2. Enter your **Bookmark Manager Server URL** (e.g., `https://bookmarks.example.com`)
3. Enter your **API Key** (get it from Settings → API Keys in your Bookmark Manager)
4. Click **Test Connection** to verify
5. Click **Save Settings**

## Usage

1. Navigate to any webpage you want to bookmark
2. Click the extension icon in your toolbar
3. (Optional) Edit the title
4. Select a category (default: Uncategorized)
5. (Optional) Add tags (comma-separated)
6. (Optional) Mark as favorite
7. Click **Save Bookmark**

## API Key

Your API key can be generated in the Bookmark Manager:

1. Log in to your Bookmark Manager
2. Go to **Settings → API Keys**
3. Click **Generate Key**
4. Copy the key (it starts with `bm_`)

## File Structure

```
browser-extension/
├── manifest.json          # Extension manifest (MV3)
├── background.js          # Service worker for background tasks
├── popup/
│   ├── popup.html         # Popup UI
│   ├── popup.css          # Popup styles
│   └── popup.js           # Popup logic
├── options/
│   ├── options.html       # Settings page UI
│   ├── options.css        # Settings page styles
│   └── options.js         # Settings page logic
└── icons/
    ├── icon16.png         # 16x16 icon
    ├── icon32.png         # 32x32 icon
    ├── icon48.png         # 48x48 icon
    └── icon128.png        # 128x128 icon
```

## Creating Icons

Replace the placeholder icons in the `icons/` folder with your own:

- `icon16.png` - 16x16 pixels (toolbar icon, small)
- `icon32.png` - 32x32 pixels (toolbar icon, retina)
- `icon48.png` - 48x48 pixels (extension management page)
- `icon128.png` - 128x128 pixels (Chrome Web Store, installation)

You can use the bookmark SVG from the popup as a base:

```svg
<svg viewBox="0 0 24 24" fill="#3b82f6" stroke="#3b82f6" stroke-width="2">
  <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
</svg>
```

## Permissions

- `activeTab`: Access to the current tab's URL and title
- `storage`: Store settings (API URL and key)
- `<all_urls>`: Make API requests to your Bookmark Manager server

## Troubleshooting

### "Please configure your API settings first"
Go to the extension settings and enter your server URL and API key.

### "Failed to fetch categories"
- Check that your server URL is correct (no trailing slash)
- Verify your API key is valid
- Ensure your server has proper CORS headers

### "Connection failed"
- Verify the server URL is accessible
- Check if your Bookmark Manager is running
- Try accessing the API directly in your browser

## Development

To modify the extension:

1. Make changes to the source files
2. Go to `chrome://extensions/`
3. Click the refresh icon on the extension card
4. Test your changes

## License

Part of the Bookmark Manager project.
