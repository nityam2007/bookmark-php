<?php
/**
 * Bookmark Model
 * Handles all bookmark database operations
 * 
 * @package BookmarkManager\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Bookmark extends BaseModel
{
    protected static string $table = 'bookmarks';
    protected static string $primaryKey = 'id';
    
    protected static array $fillable = [
        'url',
        'url_hash',
        'title',
        'description',
        // Meta fields (fetched from webpage - separate from user title/description)
        'meta_title',
        'meta_description',
        'meta_site_name',
        'meta_type',
        'meta_author',
        'meta_keywords',
        'meta_locale',
        'meta_twitter_card',
        'meta_twitter_site',
        'meta_image',
        'favicon',
        'http_status',
        'content_type',
        'meta_fetch_error',
        'meta_fetch_count',
        'meta_fetched_at',
        // Relationships & flags
        'category_id',
        'is_favorite',
        'is_archived',
        // Allow setting created_at during import
        'created_at'
    ];

    /**
     * Find bookmark by URL
     */
    public static function findByUrl(string $url): ?array
    {
        $hash = self::hashUrl($url);
        return self::findBy('url_hash', $hash);
    }

    /**
     * Check if URL exists
     */
    public static function urlExists(string $url, ?int $exceptId = null): bool
    {
        $hash = self::hashUrl($url);
        return self::exists('url_hash', $hash, $exceptId);
    }

    /**
     * Create bookmark with URL hash
     */
    public static function createBookmark(array $data): int
    {
        $data['url_hash'] = self::hashUrl($data['url']);
        return self::create($data);
    }

    /**
     * Get bookmarks with tags and category
     */
    public static function getWithRelations(int $id): ?array
    {
        $bookmark = self::find($id);
        
        if (!$bookmark) {
            return null;
        }

        // Get category
        if ($bookmark['category_id']) {
            $bookmark['category'] = Category::find($bookmark['category_id']);
        }

        // Get tags
        $bookmark['tags'] = Tag::getForBookmark($id);

        return $bookmark;
    }

    /**
     * Get paginated bookmarks with relations
     */
    public static function paginateWithRelations(
        int $page = 1,
        int $perPage = ITEMS_PER_PAGE,
        array $filters = []
    ): array {
        $where = [];
        $params = [];
        $joins = '';

        // Build WHERE clause
        if (!empty($filters['category_id'])) {
            $where[] = 'b.category_id = ?';
            $params[] = $filters['category_id'];
        }

        if (isset($filters['is_favorite'])) {
            $where[] = 'b.is_favorite = ?';
            $params[] = $filters['is_favorite'];
        }

        if (isset($filters['is_archived'])) {
            $where[] = 'b.is_archived = ?';
            $params[] = $filters['is_archived'];
        }

        if (!empty($filters['tag_id'])) {
            $joins = 'INNER JOIN bookmark_tags bt ON b.id = bt.bookmark_id';
            $where[] = 'bt.tag_id = ?';
            $params[] = $filters['tag_id'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Build ORDER BY clause based on sort parameter
        $sort = $filters['sort'] ?? 'newest';
        $orderBy = match($sort) {
            'oldest'       => 'b.created_at ASC',
            'title'        => 'b.title ASC',
            'title_desc'   => 'b.title DESC',
            'visited'      => 'b.visit_count DESC, b.created_at DESC',
            'recent_visit' => 'COALESCE(b.last_visited_at, "1970-01-01") DESC, b.created_at DESC',
            default        => 'b.created_at DESC' // newest
        };

        // Count total
        $countSql = "SELECT COUNT(DISTINCT b.id) FROM bookmarks b {$joins} {$whereClause}";
        $total = (int) Database::fetchColumn($countSql, $params);

        // Get bookmarks
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT b.*, c.name as category_name 
                FROM bookmarks b 
                LEFT JOIN categories c ON b.category_id = c.id
                {$joins}
                {$whereClause}
                GROUP BY b.id
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";
        
        // Add limit and offset as prepared statement parameters
        $params[] = $perPage;
        $params[] = $offset;

        $bookmarks = Database::fetchAll($sql, $params);

        // Get tags for each bookmark
        $bookmarkIds = array_column($bookmarks, 'id');
        $tagsMap = self::getTagsForBookmarks($bookmarkIds);

        foreach ($bookmarks as &$bookmark) {
            $bookmark['tags'] = $tagsMap[$bookmark['id']] ?? [];
        }

        return [
            'items'       => $bookmarks,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage)
        ];
    }

    /**
     * Get tags for multiple bookmarks efficiently
     */
    private static function getTagsForBookmarks(array $bookmarkIds): array
    {
        if (empty($bookmarkIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($bookmarkIds), '?'));
        
        $sql = "SELECT bt.bookmark_id, t.id, t.name, t.slug 
                FROM bookmark_tags bt
                INNER JOIN tags t ON bt.tag_id = t.id
                WHERE bt.bookmark_id IN ({$placeholders})";

        $results = Database::fetchAll($sql, $bookmarkIds);

        $tagsMap = [];
        foreach ($results as $row) {
            $tagsMap[$row['bookmark_id']][] = [
                'id'   => $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug']
            ];
        }

        return $tagsMap;
    }

    /**
     * Sync tags for a bookmark
     */
    public static function syncTags(int $bookmarkId, array $tagIds): void
    {
        Database::beginTransaction();
        
        try {
            // Remove existing tags
            Database::delete('bookmark_tags', 'bookmark_id = ?', [$bookmarkId]);
            
            // Add new tags
            foreach ($tagIds as $tagId) {
                Database::insert('bookmark_tags', [
                    'bookmark_id' => $bookmarkId,
                    'tag_id'      => $tagId
                ]);
            }
            
            Database::commit();
        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Increment visit count
     */
    public static function recordVisit(int $id): void
    {
        $sql = "UPDATE bookmarks SET visit_count = visit_count + 1, 
                last_visited_at = NOW() WHERE id = ?";
        Database::execute($sql, [$id]);
    }

    /**
     * Toggle favorite status
     */
    public static function toggleFavorite(int $id): bool
    {
        $sql = "UPDATE bookmarks SET is_favorite = NOT is_favorite WHERE id = ?";
        Database::execute($sql, [$id]);
        
        return (bool) Database::fetchColumn(
            "SELECT is_favorite FROM bookmarks WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get statistics
     */
    public static function getStats(): array
    {
        return [
            'total'     => self::count(),
            'favorites' => self::count(['is_favorite' => 1]),
            'archived'  => self::count(['is_archived' => 1]),
            'categories' => (int) Database::fetchColumn("SELECT COUNT(*) FROM categories"),
            'tags'       => (int) Database::fetchColumn("SELECT COUNT(*) FROM tags"),
            'needs_meta' => (int) Database::fetchColumn(
                "SELECT COUNT(*) FROM bookmarks WHERE meta_fetched_at IS NULL OR meta_fetch_error IS NOT NULL"
            )
        ];
    }

    /**
     * Hash URL for unique constraint
     * Uses EXACT URL - no normalization for strict duplicate detection
     */
    public static function hashUrl(string $url): string
    {
        // Use exact URL as-is for strict duplicate detection
        // Different variations like http vs https, www vs non-www, 
        // trailing slash vs no slash are treated as DIFFERENT URLs
        return hash('sha256', trim($url));
    }

    /**
     * Check if EXACT URL exists (strict matching)
     */
    public static function exactUrlExists(string $url, ?int $exceptId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM bookmarks WHERE url = ?";
        $params = [$url];
        
        if ($exceptId !== null) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }
        
        return (int) Database::fetchColumn($sql, $params) > 0;
    }

    /**
     * Check if a duplicate exists with EXACT same URL AND same category
     * For smarter import duplicate detection
     * - www.example.com and example.com are DIFFERENT (unique)
     * - http and https are DIFFERENT (unique)
     * - Only skip if BOTH URL and category are exactly the same
     */
    public static function duplicateExistsInCategory(string $url, ?int $categoryId = null): bool
    {
        $url = trim($url);
        
        if ($categoryId === null) {
            $sql = "SELECT COUNT(*) FROM bookmarks WHERE url = ? AND category_id IS NULL";
            $params = [$url];
        } else {
            $sql = "SELECT COUNT(*) FROM bookmarks WHERE url = ? AND category_id = ?";
            $params = [$url, $categoryId];
        }
        
        return (int) Database::fetchColumn($sql, $params) > 0;
    }

    /**
     * Get bookmarks that need meta refresh
     */
    public static function getNeedingMetaRefresh(int $limit = 50, int $ageThresholdDays = 7): array
    {
        $sql = "SELECT id, url, title, meta_fetch_count, meta_fetch_error 
                FROM bookmarks 
                WHERE meta_fetched_at IS NULL 
                   OR (meta_fetched_at < DATE_SUB(NOW(), INTERVAL ? DAY) AND meta_fetch_error IS NULL)
                   OR (meta_fetch_error IS NOT NULL AND meta_fetch_count < 3)
                ORDER BY 
                    CASE WHEN meta_fetched_at IS NULL THEN 0 ELSE 1 END,
                    meta_fetched_at ASC
                LIMIT ?";
        
        return Database::fetchAll($sql, [$ageThresholdDays, $limit]);
    }

    /**
     * Update meta data for a bookmark
     */
    public static function updateMeta(int $id, array $metaData): bool
    {
        $allowed = [
            'meta_title', 'meta_description', 'meta_site_name', 'meta_type',
            'meta_author', 'meta_keywords', 'meta_locale', 'meta_twitter_card',
            'meta_twitter_site', 'meta_image', 'favicon', 'http_status',
            'content_type', 'meta_fetch_error', 'meta_fetched_at'
        ];
        
        $filtered = array_intersect_key($metaData, array_flip($allowed));
        
        if (empty($filtered)) {
            return false;
        }
        
        // Increment fetch count
        Database::execute(
            "UPDATE bookmarks SET meta_fetch_count = meta_fetch_count + 1 WHERE id = ?",
            [$id]
        );
        
        return self::update($id, $filtered) > 0;
    }

    /**
     * Save meta images for a bookmark
     */
    public static function saveMetaImages(int $bookmarkId, array $images): void
    {
        // Clear existing images
        Database::delete('bookmark_meta_images', 'bookmark_id = ?', [$bookmarkId]);
        
        foreach ($images as $index => $image) {
            if (empty($image['url'])) continue;
            
            Database::insert('bookmark_meta_images', [
                'bookmark_id' => $bookmarkId,
                'image_url'   => $image['url'],
                'image_type'  => $image['type'] ?? 'og_image',
                'width'       => $image['width'] ?? null,
                'height'      => $image['height'] ?? null,
                'alt_text'    => $image['alt'] ?? null,
                'is_primary'  => $index === 0 ? 1 : 0
            ]);
        }
    }

    /**
     * Get meta images for a bookmark
     */
    public static function getMetaImages(int $bookmarkId): array
    {
        return Database::fetchAll(
            "SELECT * FROM bookmark_meta_images WHERE bookmark_id = ? ORDER BY is_primary DESC, id ASC",
            [$bookmarkId]
        );
    }

    /**
     * Get primary image for a bookmark
     */
    public static function getPrimaryImage(int $bookmarkId): ?string
    {
        $result = Database::fetchColumn(
            "SELECT image_url FROM bookmark_meta_images WHERE bookmark_id = ? AND is_primary = 1 LIMIT 1",
            [$bookmarkId]
        );
        return $result ?: null;
    }

    /**
     * Log meta fetch attempt
     */
    public static function logMetaFetch(int $bookmarkId, string $url, array $result): void
    {
        Database::insert('meta_fetch_log', [
            'bookmark_id'   => $bookmarkId,
            'url'           => mb_substr($url, 0, 2048),
            'http_status'   => $result['http_status'] ?? null,
            'fetch_time_ms' => $result['fetch_time_ms'] ?? null,
            'success'       => $result['success'] ? 1 : 0,
            'error_message' => isset($result['error']) ? mb_substr($result['error'], 0, 500) : null
        ]);
    }
}
