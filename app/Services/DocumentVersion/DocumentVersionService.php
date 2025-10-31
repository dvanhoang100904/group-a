<?php

namespace App\Services\DocumentVersion;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;

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
            ->byDocument($documentId)
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
            ])
            ->first();
    }

    /**
     * Lay danh sach người upload
     */
    public function getUploaders(int $documentId)
    {
        return User::query()
            ->select('users.user_id', 'users.name')
            ->join('document_versions', 'users.user_id', '=', 'document_versions.user_id')
            ->where('document_versions.document_id', $documentId)
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }
}
