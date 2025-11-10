<?php

namespace App\Services\DocumentVersion;

use App\Models\Document;
use App\Models\DocumentVersion;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
    public function uploadVersion(Int $documentId, UploadedFile $file, array $data, ?int $userId = null): ?DocumentVersion
    {
        $document = Document::find($documentId);

        if (!$document) {
            return null;
        }

        try {
            DB::beginTransaction();

            // 1. Tao ten file duy nhat 
            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();

            // 2. Luu file vao storage/app/public/docs
            $filePath = $file->storeAs('docs', $fileName, 'public');

            // 3. Lay so phien ban moi
            $lastVersion = $document->versions()
                ->orderByDesc('version_number')
                ->first();
            $nextVersionNumber = $lastVersion ? $lastVersion->version_number + 1 : 1;

            // 4. Tao phien ban moi 
            $version = new DocumentVersion([
                'document_id' => $document->document_id,
                'version_number' => $nextVersionNumber,
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'change_note' => $data['change_note'] ?? null,
                'is_current_version' => true,
                'user_id' => $userId ?? auth()->id() ?? 1,
            ]);
            $version->save();

            // 5. Danh dau cac phien ban cu la khong hien tai       
            foreach ($document->versions()->where('version_id', '!=', $version->version_id)->cursor() as $oldVersion) {
                $oldVersion->is_current_version = false;
                $oldVersion->save();
            }

            // 6. Sinh preview file
            $this->previewService->getOrGeneratePreview($version->version_id, $document->document_id);

            DB::commit();

            return $version;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi upload phiên bản tài liệu: ' . $e->getMessage());

            if (isset($filePath) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            return null;
        }
    }
}
