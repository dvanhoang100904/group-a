<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentVersionRequest;
use App\Models\Document;
use App\Services\DocumentVersionPreviewService;
use App\Services\DocumentVersionService;
use App\Services\DocumentVersionUploadService;
use Illuminate\Http\Request;
use Throwable;

class DocumentVersionApiController extends Controller
{
    protected $documentVersionService;
    protected $previewService;
    protected $uploadService;

    public function __construct(DocumentVersionService $documentVersionService, DocumentVersionPreviewService $previewService, DocumentVersionUploadService $uploadService)
    {
        $this->documentVersionService = $documentVersionService;
        $this->previewService = $previewService;
        $this->uploadService = $uploadService;
    }

    /**
     * Hien thi danh sach phien ban tai lieu co phan trang
     */
    public function index(Request $request, $id)
    {
        $filters = $request->only(['keyword', 'user_id', 'status', 'from_date', 'to_date']);

        $versions = $this->documentVersionService->getDocumentVersionsHasPaginated($id, $filters);

        if (!$versions) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $versions,
            'message' => 'Danh sách phiên bản tải thành công'
        ]);
    }

    /**
     * Hien thi preview file
     */
    public function preview($documentId, $versionId)
    {
        $data = $this->previewService->getOrGeneratePreview($versionId, $documentId);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo preview hoặc phiên bản không tồn tại',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Upload tai lieu phen ban moi
     */
    public function store(UploadDocumentVersionRequest $request, $id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        try {
            $file = $request->file('file');
            $changeNote = $request->change_note;
            $version = $this->uploadService->upload($document, $file, $changeNote);

            return response()->json([
                'success' => true,
                'message' => 'Tải lên phiên bản mới thành công',
                'data' => $version
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
