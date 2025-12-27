<?php
/**
 * Import/Export Controller
 * Handles bookmark import and export operations
 * 
 * @package BookmarkManager\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ImportExportService;
use App\Helpers\Sanitizer;

class ImportExportController extends BaseController
{
    private ImportExportService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ImportExportService();
    }

    /**
     * Show import form
     */
    public function showImport(): string
    {
        $this->requireAuth();

        return $this->view('import/index', [
            'title' => 'Import Bookmarks'
        ]);
    }

    /**
     * Process file import
     */
    public function import(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Please select a valid file');
            $this->redirect('/import');
        }

        $file = $_FILES['file'];
        
        // Check size
        if ($file['size'] > IMPORT_MAX_SIZE) {
            $this->flash('error', 'File too large (max 10MB)');
            $this->redirect('/import');
        }

        $content = file_get_contents($file['tmp_name']);
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $result = match($extension) {
            'json' => $this->service->importJson($content),
            'html', 'htm' => $this->service->importHtml($content),
            'csv' => $this->service->importCsv($content),
            default => ['success' => false, 'error' => 'Unsupported file format']
        };

        if ($result['success']) {
            $message = "Imported {$result['imported']} bookmarks";
            if ($result['skipped'] > 0) {
                $message .= " ({$result['skipped']} skipped)";
            }
            $this->flash('success', $message);
        } else {
            $this->flash('error', $result['error'] ?? 'Import failed');
        }

        $this->redirect('/import');
    }

    /**
     * Export bookmarks
     */
    public function export(): never
    {
        $this->requireAuth();

        $format = Sanitizer::string($this->input('format') ?? 'json');

        match($format) {
            'json' => $this->service->exportJson(),
            'csv' => $this->service->exportCsv(),
            'html' => $this->service->exportHtml(),
            default => $this->service->exportJson()
        };

        exit;
    }
}
