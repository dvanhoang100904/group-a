<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\DocumentVersion\DocumentVersionPreviewService;
use Illuminate\Support\Facades\Storage;

class DocumentDetailController extends Controller
{
    public function __construct(
        protected DocumentVersionPreviewService $previewService
    ) {}

    public function show($id)
    {
        $document = Document::with(['user', 'type', 'subject', 'tags'])->findOrFail($id);

        $currentVersion = DocumentVersion::where('document_id', $document->document_id)
            ->where('is_current_version', true)
            ->firstOrFail();

        $currentVersion->file_url = asset('storage/' . $currentVersion->file_path);

        // =================== SIÊU FIX CHỈ 15 DÒNG NÀY LÀ XONG HẾT ===================
        $previewUrl   = null;
        $previewReady = false;

        // 1. Ưu tiên dùng service (nếu nó sinh được preview thì quá ngon)
        $fromService = $this->previewService->getOrGeneratePreview($currentVersion->version_id, $document->document_id);

        if ($fromService && !empty($fromService['preview_path'])) {
            $previewUrl   = $fromService['preview_path']; // có thể là /storage/previews/9040.pdf
            $previewReady = true;
        } else {
            // 2. Nếu service chưa kịp sinh → TỰ ĐỘNG TÌM THEO 2 QUY ƯỚC ĐANG TỒN TẠI
            $candidates = [
                'previews/' . (1000 + $currentVersion->version_id) . '.pdf',  // chuẩn mới: 10029.pdf, 19040.pdf
                'previews/' . $currentVersion->version_id . '.pdf',           // chuẩn cũ của service: 9040.pdf
            ];

            foreach ($candidates as $path) {
                if (Storage::disk('public')->exists($path)) {
                    $previewUrl   = Storage::url($path);
                    $previewReady = true;
                    break;
                }
            }
        }
        // ============================================================================

        // Gắn vào để Vue đọc như cũ
        $currentVersion->preview_url   = $previewUrl;
        $currentVersion->preview_ready = $previewReady;

        // Tài liệu liên quan
        $related = Document::where('subject_id', $document->subject_id)
            ->where('document_id', '!=', $id)
            ->inRandomOrder()
            ->take(3)
            ->get(['document_id', 'title', 'created_at']);

        return response()->json([
            'document'        => $document,
            'current_version' => $currentVersion,
            'preview_ready'   => $previewReady,
            'preview_url'     => $previewUrl,
            'related_documents' => $related,
        ]);
    }
}