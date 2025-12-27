<?php
/**
 * Category Controller
 * Handles nested category management
 * 
 * @package BookmarkManager\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Bookmark;
use App\Helpers\Sanitizer;

class CategoryController extends BaseController
{
    /**
     * List categories (tree view)
     */
    public function index(): string
    {
        $this->requireAuth();

        $categories = Category::getTree();
        
        return $this->view('categories/index', [
            'categories' => $categories,
            'title'      => 'Categories'
        ]);
    }

    /**
     * Show category with its bookmarks
     */
    public function show(string $id): string
    {
        $this->requireAuth();

        $categoryId = (int) $id;
        $category = Category::find($categoryId);
        
        if (!$category) {
            $this->flash('error', 'Category not found');
            $this->redirect('/categories');
            return '';
        }

        // Get sort and filter options
        $sort = Sanitizer::string($this->input('sort') ?? 'newest', 20);
        $page = max(1, Sanitizer::int($this->input('page')) ?? 1);
        
        $filters = [
            'category_id' => $categoryId,
            'is_favorite' => $this->input('favorites') !== null ? 1 : null,
            'is_archived' => $this->input('archived') !== null ? 1 : null,
        ];
        $filters = array_filter($filters, fn($v) => $v !== null);

        // Get bookmarks in this category
        $bookmarks = Bookmark::paginateWithRelations($page, ITEMS_PER_PAGE, $filters, $sort);
        
        // Get category path
        $categoryPath = Category::getPathString($categoryId, ' / ');
        
        // Get subcategories
        $subcategories = Category::getChildren($categoryId);

        return $this->view('categories/show', [
            'category'     => $category,
            'categoryPath' => $categoryPath,
            'subcategories'=> $subcategories,
            'bookmarks'    => $bookmarks['items'],
            'pagination'   => $bookmarks,
            'sort'         => $sort,
            'filters'      => $filters,
            'title'        => $category['name']
        ]);
    }

    /**
     * Show create form
     */
    public function create(): string
    {
        $this->requireAuth();

        $parentId = Sanitizer::int($this->input('parent'));
        $categories = Category::getFlatTree();

        return $this->view('categories/create', [
            'categories' => $categories,
            'parentId'   => $parentId,
            'title'      => 'Add Category'
        ]);
    }

    /**
     * Store new category
     */
    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $name = Sanitizer::string($this->input('name'), 100);
        
        if (empty($name)) {
            $this->flash('error', 'Category name is required');
            $this->redirect('/categories/create');
            return; // Unreachable but indicates intent
        }

        $parentId = Sanitizer::int($this->input('parent_id'));
        
        // Check depth limit
        if ($parentId) {
            $parent = Category::find($parentId);
            if ($parent && $parent['level'] >= CATEGORY_MAX_DEPTH - 1) {
                $this->flash('error', 'Maximum category depth reached');
                $this->redirect('/categories/create');
                return; // Unreachable but indicates intent
            }
        }

        Category::createCategory([
            'name'        => $name,
            'parent_id'   => $parentId,
            'description' => Sanitizer::string($this->input('description')),
            'sort_order'  => Sanitizer::int($this->input('sort_order')) ?? 0
        ]);

        $this->flash('success', 'Category created successfully');
        $this->redirect('/categories');
    }

    /**
     * Show edit form
     */
    public function edit(string $id): string
    {
        $this->requireAuth();

        $category = Category::find((int)$id);
        
        if (!$category) {
            $this->flash('error', 'Category not found');
            $this->redirect('/categories');
            return ''; // Unreachable but satisfies return type
        }

        // Get categories excluding self and descendants
        $descendants = Category::getDescendantIds((int)$id);
        $descendants[] = (int)$id;
        
        $allCategories = Category::getFlatTree();
        $categories = array_filter($allCategories, fn($c) => !in_array($c['id'], $descendants));

        return $this->view('categories/edit', [
            'category'   => $category,
            'categories' => $categories,
            'title'      => 'Edit Category'
        ]);
    }

    /**
     * Update category
     */
    public function update(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $categoryId = (int)$id;
        $category = Category::find($categoryId);
        
        if (!$category) {
            $this->flash('error', 'Category not found');
            $this->redirect('/categories');
            return; // Unreachable but indicates intent
        }

        $name = Sanitizer::string($this->input('name'), 100);
        
        if (empty($name)) {
            $this->flash('error', 'Category name is required');
            $this->redirect("/categories/{$id}/edit");
            return; // Unreachable but indicates intent
        }

        $newParentId = Sanitizer::int($this->input('parent_id'));
        
        // Check if parent is changing
        if ($newParentId !== $category['parent_id']) {
            if (!Category::move($categoryId, $newParentId)) {
                $this->flash('error', 'Invalid parent category');
                $this->redirect("/categories/{$id}/edit");
                return; // Unreachable but indicates intent
            }
        }

        Category::update($categoryId, [
            'name'        => $name,
            'description' => Sanitizer::string($this->input('description')),
            'sort_order'  => Sanitizer::int($this->input('sort_order')) ?? 0
        ]);

        $this->flash('success', 'Category updated successfully');
        $this->redirect('/categories');
    }

    /**
     * Delete category
     */
    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        Category::safeDelete((int)$id);
        
        if ($this->isAjax()) {
            $this->json(['success' => true]);
        }

        $this->flash('success', 'Category deleted');
        $this->redirect('/categories');
    }

    /**
     * Get category tree (JSON for AJAX)
     */
    public function tree(): void
    {
        $this->requireAuth();
        
        $categories = Category::getTree();
        $this->json($categories);
    }

    /**
     * Move category (AJAX)
     */
    public function move(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $newParentId = Sanitizer::int($this->input('parent_id'));
        
        $success = Category::move((int)$id, $newParentId);
        
        $this->json([
            'success' => $success,
            'message' => $success ? 'Category moved' : 'Failed to move category'
        ]);
    }
}
