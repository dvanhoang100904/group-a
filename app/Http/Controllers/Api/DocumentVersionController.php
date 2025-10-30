<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentVersion\DocumentVersionPreviewService;
use App\Services\DocumentVersion\DocumentVersionService;
use Illuminate\Http\Request;

class DocumentVersionController extends Controller
{
    protected $documentVersionService;
    protected $previewService;

    public function __construct(DocumentVersionService $documentVersionService, DocumentVersionPreviewService $previewService)
    {
        $this->documentVersionService = $documentVersionService;
        $this->previewService = $previewService;
    }

    /**
     * Hien thi danh sach phien ban tai lieu co phan trang
     */
    public function index(Request $request, $id)
    {
        $filters = $request->only(['keyword', 'user_id', 'status', 'from_date', 'to_date']);

        $versions = $this->documentVersionService->getDocumentVersionsHasPaginated($id, $filters);

        if (!$versions) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $versions,
            'message' => 'Danh sách phiên bản tải thành công'
        ]);
    }

    /** 
     * Xem chi tiet phien ban tai lieu
     */
    public function show($documentId, $versionId)
    {
        $version = $this->documentVersionService->getDocumentVersion($documentId, $versionId);

        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên bản không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $version
        ]);
    }

    /**
     * Hien thi preview file
     */
    public function preview($documentId, $versionId)
    {
        $data = $this->previewService->getOrGeneratePreview($versionId, $documentId);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo preview hoặc phiên bản không tồn tại',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
