<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentVersionRequest;
use App\Models\Document;
use App\Services\DocumentVersionPreviewService;
use App\Services\DocumentVersionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Support\Str;

class DocumentVersionApiController extends Controller
{
    protected $documentVersionService;
    protected $previewService;

    public function __construct(DocumentVersionService $documentVersionService, DocumentVersionPreviewService $previewService)
    {
        $this->documentVersionService = $documentVersionService;
        $this->previewService = $previewService;
    }

    /**
     * Hien thi danh sach phien ban tai lieu co phan trang
     */
    public function index($id)
    {
        $versions = $this->documentVersionService->getDocumentVersionsHasPaginated($id);

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
    public function upload(UploadDocumentVersionRequest $request, $id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Tài liệu không tồn tại'
            ], 404);
        }

        if (!$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'Không có tệp nào được tải lên.'
            ], 400);
        }

        // if (auth()->id() !== $document->user_id) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Bạn không có quyền upload phiên bản mới'
        //     ], 403);
        // }

        DB::beginTransaction();
        try {
            // Luu file 
            $file = $request->file('file');
            // Tao ten file an toan
            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('docs', $fileName, 'public');

            // Xac dinh version_number tiep theo
            $lastVersion = $document->versions()->orderByDesc('version_number')->first();
            $newVersionNumber = $lastVersion ? $lastVersion->version_number + 1 : 1;

            // Tao moi
            $version = $document->versions()->create([
                'version_number' => $newVersionNumber,
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'change_note' => $request->change_note,
                'is_current_version' => true,
                'user_id' => auth()->id() ?? 1,
            ]);

            // Cap nhat cac phien ban khac ve cu
            $document->versions()
                ->where('version_id', '!=', $version->version_id)
                ->update(['is_current_version' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tải lên phiên bản mới thành công',
                'data' => $version
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            // Xoa file neu da luu
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi tải lên: ' . $e->getMessage()], 500);
        }
    }
}
