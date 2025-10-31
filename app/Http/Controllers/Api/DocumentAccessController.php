<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}
