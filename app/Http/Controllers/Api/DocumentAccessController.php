<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $document = $this->documentAccessService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại'
            ]);
        }

        $currentUserId = auth()->id();
        if ($document->uploaded_by !== $currentUserId && !auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền chia sẻ tài liệu này'
            ]);
        }

        $data = $this->documentAccessService->getDocumentAccessesHasPaginated($documentId);

        return response()->json([
            'success' => true,
            'data' => $data->items(),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
            ],
            'message' => $data->isEmpty() ? 'Chưa có quyền chia sẻ nào' : 'Danh sách quyền chia sẻ tải thành công',
        ]);
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

        $users = $this->documentAccessService->getUsersForAccess($documentId);

        if (!$users) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra vui lòng thử lại.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Danh sách người upload của tài liệu'
        ]);
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
