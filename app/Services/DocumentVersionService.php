<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentVersionService
{
    const PER_PAGE = 5;

    /**
     * Lay tai lieu hien thi trang phien ban tai lieu
     */
    public function getDocumentWithRelations(int $documentId): ?Document
    {
        return Document::with('subject.department')->withCount('versions')->find($documentId);
    }

    /**
     * Lay danh sach phien ban cua tai lieu co phan trang
     */
    public function getDocumentVersionsHasPaginated(int $documentId, array $filters = [])
    {
        $document = Document::find($documentId);

        if (!$document) {
            return null;
        }

        $filters = is_array($filters) ? $filters : [];

        return $document->versions()
            ->with('user:user_id,name')
            ->select('version_id', 'version_number', 'user_id', 'created_at', 'is_current_version', 'file_path')
            ->filter($filters)
            ->latestOrder()
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Chi tiet phien ban tai lieu
     */
    public function getDocumentVersion(int $documentId, int $versionId)
    {
        return DocumentVersion::with([
            'user:user_id,name',
            'latestPreview'
        ])
            ->select('version_id', 'version_number', 'user_id', 'created_at', 'is_current_version', 'document_id', 'change_note', 'file_size', 'mime_type')
            ->byDocument($documentId)
            ->byVersion($versionId)
            ->first();
    }

    /**
     * Tai xuong phien ban tai lieu
     */
    public function downloadVersion(int $documentId, int $versionId)
    {
        $version = DocumentVersion::byDocument($documentId)
            ->byVersion($versionId)
            ->first();

        if (!$version) {
            abort(404, 'Phiên bản không tồn tại.');
        }

        $filePath = $version->file_path;

        if (empty($filePath) || !Storage::disk('public')->exists($filePath)) {
            abort(404, 'File cần tải xuống không tồn tại.');
        }

        $fileName = basename($filePath);

        return response()->streamDownload(function () use ($filePath, $version) {
            $stream = Storage::disk('public')->readStream($filePath);
            fpassthru($stream);
            fclose($stream);

            // Ghi log download
            try {
                $version->document?->activities()->create([
                    'action' => 'download',
                    'user_id' => Auth::id() ?? 1,
                    'action_detail' => "Tải xuống phiên bản #{$version->version_number}",
                ]);
            } catch (\Exception $e) {
                report($e);
            }
        }, $fileName);
    }
}
