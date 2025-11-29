<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentVersion\DocumentVersionCompareRequest;
use App\Http\Requests\DocumentVersion\DocumentVersionFilterRequest;
use App\Http\Requests\DocumentVersion\DocumentVersionUploadRequest;
use App\Services\DocumentVersion\DocumentVersionCompareService;
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
    protected DocumentVersionCompareService $compareService;

    public function __construct(DocumentVersionService $documentVersionService, DocumentVersionPreviewService $previewService, DocumentVersionUploadService $uploadService, DocumentVersionCompareService $compareService)
    {
        $this->documentVersionService = $documentVersionService;
        $this->previewService = $previewService;
        $this->uploadService = $uploadService;
        $this->compareService = $compareService;
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
            'message' => $data->isEmpty() ? 'Chưa có phiên bản nào. Vui lòng thử lại.' : 'Danh sách phiên bản tải thành công',
        ]);
    }

    /**
     * Hien thi danh sach nguoi dung de loc phien ban tai lieu
     */
    public function listUsers($documentId)
    {
        $document = $this->documentVersionService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại.'
            ]);
        }

        $users = $this->documentVersionService->getUsersForDocumentVersion($documentId);

        if (!$users) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa có người dùng nào.'
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Danh sách người dùng tải thành công.'
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
                'message' => 'Không thể xem chi tiết phiên bản. Vui lòng thử lại.'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Chi tiết phiên bản đã xem thành công.'
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
                'message' => 'Không thể xem preview file. Vui lòng thử lại.'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Preview file phiên bản đã xem thành công.'
        ]);
    }

    /**
     * Upload tai lieu phen ban moi
     */
    public function store(DocumentVersionUploadRequest $request, $documentId)
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
                'message' => 'Không thể tải lên phiên bản mới. Vui lòng thử lại.'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Phiên bản mới đã tải lên thành công.',
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
                'message' => 'Không thể tải xuống phiên bản. Vui lòng thử lại.'
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

        $version = $this->documentVersionService->restoreVersion($documentId, $versionId);

        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể khôi phục phiên bản. Vui lòng thử lại.'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Phiên bản đã khôi phục thành công.',
            'data' => $version
        ]);
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

        $version = $this->documentVersionService->deleteVersion($documentId, $versionId);

        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa phiên bản. Vui lòng thử lại.'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Phiên bản đã xóa thành công.',
            'data' => $version
        ]);
    }

    /**
     * So sanh hai phien ban tai lieu
     */
    public function compare(DocumentVersionCompareRequest $request, $documentId)
    {
        $document = $this->documentVersionService->getDocumentById($documentId);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại. Vui lòng thử lại'
            ]);
        }

        $versionA = $request->query('version_a');
        $versionB = $request->query('version_b');

        if (!$versionA || !$versionB) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn cả hai phiên bản'
            ]);
        }

        $versions = $this->compareService->compareVersions($documentId, $versionA, $versionB);

        if (!$versions) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể so sánh phiên bản. Vui lòng thử lại.'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $versions,
            'message' => 'Phiên bản đã so sánh thành công.'
        ]);
    }
}
