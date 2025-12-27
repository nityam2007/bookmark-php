<?php
/**
 * Base Model
 * Common database operations for all models
 * 
 * @package BookmarkManager\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

abstract class BaseModel
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected static array $fillable = [];
    protected static array $hidden = [];

    /**
     * Find by primary key
     */
    public static function find(int $id): ?array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ? LIMIT 1";
        return Database::fetchOne($sql, [$id]);
    }

    /**
     * Find by column value
     */
    public static function findBy(string $column, mixed $value): ?array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE {$column} = ? LIMIT 1";
        return Database::fetchOne($sql, [$value]);
    }

    /**
     * Get all records
     */
    public static function all(string $orderBy = 'id', string $direction = 'DESC'): array
    {
        $sql = "SELECT * FROM " . static::$table . " ORDER BY {$orderBy} {$direction}";
        return Database::fetchAll($sql);
    }

    /**
     * Get paginated records
     */
    public static function paginate(int $page = 1, int $perPage = ITEMS_PER_PAGE, array $where = []): array
    {
        $offset = ($page - 1) * $perPage;
        
        $whereClause = '';
        $params = [];
        
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $column => $value) {
                $conditions[] = "{$column} = ?";
                $params[] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        // Get total count
        $countSql = "SELECT COUNT(*) FROM " . static::$table . " {$whereClause}";
        $total = (int) Database::fetchColumn($countSql, $params);

        // Get records - use prepared statement params for LIMIT/OFFSET
        $sql = "SELECT * FROM " . static::$table . " {$whereClause} 
                ORDER BY " . static::$primaryKey . " DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        $items = Database::fetchAll($sql, $params);

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage)
        ];
    }

    /**
     * Create new record
     */
    public static function create(array $data): int
    {
        $filtered = self::filterFillable($data);
        return Database::insert(static::$table, $filtered);
    }

    /**
     * Update record
     */
    public static function update(int $id, array $data): int
    {
        $filtered = self::filterFillable($data);
        return Database::update(static::$table, $filtered, static::$primaryKey . ' = ?', [$id]);
    }

    /**
     * Delete record
     */
    public static function delete(int $id): int
    {
        return Database::delete(static::$table, static::$primaryKey . ' = ?', [$id]);
    }

    /**
     * Check if record exists
     */
    public static function exists(string $column, mixed $value, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM " . static::$table . " WHERE {$column} = ?";
        $params = [$value];

        if ($exceptId !== null) {
            $sql .= " AND " . static::$primaryKey . " != ?";
            $params[] = $exceptId;
        }

        return Database::fetchColumn($sql, $params) !== false;
    }

    /**
     * Count records
     */
    public static function count(array $where = []): int
    {
        $whereClause = '';
        $params = [];
        
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $column => $value) {
                $conditions[] = "{$column} = ?";
                $params[] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        $sql = "SELECT COUNT(*) FROM " . static::$table . " {$whereClause}";
        return (int) Database::fetchColumn($sql, $params);
    }

    /**
     * Filter only fillable fields
     */
    protected static function filterFillable(array $data): array
    {
        if (empty(static::$fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip(static::$fillable));
    }

    /**
     * Hide sensitive fields
     */
    protected static function hideFields(array $record): array
    {
        foreach (static::$hidden as $field) {
            unset($record[$field]);
        }
        return $record;
    }
}
