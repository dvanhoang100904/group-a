<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    public function index()
    {
        return view('documents.My_Documents.My_Documents');
    }

    public function getDocuments(Request $request)
    {
        try {
            $search = $request->search ?? '';

            $query = Document::with(['user', 'type', 'subject']);

            if ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            }

            $documents = $query->orderByDesc('created_at')->paginate(10);

            $data = $documents->map(function ($doc) {
                $path = base_path('app/Public_UploadFile/' . $doc->file_name);
                $doc->size = File::exists($path)
                    ? $this->formatFileSize(File::size($path))
                    : 'Không tìm thấy';
                return $doc;
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $documents->total(),
                'per_page' => $documents->perPage(),
            ]);
        } catch (\Exception $e) {
            Log::error("getDocuments error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tải danh sách: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $document = Document::with(['user', 'type', 'subject', 'tags'])->find($id);

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tài liệu'], 404);
        }

        $path = base_path('app/Public_UploadFile/' . $document->file_name);
        $document->file_exists = File::exists($path);
        $document->size = $document->file_exists
            ? $this->formatFileSize(File::size($path))
            : 'Không tìm thấy';
        $document->file_path = $document->file_exists ? asset('app/Public_UploadFile/' . $document->file_name) : null;

        return response()->json(['success' => true, 'data' => $document]);
    }

    public function update(Request $request, $id)
    {
        $doc = Document::find($id);
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tài liệu'], 404);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|max:20',
        ]);

        $doc->update($validated);

        return response()->json(['success' => true, 'message' => 'Cập nhật thành công', 'data' => $doc]);
    }

    public function destroy($id)
    {
        $doc = Document::find($id);
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tài liệu'], 404);
        }

        $path = base_path('app/Public_UploadFile/' . $doc->file_name);
        if (File::exists($path)) File::delete($path);
        $doc->delete();

        return response()->json(['success' => true, 'message' => 'Xóa tài liệu thành công']);
    }

    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        elseif ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        elseif ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        elseif ($bytes > 1) return $bytes . ' bytes';
        elseif ($bytes == 1) return '1 byte';
        else return '0 bytes';
    }
}
