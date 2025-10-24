<?php

namespace App\Services;

use App\Models\Document;

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
    public function getDocumentVersionsHasPaginated(int $documentId)
    {
        $document = Document::find($documentId);

        if (!$document) {
            return null;
        }

        return $document->versions()
            ->with('user')
            ->orderByDesc('version_number')
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE);
    }
}
