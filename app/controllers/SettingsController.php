<?php
/**
 * Settings Controller
 * Handles user profile and settings management
 * 
 * @package BookmarkManager\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\ApiKey;
use App\Core\Database;
use App\Helpers\Auth;
use App\Helpers\Sanitizer;

class SettingsController extends BaseController
{
    /**
     * Show settings page
     */
    public function index(): string
    {
        $this->requireAuth();

        $user = User::find(Auth::id());
        
        // Try to get API keys, but handle case where table doesn't exist
        try {
            $apiKeys = ApiKey::getByUser(Auth::id());
        } catch (\Exception $e) {
            $apiKeys = [];
        }

        return $this->view('settings/index', [
            'user'    => $user,
            'apiKeys' => $apiKeys,
            'title'   => 'Settings'
        ]);
    }

    /**
     * Generate a new API key
     */
    public function generateApiKey(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = Auth::id();
        $name = Sanitizer::string($this->input('key_name'), 100);

        if (empty($name)) {
            $name = 'API Key ' . date('Y-m-d');
        }

        // Limit number of API keys per user
        $keyCount = ApiKey::countByUser($userId);
        if ($keyCount >= 10) {
            $this->flash('error', 'Maximum 10 API keys allowed. Please delete unused keys first.');
            $this->redirect('/settings');
        }

        // Generate the key
        $result = ApiKey::generate($userId, $name);

        // Store the key in session to show once (it's not stored in DB)
        $_SESSION['new_api_key'] = $result['key'];
        $_SESSION['new_api_key_name'] = $result['name'];

        $this->flash('success', 'API key generated! Copy it now - it won\'t be shown again.');
        $this->redirect('/settings');
    }

    /**
     * Revoke (deactivate) an API key
     */
    public function revokeApiKey(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $keyId = Sanitizer::int($this->input('key_id'));

        if (!$keyId) {
            $this->flash('error', 'Invalid API key');
            $this->redirect('/settings');
        }

        if (ApiKey::revoke($keyId, Auth::id())) {
            $this->flash('success', 'API key revoked successfully');
        } else {
            $this->flash('error', 'Failed to revoke API key');
        }

        $this->redirect('/settings');
    }

    /**
     * Delete an API key permanently
     */
    public function deleteApiKey(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $keyId = Sanitizer::int($this->input('key_id'));

        if (!$keyId) {
            $this->flash('error', 'Invalid API key');
            $this->redirect('/settings');
        }

        if (ApiKey::deleteKey($keyId, Auth::id())) {
            $this->flash('success', 'API key deleted permanently');
        } else {
            $this->flash('error', 'Failed to delete API key');
        }

        $this->redirect('/settings');
    }

    /**
     * Update profile information (email, username)
     */
    public function updateProfile(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = Auth::id();
        $user = User::find($userId);

        if (!$user) {
            $this->flash('error', 'User not found');
            $this->redirect('/settings');
        }

        $email = Sanitizer::email($this->input('email'));
        $username = Sanitizer::string($this->input('username'), 50);

        // Validate email
        if (!$email) {
            $this->flash('error', 'Please enter a valid email address');
            $this->redirect('/settings');
        }

        // Validate username
        if (empty($username) || strlen($username) < 3) {
            $this->flash('error', 'Username must be at least 3 characters');
            $this->redirect('/settings');
        }

        // Check if email is already taken by another user
        $existingEmail = User::findByEmail($email);
        if ($existingEmail && $existingEmail['id'] !== $userId) {
            $this->flash('error', 'This email is already in use');
            $this->redirect('/settings');
        }

        // Check if username is already taken by another user
        $existingUsername = User::findByUsername($username);
        if ($existingUsername && $existingUsername['id'] !== $userId) {
            $this->flash('error', 'This username is already taken');
            $this->redirect('/settings');
        }

        // Update user
        User::update($userId, [
            'email'    => $email,
            'username' => $username
        ]);

        // Update session cache
        Auth::refresh();

        $this->flash('success', 'Profile updated successfully');
        $this->redirect('/settings');
    }

    /**
     * Update password
     */
    public function updatePassword(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = Auth::id();

        $currentPassword = $this->input('current_password') ?? '';
        $newPassword = $this->input('new_password') ?? '';
        $confirmPassword = $this->input('confirm_password') ?? '';

        // Validate current password
        if (empty($currentPassword)) {
            $this->flash('error', 'Please enter your current password');
            $this->redirect('/settings');
        }

        // Verify current password
        if (!User::verifyPassword($userId, $currentPassword)) {
            $this->flash('error', 'Current password is incorrect');
            $this->redirect('/settings');
        }

        // Validate new password length
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            $this->flash('error', 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters');
            $this->redirect('/settings');
        }

        // Confirm passwords match
        if ($newPassword !== $confirmPassword) {
            $this->flash('error', 'New passwords do not match');
            $this->redirect('/settings');
        }

        // Don't allow same password
        if ($currentPassword === $newPassword) {
            $this->flash('error', 'New password must be different from current password');
            $this->redirect('/settings');
        }

        // Update password
        if (User::updatePassword($userId, $newPassword)) {
            // Regenerate session for security
            session_regenerate_id(true);
            $this->flash('success', 'Password changed successfully');
        } else {
            $this->flash('error', 'Failed to update password. Please try again.');
        }

        $this->redirect('/settings');
    }

    /**
     * Delete all user data (bookmarks, categories, tags) but keep account
     */
    public function deleteAllData(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = Auth::id();
        $password = $this->input('password') ?? '';

        // Verify password before deletion
        if (!User::verifyPassword($userId, $password)) {
            $this->flash('error', 'Password is incorrect');
            $this->redirect('/settings');
        }

        try {
            Database::beginTransaction();

            // Delete all bookmark_tags first (foreign key constraint)
            Database::execute("DELETE FROM bookmark_tags");
            
            // Delete all bookmarks
            Database::execute("DELETE FROM bookmarks");
            
            // Delete all tags
            Database::execute("DELETE FROM tags");
            
            // Delete all categories except the default one, then reset to just Uncategorized
            Database::execute("DELETE FROM categories");
            Database::execute("INSERT INTO categories (name, slug, description, level) VALUES ('Uncategorized', 'uncategorized', 'Default category for bookmarks', 0)");
            
            // Clear search cache
            Database::execute("DELETE FROM search_cache");
            
            // Clear image cache directory
            $cacheDir = __DIR__ . '/../../cache/images';
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file) && basename($file) !== '.gitkeep') {
                        @unlink($file);
                    }
                }
            }

            Database::commit();
            $this->flash('success', 'All data has been deleted successfully. Your account is still active.');
        } catch (\Exception $e) {
            Database::rollback();
            $this->flash('error', 'Failed to delete data: ' . $e->getMessage());
        }

        $this->redirect('/settings');
    }

    /**
     * Delete account (optional - for future use)
     */
    public function deleteAccount(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = Auth::id();
        $password = $this->input('password') ?? '';

        // Verify password before deletion
        if (!User::verifyPassword($userId, $password)) {
            $this->flash('error', 'Password is incorrect');
            $this->redirect('/settings');
        }

        // Check if user is admin
        if (Auth::isAdmin()) {
            // Count admins
            $adminCount = User::count(['role' => 'admin']);
            if ($adminCount <= 1) {
                $this->flash('error', 'Cannot delete the last admin account');
                $this->redirect('/settings');
            }
        }

        // Delete user
        User::delete($userId);
        
        // Logout
        Auth::logout();

        $this->flash('success', 'Your account has been deleted');
        $this->redirect('/login');
    }
}
