<?php

namespace App\Services\DocumentAccess;

use App\Models\Document;
use App\Models\DocumentAccess;

class DocumentAccessService
{
    const PER_PAGE = 5;

    /**
     * Lay tai lieu hien thi trang quyen chia se tai lieu
     */
    public function getDocumentWithRelations(int $documentId): ?Document
    {
        return Document::select(['document_id', 'title', 'subject_id'])
            ->with([
                'folder:folder_id,name',
                'subject:subject_id,name,department_id',
                'subject.department:department_id,name',
            ])
            ->whereKey($documentId)
            ->first();
    }

    /**
     * Lay danh sach quyen truy cap cua tai lieu co phan trang
     */
    public function getDocumentAccessesHasPaginated(int $documentId)
    {
        return DocumentAccess::query()
            ->where('document_id', $documentId)
            ->select([
                'access_id',
                'granted_by',
                'granted_to_user_id',
                'granted_to_role_id',
                'can_view',
                'can_edit',
                'can_delete',
                'can_download',
                'expiration_date'
            ])
            ->with([
                'grantedBy:user_id,name',
                'grantedToUser:user_id,name',
                'grantedToRole:role_id,name',
            ])
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
