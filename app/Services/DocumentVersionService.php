<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;

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
            ->select('version_id', 'version_number', 'user_id', 'created_at', 'is_current_version')
            ->filter($filters)
            ->latestOrder()
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

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
}
