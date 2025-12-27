<?php
/**
 * User Model
 * Handles user authentication and management
 * 
 * @package BookmarkManager\Models
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User extends BaseModel
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';
    
    protected static array $fillable = [
        'username',
        'email',
        'password_hash',
        'role',
        'is_active'
    ];

    protected static array $hidden = [
        'password_hash'
    ];

    /**
     * Find by username
     */
    public static function findByUsername(string $username): ?array
    {
        return self::findBy('username', $username);
    }

    /**
     * Find by email
     */
    public static function findByEmail(string $email): ?array
    {
        return self::findBy('email', strtolower($email));
    }

    /**
     * Authenticate user
     */
    public static function authenticate(string $username, string $password): ?array
    {
        $user = self::findByUsername($username);
        
        if (!$user || !$user['is_active']) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        // Update last login
        Database::execute(
            "UPDATE users SET last_login_at = NOW() WHERE id = ?",
            [$user['id']]
        );

        return self::hideFields($user);
    }

    /**
     * Create user with hashed password
     */
    public static function createUser(array $data): int
    {
        $data['password_hash'] = password_hash(
            $data['password'],
            PASSWORD_BCRYPT,
            ['cost' => 12]
        );
        unset($data['password']);
        
        $data['email'] = strtolower($data['email']);
        
        return self::create($data);
    }

    /**
     * Update password
     */
    public static function updatePassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        return self::update($id, ['password_hash' => $hash]) > 0;
    }

    /**
     * Verify current password
     */
    public static function verifyPassword(int $id, string $password): bool
    {
        $sql = "SELECT password_hash FROM users WHERE id = ?";
        $hash = Database::fetchColumn($sql, [$id]);
        
        return $hash && password_verify($password, $hash);
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin(int $id): bool
    {
        $sql = "SELECT role FROM users WHERE id = ? AND is_active = 1";
        return Database::fetchColumn($sql, [$id]) === 'admin';
    }

    /**
     * Get safe user data (no password)
     */
    public static function getSafe(int $id): ?array
    {
        $user = self::find($id);
        return $user ? self::hideFields($user) : null;
    }
}
