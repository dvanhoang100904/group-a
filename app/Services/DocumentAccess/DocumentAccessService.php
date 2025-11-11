<?php

namespace App\Services\DocumentAccess;

use App\Models\Document;
use App\Models\DocumentAccess;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentAccessService
{
    // Dinh nghia so luong quyen truy cap tai lieu moi trang
    const PER_PAGE = 8;

    /**
     * Lay tai lieu hien thi trang quyen truy cap tai lieu
     */
    public function getDocument(int $documentId): ?Document
    {
        $document = Document::query()
            ->select([
                'document_id',
                'title',
                'subject_id',
                'share_mode',
                'share_link',
                'no_expiry',
                'expiration_date',
            ])
            ->with([
                'subject:subject_id,name,department_id',
                'subject.department:department_id,name',
            ])
            ->find($documentId);

        if (!$document) {
            return null;
        }

        return $document;
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
     * Lay danh sach quyen truy cap cua tai lieu co phan trang
     */
    public function getDocumentAccessesHasPaginated(int $documentId): LengthAwarePaginator
    {
        return DocumentAccess::query()
            ->select([
                'access_id',
                'granted_by',
                'granted_to_type',
                'granted_to_user_id',
                'granted_to_role_id',
                'can_view',
                'can_edit',
                'can_delete',
                'can_upload',
                'can_download',
                'can_share',
                'expiration_date',
                'no_expiry'
            ])
            ->with([
                'grantedBy:user_id,name',
                'grantedToUser:user_id,name',
                'grantedToRole:role_id,name',
            ])
            ->where('document_id', $documentId)
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE, ['*'], 'page')
            ->withQueryString();
    }

    /**
     * Lay tat ca nguoi dung chua duoc cap quyen
     */
    public function getUsersForAccess(int $documentId): Collection
    {
        return User::query()
            ->select([
                'user_id',
                'name'
            ])
            ->where('status', true)
            ->whereNotIn('user_id', function ($q) use ($documentId) {
                $q->select([
                    'granted_to_user_id'
                ])
                    ->from('document_accesses')
                    ->where('document_id', $documentId)
                    ->whereNotNull('granted_to_user_id');
            })
            ->orderBy('name')
            ->get();
    }

    /** 
     * Lay tat ca vai tro chua duoc cap quyen
     */
    public function getRolesForAccess(int $documentId): Collection
    {
        return Role::query()
            ->select([
                'role_id',
                'name'
            ])
            ->where('status', true)
            ->whereNotIn('role_id', function ($q) use ($documentId) {
                $q->select([
                    'granted_to_role_id'
                ])
                    ->from('document_accesses')
                    ->where('document_id', $documentId)
                    ->whereNotNull('granted_to_role_id');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Cap nhat quyen truy cap tai lieu
     */
    public function updateSettingAccess(int $documentId, array $data): ?Document
    {
        $document = Document::find($documentId);

        if (!$document) {
            return null;
        }

        try {
            DB::beginTransaction();

            $allowedModes = ['public', 'private', 'custom'];
            $shareMode = $data['share_mode'] ?? 'private';
            $document->share_mode = in_array($shareMode, $allowedModes) ? $shareMode : 'private';

            $document->share_link = $data['share_link'] ?? null;

            $document->no_expiry = !empty($data['no_expiry']);
            $document->expiration_date = $document->no_expiry ? null : ($data['expiration_date'] ?? null);

            $document->save();

            DB::commit();
            return $document;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi cập nhật quyền truy cập tài liệu: ' . $e->getMessage());
            return null;
        }
    }
}
