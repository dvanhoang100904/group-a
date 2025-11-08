<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentVersion\DocumentVersionDeleteService;
use App\Services\DocumentVersion\DocumentVersionDownloadService;
use App\Services\DocumentVersion\DocumentVersionRestoreService;
use Illuminate\Http\Request;

class DocumentVersionActionController extends Controller
{
    protected $downloadService;
    protected $restoreService;
    protected $deleteService;

    public function __construct(DocumentVersionDownloadService $downloadService, DocumentVersionRestoreService $restoreService, DocumentVersionDeleteService $deleteService)
    {
        $this->downloadService = $downloadService;
        $this->restoreService = $restoreService;
        $this->deleteService = $deleteService;
    }

    /**
     * Tai xuong phien ban tai lieu
     */
    public function download($documentId, $versionId)
    {
        return $this->downloadService->downloadVersion($documentId, $versionId);
    }

    /**
     * Khoi phuc phien ban tai lieu
     */
    public function restore($documentId, $versionId)
    {
        return $this->restoreService->restoreVersion($documentId, $versionId);
    }

    /**
     * Xoa phien ban tai lieu
     */
    public function destroy($documentId, $versionId)
    {
        return $this->deleteService->deleteVersion($documentId, $versionId);
    }
}
