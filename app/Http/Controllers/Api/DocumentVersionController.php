<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentVersion\DocumentVersionFilterRequest;
use App\Http\Requests\DocumentVersion\UploadDocumentVersionRequest;
use App\Services\DocumentVersion\DocumentVersionPreviewService;
use App\Services\DocumentVersion\DocumentVersionService;
use App\Services\DocumentVersion\DocumentVersionUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentVersionController extends Controller
{
    protected DocumentVersionService $documentVersionService;
    protected DocumentVersionPreviewService $previewService;
    protected DocumentVersionUploadService $uploadService;

    public function __construct(DocumentVersionService $documentVersionService, DocumentVersionPreviewService $previewService, DocumentVersionUploadService $uploadService)
    {
        $this->documentVersionService = $documentVersionService;
        $this->previewService = $previewService;
        $this->uploadService = $uploadService;
    }

    /**
     * Hien thi danh sach phien ban tai lieu co loc tim kiem phan trang
     */
    public function index(DocumentVersionFilterRequest $request, int $documentId): JsonResponse
    {
        $document = $this->documentVersionService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        $filters = $request->only([
            'keyword',
            'user_id',
            'status',
            'from_date',
            'to_date'
        ]);

        $data = $this->documentVersionService->getDocumentVersionsHasPaginated($documentId, $filters);

        return response()->json([
            'success' => true,
            'data' => $data->items(),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
            ],
            'message' => $data->isEmpty() ? 'Chưa có phiên bản nào' : 'Danh sách phiên bản tải thành công',
        ]);
    }

    /**
     * Hien thi danh sach nguoi dung de loc phien ban tai lieu
     */
    public function listUsers($documentId)
    {
        $users = $this->documentVersionService->getUsersForDocumentVersion($documentId);

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Danh sách người upload của tài liệu'
        ]);
    }

    /** 
     * Xem chi tiet phien ban tai lieu
     */
    public function show(int $documentId, int $versionId)
    {
        $document = $this->documentVersionService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        $data = $this->documentVersionService->getDocumentVersion($documentId, $versionId);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên bản tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Thông tin chi tiết phiên bản tài liệu'
        ]);
    }

    /**
     * Hien thi preview file
     */
    public function preview(int $documentId, int $versionId)
    {
        $document = $this->documentVersionService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        $data = $this->previewService->getOrGeneratePreview($versionId, $documentId);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên bản tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Mở preview phiên bản tài liệu'
        ]);
    }

    /**
     * Upload tai lieu phen ban moi
     */
    public function store(UploadDocumentVersionRequest $request, $documentId)
    {
        $document = $this->documentVersionService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        $file = $request->file('file');

        $data = $request->only([
            'change_note'
        ]);

        $version = $this->uploadService->uploadVersion($documentId, $file, $data);

        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại .'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tải lên phiên bản mới thành công',
            'data' => $version
        ]);
    }

    /**
     * Tai xuong phien ban tai lieu
     */
    public function download($documentId, $versionId)
    {
        $document = $this->documentVersionService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        $version = $this->documentVersionService->downloadVersion($documentId, $versionId);

        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại.'
            ]);
        }

        return $version;
    }

    /**
     * Khoi phuc phien ban tai lieu
     */
    public function restore($documentId, $versionId)
    {
        $document = $this->documentVersionService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        return $this->documentVersionService->restoreVersion($documentId, $versionId);
    }

    /**
     * Xoa phien ban tai lieu
     */
    public function destroy($documentId, $versionId)
    {
        $document = $this->documentVersionService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        return $this->documentVersionService->deleteVersion($documentId, $versionId);
    }
}
