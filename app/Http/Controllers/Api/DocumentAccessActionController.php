<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentAccess\AddDocumentAccessRequest;
use App\Http\Requests\DocumentAccess\UpdateDocumentAccessRequest;
use App\Models\Document;
use App\Services\DocumentAccess\DocumentAccessAddService;
use App\Services\DocumentAccess\DocumentAccessUpdateService;
use Illuminate\Http\Request;

class DocumentAccessActionController extends Controller
{
    protected $addService;
    protected $updateService;

    public function __construct(DocumentAccessAddService $addService, DocumentAccessUpdateService $updateService)
    {
        $this->addService = $addService;
        $this->updateService = $updateService;
    }

    /**
     * Them moi quen 
     */
    public function store(AddDocumentAccessRequest $request, $documentId)
    {
        $document = Document::find($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        try {
            $access = $this->addService->addAccess(
                $request->validated(),
                $document->document_id,
                auth()->id() ?? 1
            );;

            return response()->json([
                'success' => true,
                'message' => 'Thêm quyền thành công!',
                'data' => $access
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể thêm quyền: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateDocumentAccessRequest $request, $documentId, $accessId)
    {
        $document = Document::find($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        try {
            $access = $this->updateService->updateAccess(
                $document->document_id,
                $accessId,
                $request->validated(),
                auth()->id() ?? 1
            );

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật quyền thành công!',
                'data' => $access
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật quyền: ' . $e->getMessage(),
            ], 500);
        }
    }
}
