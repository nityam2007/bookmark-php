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
     * Parse HTML bookmark format
     */
    private function parseHtmlBookmarks(string $html): array
    {
        $bookmarks = [];
        $currentFolder = null;

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

            // Find parent folder (DT > A, parent DL, previous sibling H3)
            $folder = $this->findParentFolder($link);
            
            // Get add_date attribute (Unix timestamp - standard in browser exports)
            $addDate = $link->getAttribute('add_date') ?: null;

            $bookmarks[] = [
                'url'      => $url,
                'title'    => trim($link->nodeValue) ?: null,
                'category' => $folder,
                'add_date' => $addDate
            ];
        }

        return $bookmarks;
    }

    /**
     * Find parent folder name for a bookmark
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
     * Find or create category by name
     */
    private function findOrCreateCategory(string $name): int
    {
        $slug = \App\Helpers\Sanitizer::slug($name);
        $existing = Category::findBy('slug', $slug);
        
        if ($existing) {
            return $existing['id'];
        }

        return Category::createCategory([
            'name'        => $name,
            'parent_id'   => null,
            'description' => 'Imported category'
        ]);
    }

    /**
     * Export to JSON (streaming for large datasets)
     */
    public function exportJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="bookmarks_' . date('Y-m-d') . '.json"');

        echo '{"bookmarks":[';

        $offset = 0;
        $first = true;
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

            // Get tags for chunk
            $ids = array_column($bookmarks, 'id');
            $tags = $this->getTagsForBookmarks($ids);

            foreach ($bookmarks as $bookmark) {
                if (!$first) echo ',';
                $first = false;
                
                echo json_encode([
                    'url'         => $bookmark['url'],
                    'title'       => $bookmark['title'],
                    'description' => $bookmark['description'],
                    'category'    => $bookmark['category_name'],
                    'tags'        => array_column($tags[$bookmark['id']] ?? [], 'name'),
                    'is_favorite' => (bool)$bookmark['is_favorite'],
                    'created_at'  => $bookmark['created_at']
                ], JSON_UNESCAPED_UNICODE);
                
                flush();
            }

            $offset += $chunkSize;
        }

        echo ']}';
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
     * Export to HTML (browser bookmark format)
     */
    public function exportHtml(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="bookmarks_' . date('Y-m-d') . '.html"');

        echo "<!DOCTYPE NETSCAPE-Bookmark-file-1>\n";
        echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\">\n";
        echo "<TITLE>Bookmarks</TITLE>\n";
        echo "<H1>Bookmarks</H1>\n";
        echo "<DL><p>\n";

        // Group by category
        $categories = Category::getTree();
        $this->exportCategoryHtml($categories);

        // Uncategorized bookmarks
        $sql = "SELECT * FROM bookmarks WHERE category_id IS NULL ORDER BY title";
        $uncategorized = Database::fetchAll($sql);
        
        foreach ($uncategorized as $bookmark) {
            $this->exportBookmarkHtml($bookmark);
        }

        echo "</DL><p>\n";
    }

    private function exportCategoryHtml(array $categories, int $indent = 1): void
    {
        $pad = str_repeat('    ', $indent);
        
        foreach ($categories as $category) {
            echo "{$pad}<DT><H3>" . htmlspecialchars($category['name']) . "</H3>\n";
            echo "{$pad}<DL><p>\n";
            
            // Get bookmarks in this category
            $sql = "SELECT * FROM bookmarks WHERE category_id = ? ORDER BY title";
            $bookmarks = Database::fetchAll($sql, [$category['id']]);
            
            foreach ($bookmarks as $bookmark) {
                $this->exportBookmarkHtml($bookmark, $indent + 1);
            }
            
            // Recurse into children
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
        $title = htmlspecialchars($bookmark['title'] ?? $bookmark['url']);
        
        echo "{$pad}<DT><A HREF=\"{$url}\">{$title}</A>\n";
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
