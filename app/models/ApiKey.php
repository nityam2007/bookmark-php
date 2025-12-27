<?php
/**
 * API Key Model
 * Handles API key generation and validation
 * 
 * @package BookmarkManager\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ApiKey extends BaseModel
{
    protected static string $table = 'api_keys';
    protected static string $primaryKey = 'id';
    
    protected static array $fillable = [
        'user_id',
        'name',
        'key_hash',
        'key_prefix',
        'permissions',
        'expires_at',
        'is_active'
    ];

    /**
     * Generate a new API key for a user
     * Returns the plain text key (only shown once!)
     */
    public static function generate(int $userId, string $name, ?string $expiresAt = null): array
    {
        // Generate a secure random key: bm_xxxxxxxxxxxxxxxxxxxxxxxxxxxx (32 chars after prefix)
        $plainKey = 'bm_' . bin2hex(random_bytes(24)); // 48 hex chars = very secure
        
        $keyHash = hash('sha256', $plainKey);
        $keyPrefix = substr($plainKey, 0, 8); // "bm_xxxxx" for identification
        
        $id = self::create([
            'user_id'    => $userId,
            'name'       => $name,
            'key_hash'   => $keyHash,
            'key_prefix' => $keyPrefix,
            'expires_at' => $expiresAt
        ]);
        
        return [
            'id'         => $id,
            'name'       => $name,
            'key'        => $plainKey, // Only returned once!
            'key_prefix' => $keyPrefix,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Validate an API key and return the user
     */
    public static function validate(string $apiKey): ?array
    {
        $keyHash = hash('sha256', $apiKey);
        
        $sql = "SELECT ak.*, u.id as uid, u.username, u.email, u.role, u.is_active as user_active
                FROM api_keys ak
                JOIN users u ON ak.user_id = u.id
                WHERE ak.key_hash = ?
                  AND ak.is_active = 1
                  AND u.is_active = 1
                  AND (ak.expires_at IS NULL OR ak.expires_at > NOW())
                LIMIT 1";
        
        $result = Database::fetchOne($sql, [$keyHash]);
        
        if (!$result) {
            return null;
        }
        
        // Update last used timestamp
        Database::execute(
            "UPDATE api_keys SET last_used_at = NOW() WHERE id = ?",
            [$result['id']]
        );
        
        // Return user data
        return [
            'id'       => $result['uid'],
            'username' => $result['username'],
            'email'    => $result['email'],
            'role'     => $result['role'],
            'api_key_id' => $result['id'],
            'api_key_name' => $result['name']
        ];
    }

    /**
     * Get all API keys for a user (without the actual keys)
     */
    public static function getByUser(int $userId): array
    {
        $sql = "SELECT id, name, key_prefix, last_used_at, expires_at, is_active, created_at
                FROM api_keys
                WHERE user_id = ?
                ORDER BY created_at DESC";
        
        return Database::fetchAll($sql, [$userId]);
    }

    /**
     * Revoke an API key
     */
    public static function revoke(int $keyId, int $userId): bool
    {
        $sql = "UPDATE api_keys SET is_active = 0 WHERE id = ? AND user_id = ?";
        return Database::execute($sql, [$keyId, $userId]) > 0;
    }

    /**
     * Delete an API key permanently
     */
    public static function deleteKey(int $keyId, int $userId): bool
    {
        $sql = "DELETE FROM api_keys WHERE id = ? AND user_id = ?";
        return Database::execute($sql, [$keyId, $userId]) > 0;
    }

    /**
     * Count active API keys for a user
     */
    public static function countByUser(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM api_keys WHERE user_id = ? AND is_active = 1";
        return (int) Database::fetchColumn($sql, [$userId]);
    }
}
