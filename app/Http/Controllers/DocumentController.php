<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // Hiển thị blade
    public function index()
    {
        return view('documents.My_Documents.My_Documents');
    }

    // API trả JSON toàn bộ danh sách documents
    public function getDocuments(Request $request)
    {
        // Lấy số lượng mỗi trang (mặc định 20)
        $perPage = $request->input('per_page', 20);

        // Query documents kèm quan hệ
        $query = Document::with(['user', 'type', 'subject'])
            ->orderByDesc('created_at');

        // Thực hiện phân trang
        $documents = $query->paginate($perPage);

        // Thêm size và file_path
        foreach ($documents as $doc) {
            $path = base_path('app/Public_UploadFile/' . ($doc->file_name ?? ''));

            $doc->size = file_exists($path) ? filesize($path) : null;
            $doc->file_path = file_exists($path)
                ? asset('app/Public_UploadFile/' . $doc->file_name)
                : null;
        }

        return response()->json([
            'success' => true,
            'data' => $documents->items(),         // 20 tài liệu của trang hiện tại
            'current_page' => $documents->currentPage(),
            'last_page' => $documents->lastPage(),
            'total' => $documents->total(),
            'per_page' => $documents->perPage(),
        ]);
    }
    public function downloadVersion($versionId)
    {
        $version = DocumentVersion::find($versionId);

        if (!$version) {
            abort(404, 'Phiên bản tài liệu không tồn tại.');
        }

        // Kiểm tra file_path có hợp lệ không
        if (!$version->file_path || trim($version->file_path) === '') {
            abort(500, 'File path rỗng hoặc không hợp lệ trong database.');
        }

        // Đường dẫn trong disk public
        $filePath = $version->file_path;

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'Không tìm thấy file trên hệ thống.');
        }

        // Tên file trả về cho người dùng
        $downloadName = basename($filePath);

        return Storage::disk('public')->download($filePath, $downloadName);
    }
}
