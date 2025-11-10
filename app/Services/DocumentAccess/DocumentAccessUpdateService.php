<?php

namespace App\Services\DocumentAccess;

use App\Models\DocumentAccess;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentAccessUpdateService
{
    /**
     * chinh sua quyen tai lieu
     */
    public function updateAccess(int $documentId, int $accessId, array $data): ?DocumentAccess
    {
        $access = DocumentAccess::query()
            ->select([
                'access_id',
                'granted_to_type',
                'granted_to_user_id',
                'granted_to_role_id',
                'can_view',
                'can_edit',
                'can_delete',
                'can_upload',
                'can_download',
                'can_share',
                'no_expiry',
                'expiration_date',
                'document_id',
                'granted_by'
            ])
            ->where('document_id', $documentId)
            ->where('access_id', $accessId)
            ->first();

        if (!$access) {
            return null;
        }

        try {
            DB::beginTransaction();
            $access->can_view = $data['can_view'] ?? $access->can_view;
            $access->can_edit = $data['can_edit'] ?? $access->can_edit;
            $access->can_delete = $data['can_delete'] ?? $access->can_delete;
            $access->can_upload = $data['can_upload'] ?? $access->can_upload;
            $access->can_download = $data['can_download'] ?? $access->can_download;
            $access->can_share = $data['can_share'] ?? $access->can_share;
            $access->no_expiry = $data['no_expiry'] ?? $access->no_expiry;
            if (!empty($data['no_expiry']) && $data['no_expiry']) {
                $access->expiration_date = null;
            } else {
                if (isset($data['expiration_date'])) {
                    $access->expiration_date = $data['expiration_date'];
                }
            }
            $access->save();

            DB::commit();

            return $access;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi chỉnh sửa quyền chia sẻ: ' . $e->getMessage());
            return null;
        }
    }
}
