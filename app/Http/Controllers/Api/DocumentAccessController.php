<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\DocumentAccess\DocumentAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentAccessController extends Controller
{
    protected DocumentAccessService $documentAccessService;

    public function __construct(DocumentAccessService $documentAccessService)
    {
        $this->documentAccessService = $documentAccessService;
    }

    /**
     * Hien thi danh sach chia se quyen co phan trang
     */
    public function index(int $documentId): JsonResponse
    {
        $document = $this->documentAccessService->getDocument($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        // $currentUserId = auth()->id();
        // if ($document->uploaded_by !== $currentUserId && !auth()->user()->is_admin) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Bạn không có quyền chia sẻ tài liệu này'
        //     ], 403);
        // }

        $data = $this->documentAccessService->getDocumentAccessesHasPaginated($documentId);

        if ($data) {
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Danh sách quyền chia sẻ thành công'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không thể load danh sách.',
        ], 500);
    }

    public function users(int $documentId)
    {
        $document = $this->documentAccessService->getDocument($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        $data = $this->documentAccessService->getUsersForAccess($documentId);

        if ($data) {
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Danh sách người upload của tài liệu'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không thể tải danh sách.',
        ], 500);
    }

    public function roles(int $documentId)
    {
        $document = $this->documentAccessService->getDocument($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        $data = $this->documentAccessService->getRolesForAccess($documentId);

        if ($data) {
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Danh sách vai trò của tài liệu'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không thể tải danh sách.',
        ], 500);
    }
}
