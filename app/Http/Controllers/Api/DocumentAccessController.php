<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentAccess\DocumentAccessAddRequest;
use App\Http\Requests\DocumentAccess\DocumentAccessUpdateRequest;
use App\Services\DocumentAccess\DocumentAccessAddService;
use App\Services\DocumentAccess\DocumentAccessDeleteService;
use App\Services\DocumentAccess\DocumentAccessService;
use App\Services\DocumentAccess\DocumentAccessUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentAccessController extends Controller
{
    protected DocumentAccessService $documentAccessService;
    protected DocumentAccessAddService $addService;
    protected DocumentAccessUpdateService $updateService;
    protected DocumentAccessDeleteService $deleteService;

    public function __construct(DocumentAccessService $documentAccessService, DocumentAccessAddService $addService, DocumentAccessUpdateService $updateService, DocumentAccessDeleteService $deleteService)
    {
        $this->documentAccessService = $documentAccessService;
        $this->addService = $addService;
        $this->updateService = $updateService;
        $this->deleteService = $deleteService;
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
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        $currentUserId = auth()->id();
        if ($document->uploaded_by !== $currentUserId && !auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền chia sẻ tài liệu này. Vui lòng thử lại.'
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
            'message' => $data->isEmpty() ? 'Chưa có quyền chia sẻ nào. Vui lòng thử lại.' : 'Danh sách quyền chia sẻ tải thành công.',
        ]);
    }

    public function listUsers(int $documentId)
    {
        $document = $this->documentAccessService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        $users = $this->documentAccessService->getUsersForAccess($documentId);

        if (!$users) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa có người dùng nào. Vui lòng thử lại.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Danh sách người dùng tải thành công.'
        ]);
    }

    public function listRoles(int $documentId)
    {
        $document = $this->documentAccessService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại'
            ]);
        }

        $roles = $this->documentAccessService->getRolesForAccess($documentId);

        if (!$roles) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa có vai trò nào.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $roles,
            'message' => 'Danh sách vai trò tải thành công.'
        ]);
    }

    /**
     * Them moi quyen chia se tai lieu 
     */
    public function store(DocumentAccessAddRequest $request, int $documentId): JsonResponse
    {
        $document = $this->documentAccessService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        $data = $request->only([
            'granted_to_type',
            'granted_to_user_id',
            'granted_to_role_id',
            'no_expiry',
            'expiration_date',
            'can_view',
            'can_edit',
            'can_delete',
            'can_upload',
            'can_download',
            'can_share',
        ]);

        $access = $this->addService->addAccess($data, $documentId, auth()->id() ?? 1);

        if (!$access) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể thêm quyền chia sẻ. Vui lòng thử lại.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quyền chia sẻ đã được thêm.',
            'data' => $access
        ]);
    }

    /**
     * Cap nhat quyen chia se tai lieu
     */
    public function update(DocumentAccessUpdateRequest $request, int $documentId, int $accessId): JsonResponse
    {
        $document = $this->documentAccessService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại'
            ]);
        }

        $data = $request->only([
            'no_expiry',
            'expiration_date',
            'can_view',
            'can_edit',
            'can_delete',
            'can_upload',
            'can_download',
            'can_share',
        ]);

        $access = $this->updateService->updateAccess(
            $documentId,
            $accessId,
            $data,
        );

        if (!$access) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật quyền chia sẻ. Vui lòng thử lại.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quyền chia sẻ đã cập nhật thành công.',
            'data' => $access
        ]);
    }

    /** 
     * Xoa quyen chia se tai lieu
     */
    public function destroy(int $documentId, int $accessId): JsonResponse
    {
        $document = $this->documentAccessService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại'
            ]);
        }

        $access = $this->deleteService->deleteAccess($documentId, $accessId);

        if (!$access) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa quyền chia sẻ. Vui lòng thử lại.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quyền chia sẻ đã xóa thành công.',
            'data' => $access,
        ]);
    }
}
