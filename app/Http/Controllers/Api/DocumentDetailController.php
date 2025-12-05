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
        $document = Document::with(['user', 'type', 'subject', 'versions', 'tags'])->findOrFail($id);

        $currentVersion = $document->versions()
            ->where('is_current_version', true)
            ->firstOrFail();

        // File gốc
        $currentVersion->file_url = Storage::disk('public')->url($currentVersion->file_path);

        // TÌM PREVIEW PDF
        $preview = DocumentPreview::where('document_id', $document->document_id)
            ->where('version_id', $currentVersion->version_id)
            ->first();

        $preview_url = null;

        if ($preview && Storage::disk('public')->exists($preview->preview_path)) {
            $preview_url = Storage::disk('public')->url($preview->preview_path);
        } else {
            // Fallback: tự tạo URL theo quy ước của ông (preview_v1.pdf)
            $fallbackPath = "documents/{$document->document_id}/preview_v{$currentVersion->version_number}.pdf";
            if (Storage::disk('public')->exists($fallbackPath)) {
                $preview_url = Storage::disk('public')->url($fallbackPath);
            }
        }

        // QUAN TRỌNG NHẤT: GÁN VÀO currentVersion ĐỂ VUE ĐỌC ĐƯỢC!!!
        $currentVersion->preview_url = $preview_url;
        $currentVersion->preview_ready = !is_null($preview_url);

        // Tài liệu liên quan
        $related = Document::where('subject_id', $document->subject_id)
            ->where('document_id', '!=', $id)
            ->inRandomOrder()
            ->take(3)
            ->get();

        return response()->json([
            'document'          => $document,
            'current_version'   => $currentVersion, // ← Vue đọc preview_url ở đây
            'preview_ready'     => $currentVersion->preview_ready,
            'preview_url'       => $preview_url,
            'related_documents' => $related
        ]);
    }
}
