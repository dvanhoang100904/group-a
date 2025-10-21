<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentVersionRequest;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Throwable;
use Illuminate\Support\Str;

class DocumentVersionApiController extends Controller
{
    const PER_PAGE = 3;

    /**
     * Hien thi danh sach phien ban tai lieu co phan trang
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

        $versions = $document->versions()
            ->with('user')
            ->latest()
            ->paginate(self::PER_PAGE);

        return response()->json([
            'success' => true,
            'data' => $versions,
            'message' => 'Danh sách phiên bản'
        ]);
    }

    /**
     * Hien thi preview file
     */
    public function preview($id)
    {
        $version = DocumentVersion::with('previews')->find($id);

        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên bản không tồn tại'
            ], 404);
        }

        // Lấy preview mới nhất còn hạn
        $preview = $version->previews()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->first();

        if ($preview && Storage::disk('public')->exists($preview->preview_path)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'preview_path' => Storage::url($preview->preview_path),
                    'file_name' => basename($version->file_path),
                    'expires_at' => $preview->expires_at
                ]
            ]);
        }

        // Chuyển DOCX sang PDF nếu cần
        $filePath = storage_path('app/public/' . $version->file_path);
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        if ($ext === 'docx') {
            $pdfPreviewPath = 'previews/' . $version->version_id . '.pdf';
            $pdfFullPath = storage_path('app/public/' . $pdfPreviewPath);

            if (!file_exists($pdfFullPath)) {
                if (!file_exists(storage_path('app/public/previews'))) {
                    mkdir(storage_path('app/public/previews'), 0755, true);
                }

                try {
                    $phpWord = IOFactory::load($filePath);
                    $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
                    $pdfWriter->save($pdfFullPath);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể tạo preview PDF: ' . $e->getMessage()
                    ]);
                }
            }

            $previewUrl = Storage::url($pdfPreviewPath);
        } else {
            // File PDF hoặc khác
            $previewUrl = Storage::url($version->file_path);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'preview_path' => $previewUrl,
                'file_name' => basename($version->file_path)
            ]
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
