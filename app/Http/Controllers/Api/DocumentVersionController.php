<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentVersion\DocumentVersionFilterRequest;
use App\Services\DocumentVersion\DocumentVersionPreviewService;
use App\Services\DocumentVersion\DocumentVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentVersionController extends Controller
{
    protected DocumentVersionService $documentVersionService;
    protected DocumentVersionPreviewService $previewService;

    public function __construct(DocumentVersionService $documentVersionService, DocumentVersionPreviewService $previewService)
    {
        $this->documentVersionService = $documentVersionService;
        $this->previewService = $previewService;
    }

    /**
     * Hien thi danh sach phien ban tai lieu co phan trang
     */
    public function index(DocumentVersionFilterRequest $request, int $documentId): JsonResponse
    {
        $document = $this->documentVersionService->getDocument($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        $filters = $request->only([
            'keyword',
            'user_id',
            'status',
            'from_date',
            'to_date'
        ]);

        $data = $this->documentVersionService->getDocumentVersionsHasPaginated($documentId, $filters);

        if ($data) {
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Danh sách phiên bản tải thành công'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tài liệu không tồn tại'
        ], 404);
    }

    /**
     * Hiển thị danh sách nguoi dung
     */
    public function uploaders($documentId)
    {
        $users = $this->documentVersionService->getUploaders($documentId);

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Danh sách người upload của tài liệu'
        ]);
    }

    /** 
     * Xem chi tiet phien ban tai lieu
     */
    public function show(int $documentId, int $versionId)
    {
        $document = $this->documentVersionService->getDocument($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        $data = $this->documentVersionService->getDocumentVersion($documentId, $versionId);

        if ($data) {
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'phiên bản tải thành công'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Phiên bản không tồn tại'
        ], 404);
    }

    /**
     * Hien thi preview file
     */
    public function preview(int $documentId, int $versionId)
    {
        $document = $this->documentVersionService->getDocument($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        $data = $this->previewService->getOrGeneratePreview($versionId, $documentId);

        if ($data) {
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'tạo preview thành công'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Phiên bản không tồn tại'
        ], 404);
    }
}
