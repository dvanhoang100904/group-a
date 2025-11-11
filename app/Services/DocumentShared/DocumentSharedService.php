<?php

namespace App\Services\DocumentShared;

use App\Models\Document;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentSharedService
{
    // Dinh nghia so luong tai lieu
    const PER_PAGE = 5;

    /**
     * Lay danh sach tai lieu duoc chia se voi toi  co loc tim kiem phan trang
     */
    public function getSharedDocumentsPaginated(int $userId): LengthAwarePaginator
    {
        return Document::query()
            ->select([
                'document_id',
                'title',
                'user_id',
                'created_at'
            ])
            ->whereHas('accesses', function ($q) use ($userId) {
                $q->where('granted_to_user_id', $userId);
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
                            'can_download',
                            'created_at'
                        ])
                        ->with('grantedBy:user_id,name');
                }
            ])

            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE);
    }
}
