<?php

namespace App\Services\DocumentVersion;

use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Event\Code\Throwable;
use Illuminate\Support\Str;

class DocumentVersionUploadService
{
    protected DocumentVersionPreviewService $previewService;

    public function __construct(DocumentVersionPreviewService $previewService)
    {
        $this->previewService = $previewService;
    }

    /**
     * Upload file va tao phien ban moi cho tai lieu
     */
    public function upload(Document $document, $file, ?string $changeNote = null, ?int $userId = null)
    {
        DB::beginTransaction();

        try {
            // Tao ten file
            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '.' . $file->getClientOriginalExtension();

            $path = $file->storeAs('docs', $fileName, 'public');

            // Tao version moi 
            $version = $document->versions()->create([
                'version_number' => $document->getNextVersionNumber(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'change_note' => $changeNote,
                'is_current_version' => true,
                'user_id' => $userId ?? auth()->id() ?? 1,
            ]);

            // Cap nhat cac version khac ve khong current
            $document->setCurrentVersion($version);

            // Sinh preview file
            $this->previewService->getOrGeneratePreview($version->version_id, $document->document_id);

            DB::commit();

            return $version;
        } catch (Throwable $e) {
            DB::rollBack();

            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    }
}
