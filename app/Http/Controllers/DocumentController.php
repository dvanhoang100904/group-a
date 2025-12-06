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
        $perPage = $request->input('per_page', 20);

        $query = Document::with(['user', 'type', 'subject'])
            ->orderByDesc('created_at'); // mặc định mới nhất

        /*
    |--------------------------------------------------------------------------
    | 1) Lọc theo Type
    |--------------------------------------------------------------------------
    */
        if ($request->filled('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        /*
    |--------------------------------------------------------------------------
    | 2) Lọc theo Subject
    |--------------------------------------------------------------------------
    */
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        /*
    |--------------------------------------------------------------------------
    | 3) Lọc theo Tên tác giả (user.name)
    |--------------------------------------------------------------------------
    */
        if ($request->filled('author_name')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->author_name . '%');
            });
        }

        /*
    |--------------------------------------------------------------------------
    | 4) Tìm kiếm theo Title
    |--------------------------------------------------------------------------
    */
        if ($request->filled('search')) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }

        /*
    |--------------------------------------------------------------------------
    | 5) Lọc theo khoảng thời gian created_at
    | from_date, to_date format: YYYY-MM-DD
    |--------------------------------------------------------------------------
    */
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        /*
    |--------------------------------------------------------------------------
    | 6) Sắp xếp theo tên tác giả A->Z hoặc Z->A
    |--------------------------------------------------------------------------
    | sort = author_asc | author_desc
    |--------------------------------------------------------------------------
    */
        if ($request->sort === 'author_asc') {
            $query->join('users', 'users.user_id', '=', 'documents.user_id')
                ->orderBy('users.name', 'ASC')
                ->select('documents.*'); // tránh override select
        }

        if ($request->sort === 'author_desc') {
            $query->join('users', 'users.user_id', '=', 'documents.user_id')
                ->orderBy('users.name', 'DESC')
                ->select('documents.*');
        }

        /*
    |--------------------------------------------------------------------------
    | 7) Lấy dữ liệu với paginate
    |--------------------------------------------------------------------------
    */
        $documents = $query->paginate($perPage);

        /*
    |--------------------------------------------------------------------------
    | 8) Bổ sung size và file_path
    |--------------------------------------------------------------------------
    */
        foreach ($documents as $doc) {
            $path = base_path('app/Public_UploadFile/' . ($doc->file_name ?? ''));

            $doc->size = file_exists($path) ? filesize($path) : null;
            $doc->file_path = file_exists($path)
                ? asset('app/Public_UploadFile/' . $doc->file_name)
                : null;
        }

        return response()->json([
            'success' => true,
            'data' => $documents->items(),
            'current_page' => $documents->currentPage(),
            'last_page' => $documents->lastPage(),
            'total' => $documents->total(),
            'per_page' => $documents->perPage(),
        ]);
    }
    public function downloadVersion($versionId)
    {
        $version = DocumentVersion::find($versionId);

        // Không tìm thấy version → chuyển sang trang not found đẹp
        if (!$version) {
            return $this->renderFileNotFound('Phiên bản tài liệu không tồn tại.');
        }

        // file_path rỗng → lỗi dữ liệu
        if (empty(trim($version->file_path))) {
            return $this->renderFileNotFound('Tài liệu này không có file đính kèm.');
        }

        $filePath = $version->file_path;

        // FILE CÓ TỒN TẠI → CHO TẢI NGAY
        if (Storage::disk('public')->exists($filePath)) {
            $downloadName = $version->original_name
                ?? $version->file_name
                ?? basename($filePath);

            return Storage::disk('public')->download(
                $filePath,
                $downloadName,
                ['Content-Type' => $version->mime_type ?? 'application/octet-stream']
            );
        }

        // FILE KHÔNG TỒN TẠI → HIỆN TRANG ĐẸP
        return $this->renderFileNotFound('File đã bị xóa khỏi hệ thống hoặc không còn tồn tại.');
    }

    /**
     * Render trang thông báo file không tồn tại (đẹp, thân thiện)
     */
    private function renderFileNotFound(string $message = 'File không tồn tại.')
    {
        // Kiểm tra view có tồn tại không, nếu không thì fallback về view mặc định
        if (View::exists('documents.See_Document_Details.notfound')) {
            return response()
                ->view('documents.See_Document_Details.notfound', [
                    'message' => $message,
                ], 404)
                ->header('Cache-Control', 'no-store, no-cache');
        }

        // Fallback nếu view chưa tạo
        return response()
            ->view('errors.file-not-found', ['message' => $message], 404);
    }
}
