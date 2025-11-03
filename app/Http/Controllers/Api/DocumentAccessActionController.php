<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentAccess\DocumentAccessAddRequest;
use App\Http\Requests\DocumentAccess\DocumentAccessUpdateRequest;
use App\Models\Document;
use App\Services\DocumentAccess\DocumentAccessAddService;
use App\Services\DocumentAccess\DocumentAccessDeleteService;
use App\Services\DocumentAccess\DocumentAccessUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentAccessActionController extends Controller
{
    protected DocumentAccessAddService $addService;
    protected DocumentAccessUpdateService $updateService;
    protected DocumentAccessDeleteService $deleteService;

    public function __construct(DocumentAccessAddService $addService, DocumentAccessUpdateService $updateService, DocumentAccessDeleteService $deleteService)
    {
        $this->addService = $addService;
        $this->updateService = $updateService;
        $this->deleteService = $deleteService;
    }

    /**
     * Them moi quyen chia se tai lieu 
     */
    public function store(DocumentAccessAddRequest $request, int $documentId): JsonResponse
    {
        $document = Document::find($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        $data = $this->addService->addAccess(
            $request->validated(),
            $documentId,
            auth()->id() ?? 1
        );

        if ($data) {
            return response()->json([
                'success' => true,
                'message' => 'Thêm quyền chia sẻ thành công.',
                'data' => $data
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không thể thêm quyền chia sẻ (có thể người dùng hoặc vai trò đã được cấp).',
        ], 500);
    }

    /**
     * Cap nhat quyen chia se tai lieu
     */
    public function update(DocumentAccessUpdateRequest $request, int $documentId, int $accessId): JsonResponse
    {
        $document = Document::find($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        $data = $this->updateService->updateAccess(
            $documentId,
            $accessId,
            $request->validated(),
            auth()->id() ?? 1
        );

        if ($data) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật quyền chia sẻ thành công.',
                'data' => $data
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không thể cập nhật quyền chia sẻ (bản ghi có thể không tồn tại hoặc lỗi hệ thống).',
        ], 500);
    }

    /** 
     * Xoa quyen chia se tai lieu
     */
    public function destroy(int $documentId, int $accessId): JsonResponse
    {
        $document = Document::find($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        $data = $this->deleteService->deleteAccess($documentId, $accessId);

        if ($data) {
            return response()->json([
                'success' => true,
                'message' => 'Xóa quyền chia sẻ thành công.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không thể xóa quyền chia sẻ.',
        ], 500);
    }
}
