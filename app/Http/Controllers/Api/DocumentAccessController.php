<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\DocumentAccess\DocumentAccessService;
use Illuminate\Http\Request;

class DocumentAccessController extends Controller
{
    protected $documentAccessService;

    public function __construct(DocumentAccessService $documentAccessService)
    {
        $this->documentAccessService = $documentAccessService;
    }

    /**
     * Hien thi danh sach chia se quyen co phan trang
     */
    public function index($id)
    {
        $document = Document::find($id);

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

        $accesses = $this->documentAccessService->getDocumentAccessesHasPaginated($id);

        if (!$accesses) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $accesses,
            'message' => 'Danh sách quyền chia sẻ thành công'
        ]);
    }

    public function users($documentId)
    {

        $users = $this->documentAccessService->getUsersForAccess($documentId);

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Danh sách người upload của tài liệu'
        ]);
    }

    public function roles($documentId)
    {
        $roles = $this->documentAccessService->getRolesForAccess($documentId);

        return response()->json([
            'success' => true,
            'data' => $roles,
            'message' => 'Danh sách vai trò của tài liệu'
        ]);
    }
}
