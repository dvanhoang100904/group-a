<?php

namespace App\Services\DocumentVersion;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentVersionService
{
    // Dinh nghia so luong phien ban tai lieu
    const PER_PAGE = 5;

    /**
     * Lay tai lieu hien thi trang phien ban tai lieu
     */
    public function getDocumentWithRelations(int $documentId): ?Document
    {
        return Document::query()
            ->select([
                'document_id',
                'title',
                'subject_id'
            ])
            ->with([
                'subject:subject_id,name,department_id',
                'subject.department:department_id,name',
            ])
            ->withCount('versions')
            ->find($documentId);
    }

    /**
     * Lay tai lieu theo id
     */
    public function getDocumentById(int $documentId): ?Document
    {
        $document = Document::find($documentId);

        if (!$document) {
            return null;
        }

        return $document;
    }

    /**
     * Lay danh sach phien ban cua tai lieu co loc tim kiem phan trang
     */
    public function getDocumentVersionsHasPaginated(int $documentId, array $filters = []): LengthAwarePaginator
    {
        return DocumentVersion::query()
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
            ->where('document_id', $documentId)
            ->when(!empty($filters['keyword']), function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('change_note', 'like', "%{$filters['keyword']}%")
                        ->orWhere('version_number', 'like', "%{$filters['keyword']}%");
                });
            })
            ->when(!empty($filters['user_id']), function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            })
            ->when(isset($filters['status']) && $filters['status'] !== '', function ($q) use ($filters) {
                $q->where('is_current_version', filter_var($filters['status'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(!empty($filters['from_date']), function ($q) use ($filters) {
                $q->whereDate('created_at', '>=', $filters['from_date']);
            })
            ->when(!empty($filters['to_date']), function ($q) use ($filters) {
                $q->whereDate('created_at', '<=', $filters['to_date']);
            })
            ->orderByDesc('version_number')
            ->orderByDesc('created_at')
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
     * Lay danh sach người dung de loc phien ban tai lieu
     */
    public function getUsersForDocumentVersion(int $documentId)
    {
        return User::whereHas('documentVersions', function ($query) use ($documentId) {
            $query->where('document_id', $documentId);
        })
            ->orderBy('name')
            ->distinct()
            ->get([
                'user_id',
                'name'
            ]);
    }
}
