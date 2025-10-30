<?php

namespace App\Services\DocumentVersion;

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
        return Document::select(['document_id', 'title', 'subject_id', 'folder_id'])
            ->with([
                'subject:subject_id,name,department_id',
                'subject.department:department_id,name',
            ])
            ->withCount('versions')
            ->whereKey($documentId)
            ->first();
    }

    /**
     * Lay danh sach phien ban cua tai lieu co phan trang
     */
    public function getDocumentVersionsHasPaginated(int $documentId, array $filters = [])
    {
        return DocumentVersion::query()
            ->where('document_id', $documentId)
            ->select([
                'version_id',
                'version_number',
                'user_id',
                'created_at',
                'is_current_version',
                'file_path',
            ])
            ->with([
                'user:user_id,name'
            ])
            ->filter($filters)
            ->latestOrder()
            ->paginate(self::PER_PAGE, ['*'], 'page')
            ->withQueryString();
    }

    /**
     * Chi tiet phien ban tai lieu
     */
    public function getDocumentVersion(int $documentId, int $versionId): ?DocumentVersion
    {
        return DocumentVersion::query()
            ->select([
                'version_id',
                'version_number',
                'user_id',
                'document_id',
                'change_note',
                'file_size',
                'mime_type',
                'is_current_version',
                'created_at'
            ])
            ->byDocument($documentId)
            ->byVersion($versionId)
            ->with([
                'user:user_id,name',
                'latestPreview:preview_id,version_id,preview_path,expires_at,created_at'
            ])
            ->first();
    }
}
