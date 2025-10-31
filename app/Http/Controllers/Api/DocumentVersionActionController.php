<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentVersion\UploadDocumentVersionRequest;
use App\Models\Document;
use App\Services\DocumentVersion\DocumentVersionDeleteService;
use App\Services\DocumentVersion\DocumentVersionDownloadService;
use App\Services\DocumentVersion\DocumentVersionRestoreService;
use App\Services\DocumentVersion\DocumentVersionUploadService;
use Illuminate\Http\Request;

class DocumentVersionActionController extends Controller
{
    protected $uploadService;
    protected $downloadService;
    protected $restoreService;
    protected $deleteService;

    public function __construct(DocumentVersionUploadService $uploadService, DocumentVersionDownloadService $downloadService, DocumentVersionRestoreService $restoreService, DocumentVersionDeleteService $deleteService)
    {
        $this->uploadService = $uploadService;
        $this->downloadService = $downloadService;
        $this->restoreService = $restoreService;
        $this->deleteService = $deleteService;
    }

    /**
     * Upload tai lieu phen ban moi
     */
    public function store(UploadDocumentVersionRequest $request, $id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        try {
            $file = $request->file('file');
            $changeNote = $request->change_note;
            $version = $this->uploadService->upload($document, $file, $changeNote);

            return response()->json([
                'success' => true,
                'message' => 'Tải lên phiên bản mới thành công',
                'data' => $version
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
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
