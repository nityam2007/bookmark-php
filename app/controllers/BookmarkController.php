<?php
/**
 * Bookmark Controller
 * Handles bookmark CRUD operations
 * 
 * @package BookmarkManager\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Tag;
use App\Services\MetaFetcher;
use App\Helpers\Sanitizer;

class BookmarkController extends BaseController
{
    /**
     * List all bookmarks
     */
    public function index(): string
    {
        $this->requireAuth();

        $page = Sanitizer::int($this->input('page'), 1, 1000) ?? 1;
        $sort = Sanitizer::string($this->input('sort') ?? 'newest', 20);
        
        $filters = [
            'category_id' => Sanitizer::int($this->input('category')),
            'tag_id'      => Sanitizer::int($this->input('tag')),
            'is_favorite' => $this->input('favorites') !== null ? 1 : null,
            'is_archived' => $this->input('archived') !== null ? 1 : null,
            'sort'        => $sort
        ];

        $filters = array_filter($filters, fn($v) => $v !== null);

        $bookmarks = Bookmark::paginateWithRelations($page, ITEMS_PER_PAGE, $filters);
        $categories = Category::getFlatTree();
        $tags = Tag::getPopular();
        $stats = Bookmark::getStats();

        return $this->view('bookmarks/index', [
            'bookmarks'  => $bookmarks,
            'categories' => $categories,
            'tags'       => $tags,
            'stats'      => $stats,
            'filters'    => $filters,
            'title'      => 'Bookmarks'
        ]);
    }

    /**
     * Search page
     */
    public function search(): string
    {
        $this->requireAuth();

        $query = Sanitizer::string($this->input('q') ?? '', 255);
        $page = Sanitizer::int($this->input('page'), 1, 1000) ?? 1;
        $sort = Sanitizer::string($this->input('sort') ?? 'relevance', 20);
        
        $options = [
            'page'        => $page,
            'per_page'    => ITEMS_PER_PAGE,
            'category_id' => Sanitizer::int($this->input('category')),
            'is_favorite' => $this->input('favorites') !== null ? 1 : null,
            'sort'        => $sort
        ];
        
        $options = array_filter($options, fn($v) => $v !== null);
        
        $results = ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
        
        if (!empty($query)) {
            $searchService = new \App\Services\SearchService();
            $results = $searchService->search($query, $options);
        }
        
        $categories = Category::getFlatTree();

        return $this->view('search/index', [
            'query'      => $query,
            'results'    => $results,
            'categories' => $categories,
            'title'      => $query ? "Search: {$query}" : 'Search'
        ]);
    }

    /**
     * Show create form
     */
    public function create(): string
    {
        $this->requireAuth();

        $categories = Category::getFlatTree();
        $tags = Tag::getPopular();

        return $this->view('bookmarks/create', [
            'categories' => $categories,
            'tags'       => $tags,
            'title'      => 'Add Bookmark'
        ]);
    }

    /**
     * Store new bookmark
     */
    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $url = Sanitizer::url($this->input('url'));
        
        if (!$url) {
            $this->flash('error', 'Invalid URL provided');
            $this->redirect('/bookmarks/create');
            return; // Unreachable but indicates intent
        }

        if (Bookmark::urlExists($url)) {
            $this->flash('error', 'This URL is already bookmarked');
            $this->redirect('/bookmarks/create');
            return; // Unreachable but indicates intent
        }

        // Fetch meta if not provided
        $title = Sanitizer::string($this->input('title'));
        $description = Sanitizer::string($this->input('description'), 1000);
        
        if (empty($title)) {
            $fetcher = new MetaFetcher();
            $meta = $fetcher->fetch($url);
            
            $title = $meta['title'] ?? parse_url($url, PHP_URL_HOST);
            $description = $description ?: ($meta['description'] ?? null);
            $metaImage = $meta['meta_image'] ?? null;
            $favicon = $meta['favicon'] ?? null;
        } else {
            $metaImage = Sanitizer::url($this->input('meta_image'));
            $favicon = Sanitizer::url($this->input('favicon'));
        }

        $bookmarkId = Bookmark::createBookmark([
            'url'            => $url,
            'title'          => $title,
            'description'    => $description,
            'meta_image'     => $metaImage ?? null,
            'favicon'        => $favicon ?? null,
            'category_id'    => Sanitizer::int($this->input('category_id')),
            'is_favorite'    => Sanitizer::bool($this->input('is_favorite')) ? 1 : 0,
            'meta_fetched_at'=> date('Y-m-d H:i:s')
        ]);

        // Handle tags
        $tagNames = Sanitizer::tags($this->input('tags'));
        if (!empty($tagNames)) {
            $tags = Tag::findOrCreateMultiple($tagNames);
            Bookmark::syncTags($bookmarkId, array_column($tags, 'id'));
        }

        $this->flash('success', 'Bookmark added successfully');
        $this->redirect('/bookmarks');
    }

    /**
     * Show single bookmark
     */
    public function show(string $id): string
    {
        $this->requireAuth();

        $bookmark = Bookmark::getWithRelations((int)$id);
        
        if (!$bookmark) {
            $this->flash('error', 'Bookmark not found');
            $this->redirect('/bookmarks');
            return ''; // Unreachable but satisfies return type
        }

        return $this->view('bookmarks/show', [
            'bookmark' => $bookmark,
            'title'    => $bookmark['title']
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(string $id): string
    {
        $this->requireAuth();

        $bookmark = Bookmark::getWithRelations((int)$id);
        
        if (!$bookmark) {
            $this->flash('error', 'Bookmark not found');
            $this->redirect('/bookmarks');
            return ''; // Unreachable but satisfies return type
        }

        $categories = Category::getFlatTree();
        $tags = Tag::getPopular();

        return $this->view('bookmarks/edit', [
            'bookmark'   => $bookmark,
            'categories' => $categories,
            'tags'       => $tags,
            'title'      => 'Edit Bookmark'
        ]);
    }

    /**
     * Update bookmark
     */
    public function update(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $bookmarkId = (int)$id;
        $bookmark = Bookmark::find($bookmarkId);
        
        if (!$bookmark) {
            $this->flash('error', 'Bookmark not found');
            $this->redirect('/bookmarks');
            return; // Unreachable but indicates intent
        }

        $url = Sanitizer::url($this->input('url'));
        
        if (!$url) {
            $this->flash('error', 'Invalid URL provided');
            $this->redirect("/bookmarks/{$id}/edit");
            return; // Unreachable but indicates intent
        }

        if (Bookmark::urlExists($url, $bookmarkId)) {
            $this->flash('error', 'This URL is already bookmarked');
            $this->redirect("/bookmarks/{$id}/edit");
            return; // Unreachable but indicates intent
        }

        Bookmark::update($bookmarkId, [
            'url'         => $url,
            'url_hash'    => Bookmark::hashUrl($url),
            'title'       => Sanitizer::string($this->input('title')),
            'description' => Sanitizer::string($this->input('description'), 1000),
            'meta_image'  => Sanitizer::url($this->input('meta_image')),
            'favicon'     => Sanitizer::url($this->input('favicon')),
            'category_id' => Sanitizer::int($this->input('category_id')),
            'is_favorite' => Sanitizer::bool($this->input('is_favorite')) ? 1 : 0,
            'is_archived' => Sanitizer::bool($this->input('is_archived')) ? 1 : 0
        ]);

        // Sync tags
        $tagNames = Sanitizer::tags($this->input('tags'));
        $tags = Tag::findOrCreateMultiple($tagNames);
        Bookmark::syncTags($bookmarkId, array_column($tags, 'id'));

        $this->flash('success', 'Bookmark updated successfully');
        $this->redirect('/bookmarks');
    }

    /**
     * Delete bookmark
     */
    public function destroy(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        Bookmark::delete((int)$id);
        
        if ($this->isAjax()) {
            $this->json(['success' => true]);
        }

        $this->flash('success', 'Bookmark deleted');
        $this->redirect('/bookmarks');
    }

    /**
     * Toggle favorite (AJAX)
     */
    public function toggleFavorite(string $id): void
    {
        $this->requireAuth();
        
        $isFavorite = Bookmark::toggleFavorite((int)$id);
        
        $this->json([
            'success'     => true,
            'is_favorite' => $isFavorite
        ]);
    }

    /**
     * Record visit and redirect
     */
    public function visit(string $id): void
    {
        $this->requireAuth();

        $bookmark = Bookmark::find((int)$id);
        
        if ($bookmark) {
            Bookmark::recordVisit((int)$id);
            $this->redirect($bookmark['url']);
        }

        $this->redirect('/bookmarks');
    }

    /**
     * Fetch metadata for a bookmark
     */
    public function fetchMeta(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $bookmark = Bookmark::find((int)$id);
        
        if (!$bookmark) {
            $this->flash('error', 'Bookmark not found');
            $this->redirect('/bookmarks');
        }

        try {
            // Use EnhancedMetaFetcher to get metadata
            $fetcher = new \App\Services\EnhancedMetaFetcher();
            $meta = $fetcher->fetch($bookmark['url']);

            // Check if this is a direct image URL (content-type is image/*)
            $contentType = $meta['content_type'] ?? '';
            $isDirectImage = str_starts_with($contentType, 'image/');
            
            if ($isDirectImage) {
                // Handle direct image URLs - cache the image itself
                $imageCache = new \App\Services\ImageCacheService();
                $cachedImage = $imageCache->cacheImageForOffload($bookmark['url']);
                
                // Extract filename from URL for title
                $urlPath = parse_url($bookmark['url'], PHP_URL_PATH);
                $filename = $urlPath ? basename($urlPath) : 'Image';
                $host = parse_url($bookmark['url'], PHP_URL_HOST) ?? '';
                
                $updateData = [
                    'meta_title'        => $filename,
                    'meta_description'  => 'Image from ' . $host,
                    'meta_site_name'    => $host,
                    'meta_type'         => 'image',
                    'meta_image'        => $cachedImage ?: null,
                    'http_status'       => $meta['http_status'] ?? null,
                    'content_type'      => $contentType,
                    'meta_fetch_error'  => null,
                    'meta_fetched_at'   => date('Y-m-d H:i:s')
                ];
                
                Bookmark::update((int)$id, $updateData);
                $this->flash('success', 'Image cached successfully!');
                
            } elseif ($meta && !isset($meta['error'])) {
                // Update bookmark with fetched metadata
                $updateData = [
                    'meta_title'        => $meta['title'] ?? null,
                    'meta_description'  => $meta['description'] ?? null,
                    'meta_site_name'    => $meta['site_name'] ?? null,
                    'meta_type'         => $meta['type'] ?? null,
                    'meta_author'       => $meta['author'] ?? null,
                    'meta_keywords'     => $meta['keywords'] ?? null,
                    'meta_locale'       => $meta['locale'] ?? null,
                    'meta_image'        => $meta['image'] ?? null,
                    'favicon'           => $meta['favicon'] ?? null,
                    'http_status'       => $meta['http_status'] ?? null,
                    'content_type'      => $meta['content_type'] ?? null,
                    'meta_fetch_error'  => null,
                    'meta_fetched_at'   => date('Y-m-d H:i:s')
                ];

                // Cache images before saving
                $imageCache = new \App\Services\ImageCacheService();
                
                // Cache meta image
                if (!empty($updateData['meta_image'])) {
                    try {
                        $cachedImage = $imageCache->getCachedUrl($updateData['meta_image'], 'image');
                        if ($cachedImage) {
                            $updateData['meta_image'] = $cachedImage;
                        }
                    } catch (\Exception $e) {
                        // Keep original URL if caching fails
                    }
                }
                
                // Cache favicon
                if (!empty($updateData['favicon'])) {
                    try {
                        $cachedFavicon = $imageCache->getCachedUrl($updateData['favicon'], 'favicon');
                        if ($cachedFavicon) {
                            $updateData['favicon'] = $cachedFavicon;
                        }
                    } catch (\Exception $e) {
                        // Keep original URL if caching fails
                    }
                }

                Bookmark::update((int)$id, $updateData);

                $this->flash('success', 'Metadata fetched successfully!');
            } else {
                $error = $meta['error'] ?? 'Unknown error';
                Bookmark::update((int)$id, [
                    'meta_fetch_error' => $error,
                    'meta_fetched_at'  => date('Y-m-d H:i:s')
                ]);
                $this->flash('warning', 'Could not fetch metadata: ' . $error);
            }
        } catch (\Exception $e) {
            Bookmark::update((int)$id, [
                'meta_fetch_error' => $e->getMessage(),
                'meta_fetched_at'  => date('Y-m-d H:i:s')
            ]);
            $this->flash('error', 'Error fetching metadata: ' . $e->getMessage());
        }

        $this->redirect('/bookmarks/' . $id);
    }
}
