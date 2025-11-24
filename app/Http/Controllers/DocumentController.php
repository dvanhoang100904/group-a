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
