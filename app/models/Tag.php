<?php
/**
 * Tag Model
 * Handles tag operations with usage tracking
 * 
 * @package BookmarkManager\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Helpers\Sanitizer;

class Tag extends BaseModel
{
    protected static string $table = 'tags';
    protected static string $primaryKey = 'id';
    
    protected static array $fillable = [
        'name',
        'slug',
        'usage_count'
    ];

    /**
     * Find or create tag by name
     */
    public static function findOrCreate(string $name): array
    {
        $name = trim($name);
        $slug = Sanitizer::slug($name);
        
        $tag = self::findBy('slug', $slug);
        
        if ($tag) {
            return $tag;
        }

        $id = self::create([
            'name' => $name,
            'slug' => $slug
        ]);

        return self::find($id);
    }

    /**
     * Find or create multiple tags
     */
    public static function findOrCreateMultiple(array $names): array
    {
        $tags = [];
        
        foreach ($names as $name) {
            $name = trim($name);
            if (!empty($name)) {
                $tags[] = self::findOrCreate($name);
            }
        }

        return $tags;
    }

    /**
     * Get tags for a bookmark
     */
    public static function getForBookmark(int $bookmarkId): array
    {
        $sql = "SELECT t.* FROM tags t
                INNER JOIN bookmark_tags bt ON t.id = bt.tag_id
                WHERE bt.bookmark_id = ?
                ORDER BY t.name";
        
        return Database::fetchAll($sql, [$bookmarkId]);
    }

    /**
     * Get popular tags
     */
    public static function getPopular(int $limit = 20): array
    {
        $sql = "SELECT * FROM tags 
                WHERE usage_count > 0 
                ORDER BY usage_count DESC, name 
                LIMIT ?";
        
        return Database::fetchAll($sql, [$limit]);
    }

    /**
     * Search tags by name
     */
    public static function search(string $query, int $limit = 10): array
    {
        $sql = "SELECT * FROM tags 
                WHERE name LIKE ? 
                ORDER BY usage_count DESC, name 
                LIMIT ?";
        
        return Database::fetchAll($sql, ['%' . $query . '%', $limit]);
    }

    /**
     * Update usage counts for all tags
     */
    public static function recalculateUsageCounts(): void
    {
        $sql = "UPDATE tags t 
                SET usage_count = (
                    SELECT COUNT(*) FROM bookmark_tags bt WHERE bt.tag_id = t.id
                )";
        
        Database::execute($sql);
    }

    /**
     * Increment usage count
     */
    public static function incrementUsage(int $tagId): void
    {
        $sql = "UPDATE tags SET usage_count = usage_count + 1 WHERE id = ?";
        Database::execute($sql, [$tagId]);
    }

    /**
     * Decrement usage count
     */
    public static function decrementUsage(int $tagId): void
    {
        $sql = "UPDATE tags SET usage_count = GREATEST(0, usage_count - 1) WHERE id = ?";
        Database::execute($sql, [$tagId]);
    }

    /**
     * Merge tags (combine source into target)
     */
    public static function merge(int $sourceId, int $targetId): bool
    {
        if ($sourceId === $targetId) {
            return false;
        }

        Database::beginTransaction();
        
        try {
            // Update bookmark_tags to point to target
            $sql = "UPDATE IGNORE bookmark_tags SET tag_id = ? WHERE tag_id = ?";
            Database::execute($sql, [$targetId, $sourceId]);
            
            // Delete orphaned associations
            Database::delete('bookmark_tags', 'tag_id = ?', [$sourceId]);
            
            // Delete source tag
            self::delete($sourceId);
            
            // Recalculate target usage
            self::recalculateUsageCounts();
            
            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Delete unused tags
     */
    public static function deleteUnused(): int
    {
        return Database::execute(
            "DELETE FROM tags WHERE usage_count = 0 AND id NOT IN (
                SELECT DISTINCT tag_id FROM bookmark_tags
            )"
        )->rowCount();
    }

    /**
     * Get tag cloud data (with font sizes)
     */
    public static function getCloud(int $limit = 50): array
    {
        $tags = self::getPopular($limit);
        
        if (empty($tags)) {
            return [];
        }

        $max = max(array_column($tags, 'usage_count'));
        $min = min(array_column($tags, 'usage_count'));
        $range = $max - $min ?: 1;

        foreach ($tags as &$tag) {
            // Calculate font size between 1-5
            $tag['size'] = ceil((($tag['usage_count'] - $min) / $range) * 4) + 1;
        }

        return $tags;
    }
}
