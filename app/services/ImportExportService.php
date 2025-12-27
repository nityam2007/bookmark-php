<?php
/**
 * Import/Export Service
 * Handles bookmark import/export in multiple formats
 * 
 * @package BookmarkManager\Services
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Tag;
use App\Core\Database;

class ImportExportService
{
    private MetaFetcher $metaFetcher;

    public function __construct()
    {
        $this->metaFetcher = new MetaFetcher();
    }

    /**
     * Import from JSON
     * Supports multiple formats:
     * - Simple: { "bookmarks": [...] } or [...]
     * - Linkwarden/Collection format: { "collections": [{ "name": "...", "parentId": ..., "links": [...] }] }
     */
    public function importJson(string $json): array
    {
        $data = json_decode($json, true);
        
        if (!$data || !is_array($data)) {
            return ['success' => false, 'error' => 'Invalid JSON format'];
        }

        // Check for Linkwarden/collection format with collections containing links
        if (isset($data['collections']) && is_array($data['collections'])) {
            // Extract pinned link URLs to mark as favorites
            $pinnedUrls = [];
            if (isset($data['pinnedLinks']) && is_array($data['pinnedLinks'])) {
                foreach ($data['pinnedLinks'] as $pinned) {
                    if (!empty($pinned['url'])) {
                        $pinnedUrls[] = $pinned['url'];
                    }
                }
            }
            return $this->importLinkwardenFormat($data['collections'], $pinnedUrls);
        }

        return $this->importBookmarks($data['bookmarks'] ?? $data);
    }

    /**
     * Import Linkwarden format with hierarchical collections
     * @param array $collections The collections to import
     * @param array $pinnedUrls URLs that should be marked as favorites
     */
    private function importLinkwardenFormat(array $collections, array $pinnedUrls = []): array
    {
        $result = [
            'success'   => true,
            'imported'  => 0,
            'skipped'   => 0,
            'errors'    => []
        ];

        Database::beginTransaction();

        try {
            // Step 1: Create all categories first with hierarchy
            // Map old collection ID -> new category ID
            $categoryMap = [];
            
            // First pass: create categories without parents
            foreach ($collections as $collection) {
                $oldId = $collection['id'] ?? null;
                $name = $collection['name'] ?? 'Uncategorized';
                
                // Check if category already exists
                $existing = Category::findByName($name);
                if ($existing) {
                    $categoryMap[$oldId] = $existing['id'];
                    // Update color if exists
                    if (!empty($collection['color'])) {
                        Database::update('categories', ['color' => $collection['color']], 'id = ?', [$existing['id']]);
                    }
                } else {
                    $categoryId = Category::createCategory([
                        'name'        => $name,
                        'description' => $collection['description'] ?? null,
                        'color'       => $collection['color'] ?? null,
                        'parent_id'   => null // Set later
                    ]);
                    $categoryMap[$oldId] = $categoryId;
                }
            }
            
            // Second pass: update parent relationships
            foreach ($collections as $collection) {
                $oldId = $collection['id'] ?? null;
                $oldParentId = $collection['parentId'] ?? null;
                
                if ($oldParentId && isset($categoryMap[$oldId]) && isset($categoryMap[$oldParentId])) {
                    $newCategoryId = $categoryMap[$oldId];
                    $newParentId = $categoryMap[$oldParentId];
                    
                    // Update parent and recalculate level
                    $parentLevel = 0;
                    $parent = Category::find($newParentId);
                    if ($parent) {
                        $parentLevel = $parent['level'];
                    }
                    
                    Database::update('categories', [
                        'parent_id' => $newParentId,
                        'level'     => $parentLevel + 1
                    ], 'id = ?', [$newCategoryId]);
                }
            }

            // Step 2: Import bookmarks with correct category mapping
            foreach ($collections as $collection) {
                $oldId = $collection['id'] ?? null;
                $categoryId = $categoryMap[$oldId] ?? null;
                $links = $collection['links'] ?? [];
                
                foreach ($links as $link) {
                    $url = $link['url'] ?? null;
                    
                    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                        $result['errors'][] = "Invalid URL: " . ($url ?? 'empty');
                        $result['skipped']++;
                        continue;
                    }

                    // Skip if same normalized URL exists in the same category
                    // Treats http/https, www/non-www as equivalent
                    if (Bookmark::duplicateExistsInCategory($url, $categoryId)) {
                        $result['skipped']++;
                        continue;
                    }

                    // Truncate title to fit database column
                    $title = $link['name'] ?? $link['title'] ?? null;
                    if ($title !== null && mb_strlen($title) > 255) {
                        $title = mb_substr($title, 0, 252) . '...';
                    }

                    // Check if this is a pinned/favorite link
                    $isFavorite = in_array($url, $pinnedUrls, true) ? 1 : 0;

                    // Get original date from JSON (Linkwarden uses createdAt in ISO format)
                    $createdAt = null;
                    if (!empty($link['createdAt'])) {
                        $createdAt = date('Y-m-d H:i:s', strtotime($link['createdAt']));
                    } elseif (!empty($link['created_at'])) {
                        $createdAt = date('Y-m-d H:i:s', strtotime($link['created_at']));
                    }

                    // Create bookmark
                    $bookmarkData = [
                        'url'         => $url,
                        'title'       => $title,
                        'description' => $link['description'] ?? null,
                        'category_id' => $categoryId,
                        'is_favorite' => $isFavorite
                    ];
                    
                    if ($createdAt) {
                        $bookmarkData['created_at'] = $createdAt;
                    }
                    
                    $bookmarkId = Bookmark::createBookmark($bookmarkData);

                    // Handle tags
                    $tags = $link['tags'] ?? [];
                    if (!empty($tags)) {
                        $tagNames = array_map(fn($t) => is_array($t) ? ($t['name'] ?? '') : $t, $tags);
                        $tagNames = array_filter($tagNames);
                        if (!empty($tagNames)) {
                            $tagRecords = Tag::findOrCreateMultiple($tagNames);
                            $tagIds = array_column($tagRecords, 'id');
                            Bookmark::syncTags($bookmarkId, $tagIds);
                        }
                    }

                    $result['imported']++;
                }
            }

            Database::commit();
        } catch (\Exception $e) {
            Database::rollback();
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Import from HTML (browser bookmark format)
     */
    public function importHtml(string $html): array
    {
        $bookmarks = $this->parseHtmlBookmarks($html);
        return $this->importBookmarks($bookmarks);
    }

    /**
     * Import from CSV
     */
    public function importCsv(string $csv): array
    {
        $lines = str_getcsv($csv, "\n");
        $header = str_getcsv(array_shift($lines));
        
        $bookmarks = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $row = str_getcsv($line);
            $bookmark = array_combine($header, $row);
            
            if (!empty($bookmark['url'])) {
                $bookmarks[] = [
                    'url'         => $bookmark['url'],
                    'title'       => $bookmark['title'] ?? null,
                    'description' => $bookmark['description'] ?? null,
                    'tags'        => isset($bookmark['tags']) 
                        ? explode(',', $bookmark['tags']) 
                        : [],
                    'category'    => $bookmark['category'] ?? null
                ];
            }
        }

        return $this->importBookmarks($bookmarks);
    }

    /**
     * Process bookmark imports
     */
    private function importBookmarks(array $bookmarks): array
    {
        $result = [
            'success'   => true,
            'imported'  => 0,
            'skipped'   => 0,
            'errors'    => []
        ];

        Database::beginTransaction();

        try {
            foreach ($bookmarks as $data) {
                $url = $data['url'] ?? null;
                
                if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                    $result['errors'][] = "Invalid URL: " . ($url ?? 'empty');
                    $result['skipped']++;
                    continue;
                }

                // Handle category first (needed for duplicate check)
                $categoryId = null;
                if (!empty($data['category'])) {
                    $categoryId = $this->findOrCreateCategory($data['category']);
                }

                // Skip if same normalized URL exists in the same category
                // Treats http/https, www/non-www, trailing slashes as equivalent
                if (Bookmark::duplicateExistsInCategory($url, $categoryId)) {
                    $result['skipped']++;
                    continue;
                }

                // Truncate title to fit database column (255 chars max)
                $title = $data['title'] ?? null;
                if ($title !== null && mb_strlen($title) > 255) {
                    $title = mb_substr($title, 0, 252) . '...';
                }

                // Get original date if available
                $createdAt = null;
                if (!empty($data['created_at'])) {
                    $createdAt = date('Y-m-d H:i:s', strtotime($data['created_at']));
                } elseif (!empty($data['add_date'])) {
                    // HTML bookmarks use add_date (Unix timestamp)
                    $createdAt = date('Y-m-d H:i:s', (int)$data['add_date']);
                }

                // Create bookmark
                $bookmarkData = [
                    'url'         => $url,
                    'title'       => $title,
                    'description' => $data['description'] ?? null,
                    'meta_image'  => $data['meta_image'] ?? null,
                    'favicon'     => $data['favicon'] ?? null,
                    'category_id' => $categoryId
                ];
                
                if ($createdAt) {
                    $bookmarkData['created_at'] = $createdAt;
                }
                
                $bookmarkId = Bookmark::createBookmark($bookmarkData);

                // Handle tags
                if (!empty($data['tags']) && is_array($data['tags'])) {
                    $tags = Tag::findOrCreateMultiple($data['tags']);
                    $tagIds = array_column($tags, 'id');
                    Bookmark::syncTags($bookmarkId, $tagIds);
                }

                $result['imported']++;
            }

            Database::commit();
        } catch (\Exception $e) {
            Database::rollback();
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Parse HTML bookmark format with full hierarchy support
     */
    private function parseHtmlBookmarks(string $html): array
    {
        $bookmarks = [];

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        
        // Get all <a> tags (links)
        $links = $xpath->query('//a[@href]');
        
        foreach ($links as $link) {
            $url = $link->getAttribute('href');
            
            if (!preg_match('#^https?://#i', $url)) {
                continue;
            }

            // Find full folder path (e.g., "Parent/Child/Grandchild")
            $folderPath = $this->findFolderPath($link);
            
            // Get add_date attribute (Unix timestamp - standard in browser exports)
            $addDate = $link->getAttribute('add_date') ?: null;
            
            // Get icon if present
            $icon = $link->getAttribute('icon') ?: null;

            $bookmarks[] = [
                'url'         => $url,
                'title'       => trim($link->nodeValue) ?: null,
                'category'    => $folderPath, // Full path like "Work/Projects/Active"
                'add_date'    => $addDate,
                'favicon'     => $icon
            ];
        }

        return $bookmarks;
    }

    /**
     * Find full folder path for a bookmark (handles nested folders)
     * Returns path like "Parent/Child/Grandchild"
     */
    private function findFolderPath(\DOMElement $link): ?string
    {
        $folders = [];
        $node = $link->parentNode;
        
        while ($node) {
            if ($node->nodeName === 'DL' && $node->previousSibling) {
                $prev = $node->previousSibling;
                
                // Skip text nodes and whitespace
                while ($prev && $prev->nodeType === XML_TEXT_NODE) {
                    $prev = $prev->previousSibling;
                }
                
                // Also check if prev is a DT containing H3
                if ($prev && $prev->nodeName === 'DT') {
                    // Look for H3 inside DT
                    foreach ($prev->childNodes as $child) {
                        if ($child->nodeName === 'H3') {
                            $folderName = trim($child->nodeValue);
                            // Skip special toolbar folders but keep the name
                            if (!empty($folderName) && $folderName !== 'Bookmarks') {
                                array_unshift($folders, $folderName);
                            }
                            break;
                        }
                    }
                } elseif ($prev && $prev->nodeName === 'H3') {
                    $folderName = trim($prev->nodeValue);
                    if (!empty($folderName) && $folderName !== 'Bookmarks') {
                        array_unshift($folders, $folderName);
                    }
                }
            }
            $node = $node->parentNode;
        }
        
        return !empty($folders) ? implode('/', $folders) : null;
    }

    /**
     * Find parent folder name for a bookmark (legacy - single level)
     */
    private function findParentFolder(\DOMElement $link): ?string
    {
        $node = $link->parentNode;
        
        while ($node) {
            if ($node->nodeName === 'DL' && $node->previousSibling) {
                $prev = $node->previousSibling;
                
                // Skip text nodes
                while ($prev && $prev->nodeType === XML_TEXT_NODE) {
                    $prev = $prev->previousSibling;
                }
                
                if ($prev && $prev->nodeName === 'H3') {
                    return trim($prev->nodeValue);
                }
            }
            $node = $node->parentNode;
        }

        return null;
    }

    /**
     * Find or create category by name or path
     * Supports hierarchical paths like "Parent/Child/Grandchild"
     */
    private function findOrCreateCategory(string $nameOrPath): int
    {
        // Check if it's a path
        if (str_contains($nameOrPath, '/')) {
            return $this->findOrCreateCategoryPath($nameOrPath);
        }
        
        $slug = \App\Helpers\Sanitizer::slug($nameOrPath);
        $existing = Category::findBy('slug', $slug);
        
        if ($existing) {
            return $existing['id'];
        }

        return Category::createCategory([
            'name'        => $nameOrPath,
            'parent_id'   => null,
            'description' => 'Imported category'
        ]);
    }
    
    /**
     * Find or create hierarchical category path
     * e.g., "Work/Projects/Active" creates Work -> Projects -> Active
     */
    private function findOrCreateCategoryPath(string $path): int
    {
        $parts = array_filter(array_map('trim', explode('/', $path)));
        
        if (empty($parts)) {
            return 0;
        }
        
        $parentId = null;
        $categoryId = null;
        
        foreach ($parts as $index => $name) {
            $slug = \App\Helpers\Sanitizer::slug($name);
            
            // Look for existing category with this name and parent
            if ($parentId === null) {
                $sql = "SELECT * FROM categories WHERE slug = ? AND parent_id IS NULL LIMIT 1";
                $existing = Database::fetch($sql, [$slug]);
            } else {
                $sql = "SELECT * FROM categories WHERE slug = ? AND parent_id = ? LIMIT 1";
                $existing = Database::fetch($sql, [$slug, $parentId]);
            }
            
            if ($existing) {
                $categoryId = $existing['id'];
            } else {
                // Create the category
                $categoryId = Category::createCategory([
                    'name'        => $name,
                    'parent_id'   => $parentId,
                    'description' => 'Imported from bookmarks'
                ]);
            }
            
            $parentId = $categoryId;
        }
        
        return $categoryId;
    }

    /**
     * Export to JSON (streaming for large datasets)
     * Exports full data with all metadata fields and hierarchical categories
     */
    public function exportJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="bookmarks_' . date('Y-m-d') . '.json"');

        // Build hierarchical structure like Linkwarden format
        $output = [
            'version' => '1.0',
            'exported_at' => date('c'),
            'collections' => [],
            'pinnedLinks' => []
        ];
        
        // Get category tree with bookmarks
        $categories = Category::getTree();
        $output['collections'] = $this->buildCollectionTree($categories);
        
        // Add uncategorized as a special collection
        $uncategorized = $this->getUncategorizedBookmarks();
        if (!empty($uncategorized)) {
            $output['collections'][] = [
                'id' => 0,
                'name' => 'Uncategorized',
                'description' => 'Bookmarks without a category',
                'parentId' => null,
                'color' => null,
                'links' => $uncategorized
            ];
        }
        
        // Get pinned/favorite bookmarks
        $favorites = Database::fetchAll("SELECT url FROM bookmarks WHERE is_favorite = 1");
        $output['pinnedLinks'] = array_map(fn($f) => ['url' => $f['url']], $favorites);
        
        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Build hierarchical collection tree for JSON export
     */
    private function buildCollectionTree(array $categories, ?int $parentId = null): array
    {
        $collections = [];
        
        foreach ($categories as $category) {
            $collection = [
                'id' => $category['id'],
                'name' => $category['name'],
                'description' => $category['description'] ?? null,
                'parentId' => $parentId,
                'color' => $category['color'] ?? null,
                'links' => $this->getCategoryBookmarksForExport($category['id'])
            ];
            
            $collections[] = $collection;
            
            // Add children as separate collections with parentId set
            if (!empty($category['children'])) {
                $childCollections = $this->buildCollectionTree($category['children'], $category['id']);
                $collections = array_merge($collections, $childCollections);
            }
        }
        
        return $collections;
    }
    
    /**
     * Get bookmarks for a category with all metadata for export
     */
    private function getCategoryBookmarksForExport(int $categoryId): array
    {
        $sql = "SELECT * FROM bookmarks WHERE category_id = ? ORDER BY created_at DESC";
        $bookmarks = Database::fetchAll($sql, [$categoryId]);
        
        $ids = array_column($bookmarks, 'id');
        $tags = $this->getTagsForBookmarks($ids);
        
        $links = [];
        foreach ($bookmarks as $b) {
            $links[] = [
                'url' => $b['url'],
                'name' => $b['title'],
                'description' => $b['description'],
                'tags' => array_map(fn($t) => ['name' => $t['name']], $tags[$b['id']] ?? []),
                'createdAt' => $b['created_at'],
                'updatedAt' => $b['updated_at'],
                // All metadata fields
                'meta_title' => $b['meta_title'],
                'meta_description' => $b['meta_description'],
                'meta_image' => $b['meta_image'],
                'meta_site_name' => $b['meta_site_name'],
                'meta_type' => $b['meta_type'],
                'meta_author' => $b['meta_author'],
                'meta_keywords' => $b['meta_keywords'],
                'meta_locale' => $b['meta_locale'],
                'favicon' => $b['favicon'],
                'http_status' => $b['http_status'],
                'content_type' => $b['content_type'],
                'is_favorite' => (bool)$b['is_favorite']
            ];
        }
        
        return $links;
    }
    
    /**
     * Get uncategorized bookmarks for export
     */
    private function getUncategorizedBookmarks(): array
    {
        $sql = "SELECT * FROM bookmarks WHERE category_id IS NULL ORDER BY created_at DESC";
        $bookmarks = Database::fetchAll($sql);
        
        $ids = array_column($bookmarks, 'id');
        $tags = $this->getTagsForBookmarks($ids);
        
        $links = [];
        foreach ($bookmarks as $b) {
            $links[] = [
                'url' => $b['url'],
                'name' => $b['title'],
                'description' => $b['description'],
                'tags' => array_map(fn($t) => ['name' => $t['name']], $tags[$b['id']] ?? []),
                'createdAt' => $b['created_at'],
                'updatedAt' => $b['updated_at'],
                'meta_title' => $b['meta_title'],
                'meta_description' => $b['meta_description'],
                'meta_image' => $b['meta_image'],
                'meta_site_name' => $b['meta_site_name'],
                'meta_type' => $b['meta_type'],
                'meta_author' => $b['meta_author'],
                'meta_keywords' => $b['meta_keywords'],
                'meta_locale' => $b['meta_locale'],
                'favicon' => $b['favicon'],
                'http_status' => $b['http_status'],
                'content_type' => $b['content_type'],
                'is_favorite' => (bool)$b['is_favorite']
            ];
        }
        
        return $links;
    }

    /**
     * Export to CSV (streaming)
     */
    public function exportCsv(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bookmarks_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        
        // BOM for Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        // Header
        fputcsv($output, ['url', 'title', 'description', 'category', 'tags', 'is_favorite', 'created_at']);

        $offset = 0;
        $chunkSize = EXPORT_CHUNK_SIZE;

        while (true) {
            $sql = "SELECT b.*, c.name as category_name 
                    FROM bookmarks b
                    LEFT JOIN categories c ON b.category_id = c.id
                    ORDER BY b.id
                    LIMIT {$chunkSize} OFFSET {$offset}";
            
            $bookmarks = Database::fetchAll($sql);
            
            if (empty($bookmarks)) {
                break;
            }

            $ids = array_column($bookmarks, 'id');
            $tags = $this->getTagsForBookmarks($ids);

            foreach ($bookmarks as $bookmark) {
                fputcsv($output, [
                    $bookmark['url'],
                    $bookmark['title'],
                    $bookmark['description'],
                    $bookmark['category_name'],
                    implode(',', array_column($tags[$bookmark['id']] ?? [], 'name')),
                    $bookmark['is_favorite'] ? 'yes' : 'no',
                    $bookmark['created_at']
                ]);
                flush();
            }

            $offset += $chunkSize;
        }

        fclose($output);
    }

    /**
     * Export to HTML (browser bookmark format - Netscape standard)
     * Creates proper hierarchical folder structure matching categories
     */
    public function exportHtml(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="bookmarks_' . date('Y-m-d') . '.html"');

        echo "<!DOCTYPE NETSCAPE-Bookmark-file-1>\n";
        echo "<!-- This is an automatically generated file.\n";
        echo "     It will be read and overwritten.\n";
        echo "     DO NOT EDIT! -->\n";
        echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\">\n";
        echo "<TITLE>Bookmarks</TITLE>\n";
        echo "<H1>Bookmarks</H1>\n";
        echo "<DL><p>\n";

        // Favorites/Bookmarks Bar equivalent
        $favorites = Database::fetchAll("SELECT * FROM bookmarks WHERE is_favorite = 1 ORDER BY title");
        if (!empty($favorites)) {
            echo "    <DT><H3 PERSONAL_TOOLBAR_FOLDER=\"true\">Favorites</H3>\n";
            echo "    <DL><p>\n";
            foreach ($favorites as $bookmark) {
                $this->exportBookmarkHtml($bookmark, 2);
            }
            echo "    </DL><p>\n";
        }

        // Group by category (hierarchical)
        $categories = Category::getTree();
        $this->exportCategoryHtml($categories);

        // Uncategorized bookmarks
        $sql = "SELECT * FROM bookmarks WHERE category_id IS NULL ORDER BY title";
        $uncategorized = Database::fetchAll($sql);
        
        if (!empty($uncategorized)) {
            echo "    <DT><H3>Uncategorized</H3>\n";
            echo "    <DL><p>\n";
            foreach ($uncategorized as $bookmark) {
                $this->exportBookmarkHtml($bookmark, 2);
            }
            echo "    </DL><p>\n";
        }

        echo "</DL><p>\n";
    }

    private function exportCategoryHtml(array $categories, int $indent = 1): void
    {
        $pad = str_repeat('    ', $indent);
        
        foreach ($categories as $category) {
            // Folder timestamp
            $folderDate = strtotime($category['created_at'] ?? 'now');
            
            echo "{$pad}<DT><H3 ADD_DATE=\"{$folderDate}\">" . htmlspecialchars($category['name']) . "</H3>\n";
            
            // Add description as DD if exists
            if (!empty($category['description'])) {
                echo "{$pad}<DD>" . htmlspecialchars($category['description']) . "\n";
            }
            
            echo "{$pad}<DL><p>\n";
            
            // Get bookmarks in this category
            $sql = "SELECT * FROM bookmarks WHERE category_id = ? ORDER BY title";
            $bookmarks = Database::fetchAll($sql, [$category['id']]);
            
            foreach ($bookmarks as $bookmark) {
                $this->exportBookmarkHtml($bookmark, $indent + 1);
            }
            
            // Recurse into children (nested folders)
            if (!empty($category['children'])) {
                $this->exportCategoryHtml($category['children'], $indent + 1);
            }
            
            echo "{$pad}</DL><p>\n";
        }
    }

    private function exportBookmarkHtml(array $bookmark, int $indent = 1): void
    {
        $pad = str_repeat('    ', $indent);
        $url = htmlspecialchars($bookmark['url']);
        $title = htmlspecialchars($bookmark['title'] ?: $bookmark['meta_title'] ?: $bookmark['url']);
        
        // Standard attributes for browser import
        $addDate = strtotime($bookmark['created_at'] ?? 'now');
        $lastModified = strtotime($bookmark['updated_at'] ?? $bookmark['created_at'] ?? 'now');
        
        // Build attributes string
        $attrs = "HREF=\"{$url}\" ADD_DATE=\"{$addDate}\" LAST_MODIFIED=\"{$lastModified}\"";
        
        // Add icon if favicon exists (data URI or path)
        if (!empty($bookmark['favicon'])) {
            $icon = htmlspecialchars($bookmark['favicon']);
            $attrs .= " ICON=\"{$icon}\"";
        }
        
        echo "{$pad}<DT><A {$attrs}>{$title}</A>\n";
        
        // Add description if exists
        $description = $bookmark['description'] ?: $bookmark['meta_description'];
        if (!empty($description)) {
            echo "{$pad}<DD>" . htmlspecialchars($description) . "\n";
        }
    }

    private function getTagsForBookmarks(array $ids): array
    {
        if (empty($ids)) return [];
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT bt.bookmark_id, t.name 
                FROM bookmark_tags bt
                INNER JOIN tags t ON bt.tag_id = t.id
                WHERE bt.bookmark_id IN ({$placeholders})";
        
        $results = Database::fetchAll($sql, $ids);
        
        $map = [];
        foreach ($results as $row) {
            $map[$row['bookmark_id']][] = ['name' => $row['name']];
        }
        
        return $map;
    }
}
