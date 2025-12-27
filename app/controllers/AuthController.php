<?php
/**
 * Auth Controller
 * Handles user authentication
 * 
 * @package BookmarkManager\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Sanitizer;

class AuthController extends BaseController
{
    /**
     * Show login form
     */
    public function showLogin(): string
    {
        if (Auth::check()) {
            $this->redirect('/bookmarks');
        }

        return $this->view('auth/login', [
            'title' => 'Login'
        ], false);
    }

    /**
     * Process login
     */
    public function login(): void
    {
        $this->validateCsrf();

        $username = Sanitizer::string($this->input('username'), 50);
        $password = $this->input('password') ?? '';

        if (empty($username) || empty($password)) {
            $this->flash('error', 'Please enter username and password');
            $this->redirect('/login');
        }

        if (Auth::attempt($username, $password)) {
            $this->flash('success', 'Welcome back!');
            $this->redirect('/bookmarks');
        }

        $this->flash('error', 'Invalid credentials');
        $this->redirect('/login');
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        Auth::logout();
        $this->flash('success', 'Logged out successfully');
        $this->redirect('/login');
    }

    /**
     * Show dashboard
     */
    public function dashboard(): string
    {
        $this->requireAuth();

        $stats = \App\Models\Bookmark::getStats();
        $recentBookmarks = \App\Models\Bookmark::paginateWithRelations(1, 10);
        $categoriesFlat = \App\Models\Category::getFlatTreeWithCounts();

        return $this->view('dashboard', [
            'stats'           => $stats,
            'recentBookmarks' => $recentBookmarks['items'],
            'categoriesFlat'  => $categoriesFlat,
            'title'           => 'Dashboard'
        ]);
    }
}
