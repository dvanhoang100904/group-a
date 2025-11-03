<?php

namespace App\Services\DocumentAccess;

use App\Models\DocumentAccess;
use Illuminate\Support\Facades\DB;
use Throwable;

class DocumentAccessUpdateService
{
    /**
     * chinh sua quyen tai lieu
     */
    public function updateAccess(int $documentId, int $accessId, array $data, int $grantedBy): ?DocumentAccess
    {
        DB::beginTransaction();

        try {
            $access = DocumentAccess::forDocument($documentId)
                ->byAccessId($accessId)
                ->first();

            if (!$access) {
                DB::rollBack();
                return null;
            }

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

            $access->granted_by = $grantedBy;

            $access->save();

            DB::commit();

            return $access;
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);
            return null;
        }
    }
}
