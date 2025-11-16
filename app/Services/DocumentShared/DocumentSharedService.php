<?php

namespace App\Services\DocumentShared;

use App\Models\Document;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentSharedService
{
    // Dinh nghia so luong tai lieu
    const PER_PAGE = 5;

    /**
     * Lay danh sach tai lieu duoc chia se voi toi co loc tim kiem phan trang
     */
    public function getSharedDocumentsPaginated(int $userId, array $filters = []): LengthAwarePaginator
    {
        return Document::query()
            ->select(['document_id', 'title', 'user_id',])
            ->whereHas('accesses', function ($q) use ($userId, $filters) {
                $q->where('granted_to_user_id', $userId);

                if (!empty($filters['from_date'])) {
                    $q->whereDate('created_at', '>=', $filters['from_date']);
                }
                if (!empty($filters['to_date'])) {
                    $q->whereDate('created_at', '<=', $filters['to_date']);
                }

                if (!empty($filters['user_id'])) {
                    $q->where('granted_by', $filters['user_id']);
                }
            })
            ->with([
                'user:user_id,name',
                'accesses' => function ($q) use ($userId) {
                    $q->where('granted_to_user_id', $userId)
                        ->select([
                            'access_id',
                            'document_id',
                            'granted_by',
                            'can_view',
                            'can_edit',
                            'can_delete',
                            'can_upload',
                            'can_download',
                            'can_share',
                            'created_at',
                        ])
                        ->with('grantedBy:user_id,name')
                        ->orderByDesc('access_id');
                }
            ])
            ->when(!empty($filters['keyword']), function ($q) use ($filters) {
                $keyword = "%{$filters['keyword']}%";
                $q->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', $keyword)
                        ->orWhere('description', 'like', $keyword);
                });
            })
            ->paginate(self::PER_PAGE);
    }

    /**
     * Lay danh sach người dung de loc tai lieu duoc chia se voi toi
     */
    public function getSharedForUser(int $userId)
    {
        return User::whereHas('grantedDocuments', function ($query) use ($userId) {
            $query->whereHas('accesses', function ($q) use ($userId) {
                $q->where('granted_to_user_id', $userId);
            });
        })
            ->orderBy('name')
            ->distinct()
            ->get(['user_id', 'name']);
    }
}
