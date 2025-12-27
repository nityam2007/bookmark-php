<?php
/**
 * Database Connection Singleton
 * PDO wrapper with connection pooling for shared hosting
 * 
 * @package BookmarkManager\Core
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static array $queryLog = [];
    private static int $transactionLevel = 0;

    /**
     * Get database connection (singleton)
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     */
    private static function connect(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false, // Shared hosting friendly
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                throw new PDOException("Database connection failed: " . $e->getMessage());
            }
            throw new PDOException("Database connection failed. Please check configuration.");
        }
    }

    /**
     * Execute a prepared statement
     */
    public static function execute(string $sql, array $params = []): \PDOStatement
    {
        $pdo = self::getConnection();
        
        if (APP_DEBUG) {
            self::$queryLog[] = ['sql' => $sql, 'params' => $params, 'time' => microtime(true)];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt;
    }

    /**
     * Fetch single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $result = self::execute($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::execute($sql, $params)->fetchAll();
    }

    /**
     * Fetch single column value
     */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        return self::execute($sql, $params)->fetchColumn($column);
    }

    /**
     * Insert and return last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        // Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new \InvalidArgumentException('Invalid table name');
        }
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        self::execute($sql, array_values($data));
        
        return (int) self::getConnection()->lastInsertId();
    }

    /**
     * Update rows
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        // Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new \InvalidArgumentException('Invalid table name');
        }
        
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        return self::execute($sql, $params)->rowCount();
    }

    /**
     * Delete rows
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        // Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new \InvalidArgumentException('Invalid table name');
        }
        
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return self::execute($sql, $params)->rowCount();
    }

    /**
     * Begin transaction (supports nested calls)
     */
    public static function beginTransaction(): bool
    {
        self::$transactionLevel++;
        
        if (self::$transactionLevel === 1) {
            return self::getConnection()->beginTransaction();
        }
        
        // Nested transaction - just increment level
        return true;
    }

    /**
     * Commit transaction (supports nested calls)
     */
    public static function commit(): bool
    {
        if (self::$transactionLevel > 0) {
            self::$transactionLevel--;
        }
        
        if (self::$transactionLevel === 0) {
            return self::getConnection()->commit();
        }
        
        // Nested transaction - just decrement level
        return true;
    }

    /**
     * Rollback transaction (supports nested calls)
     */
    public static function rollback(): bool
    {
        $level = self::$transactionLevel;
        self::$transactionLevel = 0;
        
        if ($level > 0) {
            return self::getConnection()->rollBack();
        }
        
        return true;
    }

    /**
     * Check if transaction is active
     */
    public static function inTransaction(): bool
    {
        return self::$transactionLevel > 0;
    }

    /**
     * Get query log (debug only)
     */
    public static function getQueryLog(): array
    {
        return self::$queryLog;
    }

    /**
     * Close connection
     */
    public static function close(): void
    {
        self::$instance = null;
    }
}
