<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentPreview;
use Illuminate\Support\Facades\Storage;

class DocumentDetailController extends Controller
{
    public function show($id)
    {
        // ✔ Lấy document + quan hệ
        $document = Document::with([
            'user',
            'type',
            'subject',
            'versions',
            'tags'
        ])->findOrFail($id);

        // ✔ Lấy version hiện tại
        $currentVersion = $document->versions()
            ->where('is_current_version', true)
            ->first();

        if (!$currentVersion) {
            return response()->json([
                'error' => 'Không tìm thấy phiên bản hiện tại.'
            ], 404);
        }

        // ✔ File gốc (download)
        $currentVersion->file_url = asset('storage/' . $currentVersion->file_path);

        // ✔ Tìm preview PDF của version này
        $preview = DocumentPreview::where('document_id', $document->document_id)
            ->where('version_id', $currentVersion->version_id)
            ->first();

        // ✔ preview_path có tồn tại?
        if ($preview && Storage::disk('public')->exists($preview->preview_path)) {
            $preview_url = asset('storage/' . $preview->preview_path);
            $preview_ready = true;
        } else {
            $preview_url = null;
            $preview_ready = false;
        }

        // ✔ Lấy 3 tài liệu liên quan cùng subject
        $related = Document::where('subject_id', $document->subject_id)
            ->where('document_id', '!=', $id)
            ->take(3)
            ->get();

        return response()->json([
            'document' => $document,
            'current_version' => $currentVersion,
            'preview_ready' => $preview_ready,
            'preview_url' => $preview_url,
            'related_documents' => $related
        ]);
    }
}
