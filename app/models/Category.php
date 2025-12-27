<?php
/**
 * Category Model
 * Handles nested category operations (Adjacency List)
 * 
 * @package BookmarkManager\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Helpers\Sanitizer;

class Category extends BaseModel
{
    protected static string $table = 'categories';
    protected static string $primaryKey = 'id';
    
    protected static array $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'color',
        'level',
        'sort_order'
    ];

    /**
     * Create category with auto-generated slug
     */
    public static function createCategory(array $data): int
    {
        $data['slug'] = self::generateUniqueSlug($data['name']);
        $data['level'] = self::calculateLevel($data['parent_id'] ?? null);
        
        return self::create($data);
    }

    /**
     * Find category by name
     */
    public static function findByName(string $name): ?array
    {
        $sql = "SELECT * FROM categories WHERE name = ? LIMIT 1";
        return Database::fetchOne($sql, [$name]);
    }

    /**
     * Get full category tree (hierarchical)
     */
    public static function getTree(?int $parentId = null): array
    {
        $sql = "SELECT * FROM categories 
                WHERE parent_id " . ($parentId === null ? "IS NULL" : "= ?") . "
                ORDER BY sort_order, name";
        
        $params = $parentId === null ? [] : [$parentId];
        $categories = Database::fetchAll($sql, $params);

        foreach ($categories as &$category) {
            $category['children'] = self::getTree($category['id']);
            $category['bookmark_count'] = self::getBookmarkCount($category['id']);
        }

        return $categories;
    }

    /**
     * Get flattened tree with indentation
     */
    public static function getFlatTree(?int $parentId = null, int $depth = 0): array
    {
        $result = [];
        
        $sql = "SELECT * FROM categories 
                WHERE parent_id " . ($parentId === null ? "IS NULL" : "= ?") . "
                ORDER BY sort_order, name";
        
        $params = $parentId === null ? [] : [$parentId];
        $categories = Database::fetchAll($sql, $params);

        foreach ($categories as $category) {
            $category['depth'] = $depth;
            $category['prefix'] = str_repeat('— ', $depth);
            $result[] = $category;
            
            // Recursively get children (respecting max depth)
            if ($depth < CATEGORY_MAX_DEPTH) {
                $children = self::getFlatTree($category['id'], $depth + 1);
                $result = array_merge($result, $children);
            }
        }

        return $result;
    }

    /**
     * Get flattened tree with bookmark counts (for dashboard)
     */
    public static function getFlatTreeWithCounts(?int $parentId = null, int $depth = 0): array
    {
        $result = [];
        
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM bookmarks b WHERE b.category_id = c.id) as bookmark_count
                FROM categories c 
                WHERE c.parent_id " . ($parentId === null ? "IS NULL" : "= ?") . "
                ORDER BY c.sort_order, c.name";
        
        $params = $parentId === null ? [] : [$parentId];
        $categories = Database::fetchAll($sql, $params);

        foreach ($categories as $category) {
            $category['depth'] = $depth;
            $result[] = $category;
            
            // Recursively get children (max 3 levels for dashboard)
            if ($depth < 3) {
                $children = self::getFlatTreeWithCounts($category['id'], $depth + 1);
                $result = array_merge($result, $children);
            }
        }

        return $result;
    }

    /**
     * Get direct children of a category
     */
    public static function getChildren(int $categoryId): array
    {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM bookmarks b WHERE b.category_id = c.id) as bookmark_count
                FROM categories c 
                WHERE c.parent_id = ? 
                ORDER BY c.sort_order, c.name";
        
        return Database::fetchAll($sql, [$categoryId]);
    }

    /**
     * Get breadcrumb path
     */
    public static function getBreadcrumb(int $categoryId): array
    {
        $breadcrumb = [];
        $current = self::find($categoryId);

        while ($current) {
            array_unshift($breadcrumb, $current);
            $current = $current['parent_id'] ? self::find($current['parent_id']) : null;
        }

        return $breadcrumb;
    }

    /**
     * Get category path as string (e.g., "Parent / Child / Grandchild")
     */
    public static function getPathString(int $categoryId, string $separator = ' / '): string
    {
        $breadcrumb = self::getBreadcrumb($categoryId);
        return implode($separator, array_column($breadcrumb, 'name'));
    }

    /**
     * Get all descendants IDs
     */
    public static function getDescendantIds(int $categoryId): array
    {
        $ids = [];
        $children = Database::fetchAll(
            "SELECT id FROM categories WHERE parent_id = ?",
            [$categoryId]
        );

        foreach ($children as $child) {
            $ids[] = $child['id'];
            $ids = array_merge($ids, self::getDescendantIds($child['id']));
        }

        return $ids;
    }

    /**
     * Move category to new parent
     */
    public static function move(int $categoryId, ?int $newParentId): bool
    {
        // Prevent moving to self or descendant
        if ($newParentId !== null) {
            $descendants = self::getDescendantIds($categoryId);
            if ($newParentId === $categoryId || in_array($newParentId, $descendants)) {
                return false;
            }
        }

        $newLevel = self::calculateLevel($newParentId);
        
        return self::update($categoryId, [
            'parent_id' => $newParentId,
            'level'     => $newLevel
        ]) > 0;
    }

    /**
     * Safe delete - reassign children to parent
     */
    public static function safeDelete(int $categoryId): bool
    {
        $category = self::find($categoryId);
        if (!$category) {
            return false;
        }

        Database::beginTransaction();
        
        try {
            // Move children to parent
            Database::execute(
                "UPDATE categories SET parent_id = ?, level = level - 1 WHERE parent_id = ?",
                [$category['parent_id'], $categoryId]
            );
            
            // Move bookmarks to uncategorized
            Database::execute(
                "UPDATE bookmarks SET category_id = NULL WHERE category_id = ?",
                [$categoryId]
            );
            
            // Delete category
            self::delete($categoryId);
            
            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Get bookmark count for category
     */
    public static function getBookmarkCount(int $categoryId, bool $includeChildren = false): int
    {
        if ($includeChildren) {
            $ids = array_merge([$categoryId], self::getDescendantIds($categoryId));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            return (int) Database::fetchColumn(
                "SELECT COUNT(*) FROM bookmarks WHERE category_id IN ({$placeholders})",
                $ids
            );
        }

        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM bookmarks WHERE category_id = ?",
            [$categoryId]
        );
    }

    /**
     * Calculate level based on parent
     */
    private static function calculateLevel(?int $parentId): int
    {
        if ($parentId === null) {
            return 0;
        }

        $parent = self::find($parentId);
        return $parent ? $parent['level'] + 1 : 0;
    }

    /**
     * Generate unique slug
     */
    private static function generateUniqueSlug(string $name): string
    {
        $slug = Sanitizer::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (self::exists('slug', $slug)) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }
}
