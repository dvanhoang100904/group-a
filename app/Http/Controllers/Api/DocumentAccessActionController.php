<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentAccess\AddDocumentAccessRequest;
use App\Models\Document;
use App\Services\DocumentAccess\DocumentAccessAddService;
use Illuminate\Http\Request;

class DocumentAccessActionController extends Controller
{
    protected $addService;

    public function __construct(DocumentAccessAddService $addService)
    {
        $this->addService = $addService;
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
}
