<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    // Hiển thị blade
    public function index()
    {
        return view('documents.My_Documents.My_Documents');
    }

    // API trả JSON toàn bộ danh sách documents
    public function getDocuments()
    {
        $documents = Document::with(['user', 'type', 'subject'])
            ->orderByDesc('created_at')
            ->get();

        // Thêm size file nếu có
        foreach ($documents as $doc) {
            $path = base_path('app/Public_UploadFile/' . ($doc->file_name ?? ''));
            $doc->size = file_exists($path) ? filesize($path) : null;
            $doc->file_path = file_exists($path) ? asset('app/Public_UploadFile/' . $doc->file_name) : null;
        }

        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }
}
