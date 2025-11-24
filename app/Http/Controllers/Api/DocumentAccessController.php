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
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     *  Xóa document
     */
    public function deleteDocument($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            Log::info('=== DOCUMENT DELETE START ===', ['document_id' => $id]);

            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $document = Document::find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài liệu không tồn tại'
                ], 404);
            }

            // Kiểm tra quyền - sử dụng logic tương tự các method khác
            $currentUserId = auth()->id();
            if ($document->user_id !== $currentUserId && !auth()->user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa tài liệu này'
                ], 403);
            }

            $documentName = $document->title;

            // Xóa các versions
            $versions = DocumentVersion::where('document_id', $id)->get();

            foreach ($versions as $version) {
                if ($version->file_name) {
                    $filePath = base_path('app/Public_UploadFile/' . $version->file_name);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                        Log::info('File deleted:', ['file' => $version->file_name]);
                    }
                }
                $version->delete();
            }

            // Xóa document
            $document->delete();

            DB::commit();

            Log::info('=== DOCUMENT DELETE SUCCESS ===', ['document_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Xóa tài liệu "' . $documentName . '" thành công!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('DOCUMENT DELETE ERROR: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống khi xóa tài liệu: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Hien thi danh sach truy cap quyen co phan trang
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
                'message' => 'Bạn không có quyền truy cập tài liệu này. Vui lòng thử lại.'
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
            'message' => $data->isEmpty() ? 'Chưa có quyền truy cập nào. Vui lòng thử lại.' : 'Danh sách quyền truy cập tải thành công.',
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
     * Them moi quyen truy cap tai lieu 
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
                'message' => 'Không thể thêm quyền truy cập. Vui lòng thử lại.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quyền truy cập đã được thêm.',
            'data' => $access
        ]);
    }

    /**
     * Cap nhat quyen truy cap tai lieu
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
                'message' => 'Không thể cập nhật quyền truy cập. Vui lòng thử lại.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quyền truy cập đã cập nhật thành công.',
            'data' => $access
        ]);
    }

    /** 
     * Xoa quyen truy cap tai lieu
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
                'message' => 'Không thể xóa quyền truy cập. Vui lòng thử lại.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quyền truy cập đã xóa thành công.',
            'data' => $access,
        ]);
    }
}
