<?php
/**
 * Search Service
 * Fast search with caching and full-text support
 * 
 * @package BookmarkManager\Services
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Bookmark;

class SearchService
{
    private CacheService $cache;

    public function __construct()
    {
        $this->cache = new CacheService();
    }

    /**
     * Search bookmarks
     */
    public function search(string $query, array $options = []): array
    {
        $query = trim($query);
        
        if (strlen($query) < SEARCH_MIN_LENGTH) {
            return $this->emptyResult();
        }

        // Generate cache key
        $cacheKey = $this->getCacheKey($query, $options);
        
        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Perform search
        $results = $this->performSearch($query, $options);
        
        // Cache results
        $this->cache->set($cacheKey, $results, SEARCH_CACHE_TTL);

        return $results;
    }

    /**
     * Perform the actual search
     */
    private function performSearch(string $query, array $options): array
    {
        $page = max(1, (int)($options['page'] ?? 1));
        $perPage = min(SEARCH_MAX_RESULTS, (int)($options['per_page'] ?? ITEMS_PER_PAGE));
        $offset = ($page - 1) * $perPage;

        // Determine search method
        $useFullText = $this->canUseFullText($query);
        
        if ($useFullText) {
            try {
                return $this->fullTextSearch($query, $page, $perPage, $offset, $options);
            } catch (\PDOException $e) {
                // Fallback to LIKE if fulltext fails (e.g., missing index)
            }
        }

        return $this->likeSearch($query, $page, $perPage, $offset, $options);
    }

    /**
     * Get ORDER BY clause based on sort option
     */
    private function getSortOrder(array $options, bool $hasRelevance = false): string
    {
        $sort = $options['sort'] ?? 'relevance';
        
        return match($sort) {
            'newest'  => 'b.created_at DESC',
            'oldest'  => 'b.created_at ASC',
            'title'   => 'b.title ASC',
            default   => $hasRelevance ? 'relevance DESC, b.created_at DESC' : 'b.created_at DESC'
        };
    }

    /**
     * Full-text search (faster for larger datasets)
     */
    private function fullTextSearch(
        string $query,
        int $page,
        int $perPage,
        int $offset,
        array $options
    ): array {
        $searchQuery = $this->prepareFullTextQuery($query);
        
        $whereExtra = $this->buildFilterWhere($options);
        $params = [];

        // FULLTEXT columns must exactly match the index: title, description, url, meta_title, meta_description, meta_keywords
        $ftMatch = 'MATCH(b.title, b.description, b.url, b.meta_title, b.meta_description, b.meta_keywords) AGAINST(? IN BOOLEAN MODE)';

        // Count total
        $countSql = "SELECT COUNT(*) FROM bookmarks b 
                     WHERE {$ftMatch}
                     {$whereExtra['sql']}";
        $params[] = $searchQuery;
        $params = array_merge($params, $whereExtra['params']);
        
        $total = (int) Database::fetchColumn($countSql, $params);

        // Get results with relevance score
        $params = [];
        $orderBy = $this->getSortOrder($options, true);
        $sql = "SELECT b.*, c.name as category_name,
                {$ftMatch} as relevance
                FROM bookmarks b
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE {$ftMatch}
                {$whereExtra['sql']}
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";
        
        $params[] = $searchQuery;
        $params[] = $searchQuery;
        $params[] = $searchQuery;
        $params = array_merge($params, $whereExtra['params']);
        $params[] = $perPage;
        $params[] = $offset;

        $items = Database::fetchAll($sql, $params);
        $items = $this->enrichResults($items);

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
            'query'       => $query,
            'method'      => 'fulltext'
        ];
    }

    /**
     * LIKE-based search (fallback for short queries)
     */
    private function likeSearch(
        string $query,
        int $page,
        int $perPage,
        int $offset,
        array $options
    ): array {
        $likeQuery = '%' . $query . '%';
        $whereExtra = $this->buildFilterWhere($options);

        // Search in all text columns including meta fields
        $likeWhere = '(b.title LIKE ? OR b.description LIKE ? OR b.url LIKE ? OR b.meta_title LIKE ? OR b.meta_description LIKE ? OR b.meta_keywords LIKE ?)';

        // Count total
        $countSql = "SELECT COUNT(*) FROM bookmarks b 
                     WHERE {$likeWhere}
                     {$whereExtra['sql']}";
        
        $countParams = [$likeQuery, $likeQuery, $likeQuery, $likeQuery, $likeQuery, $likeQuery];
        $countParams = array_merge($countParams, $whereExtra['params']);
        
        $total = (int) Database::fetchColumn($countSql, $countParams);

        // Get results
        $orderBy = $this->getSortOrder($options, false);
        $sql = "SELECT b.*, c.name as category_name
                FROM bookmarks b
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE {$likeWhere}
                {$whereExtra['sql']}
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";
        
        $params = [$likeQuery, $likeQuery, $likeQuery, $likeQuery, $likeQuery, $likeQuery];
        $params = array_merge($params, $whereExtra['params']);
        $params[] = $perPage;
        $params[] = $offset;

        $items = Database::fetchAll($sql, $params);
        $items = $this->enrichResults($items);

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
            'query'       => $query,
            'method'      => 'like'
        ];
    }

    /**
     * Search in tags
     */
    public function searchByTag(string $tagSlug, array $options = []): array
    {
        $page = max(1, (int)($options['page'] ?? 1));
        $perPage = min(SEARCH_MAX_RESULTS, (int)($options['per_page'] ?? ITEMS_PER_PAGE));
        $offset = ($page - 1) * $perPage;

        // Get tag
        $tag = Database::fetchOne("SELECT id, name FROM tags WHERE slug = ?", [$tagSlug]);
        
        if (!$tag) {
            return $this->emptyResult();
        }

        // Count
        $total = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM bookmark_tags WHERE tag_id = ?",
            [$tag['id']]
        );

        // Get bookmarks
        $sql = "SELECT b.*, c.name as category_name
                FROM bookmarks b
                INNER JOIN bookmark_tags bt ON b.id = bt.bookmark_id
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE bt.tag_id = ?
                ORDER BY b.created_at DESC
                LIMIT ? OFFSET ?";

        $items = Database::fetchAll($sql, [$tag['id'], $perPage, $offset]);
        $items = $this->enrichResults($items);

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
            'tag'         => $tag
        ];
    }

    /**
     * Build additional WHERE clauses from filters
     */
    private function buildFilterWhere(array $options): array
    {
        $sql = '';
        $params = [];

        if (!empty($options['category_id'])) {
            $sql .= ' AND b.category_id = ?';
            $params[] = $options['category_id'];
        }

        if (isset($options['is_favorite'])) {
            $sql .= ' AND b.is_favorite = ?';
            $params[] = (int) $options['is_favorite'];
        }

        if (isset($options['is_archived'])) {
            $sql .= ' AND b.is_archived = ?';
            $params[] = (int) $options['is_archived'];
        }

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Enrich results with tags
     */
    private function enrichResults(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $ids = array_column($items, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT bt.bookmark_id, t.id, t.name, t.slug 
                FROM bookmark_tags bt
                INNER JOIN tags t ON bt.tag_id = t.id
                WHERE bt.bookmark_id IN ({$placeholders})";

        $tags = Database::fetchAll($sql, $ids);

        $tagsMap = [];
        foreach ($tags as $tag) {
            $tagsMap[$tag['bookmark_id']][] = [
                'id'   => $tag['id'],
                'name' => $tag['name'],
                'slug' => $tag['slug']
            ];
        }

        foreach ($items as &$item) {
            $item['tags'] = $tagsMap[$item['id']] ?? [];
        }

        return $items;
    }

    /**
     * Check if fulltext search can be used
     */
    private function canUseFullText(string $query): bool
    {
        // MySQL requires minimum 3 chars for fulltext
        return strlen($query) >= 3;
    }

    /**
     * Prepare query for fulltext search
     */
    private function prepareFullTextQuery(string $query): string
    {
        $words = preg_split('/\s+/', $query);
        $prepared = array_map(function($word) {
            // Add + for required words, * for partial matching
            return '+' . preg_replace('/[^\w]/', '', $word) . '*';
        }, $words);
        
        return implode(' ', $prepared);
    }

    /**
     * Generate cache key
     */
    private function getCacheKey(string $query, array $options): string
    {
        return 'search_' . hash('sha256', $query . serialize($options));
    }

    /**
     * Return empty result structure
     */
    private function emptyResult(): array
    {
        return [
            'items'       => [],
            'total'       => 0,
            'page'        => 1,
            'per_page'    => ITEMS_PER_PAGE,
            'total_pages' => 0
        ];
    }

    /**
     * Clear search cache
     */
    public function clearCache(): void
    {
        $this->cache->clear('search_');
    }
}
