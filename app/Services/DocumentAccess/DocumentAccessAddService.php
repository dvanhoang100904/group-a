<?php

namespace App\Services\DocumentAccess;

use App\Models\DocumentAccess;
use Illuminate\Support\Facades\DB;
use Throwable;

class DocumentAccessAddService
{
    /**
     * Them moi quyen tai lieu
     */
    public function addAccess(array $data, int $documentId, int $grantedBy): ?DocumentAccess
    {
        DB::beginTransaction();

        try {
            // Kiem tra trung
            $exists = DocumentAccess::where('document_id', $documentId)
                ->when($data['granted_to_type'] === 'user', function ($q) use ($data) {
                    $q->where('granted_to_user_id', $data['granted_to_user_id']);
                })
                ->when($data['granted_to_type'] === 'role', function ($q) use ($data) {
                    $q->where('granted_to_role_id', $data['granted_to_role_id']);
                })
                ->exists();

            if ($exists) {
                DB::rollBack();
                return null;
            }

            $access = new DocumentAccess();
            $access->document_id = $documentId;
            $access->granted_by = $grantedBy;
            $access->granted_to_type = $data['granted_to_type'];
            $access->granted_to_user_id = $data['granted_to_user_id'] ?? null;
            $access->granted_to_role_id = $data['granted_to_role_id'] ?? null;
            $access->no_expiry = $data['no_expiry'] ?? false;
            $access->expiration_date = $access->no_expiry ? null : ($data['expiration_date'] ?? null);
            $access->can_view = $data['can_view'] ?? false;
            $access->can_download = $data['can_download'] ?? false;
            $access->can_edit = $data['can_edit'] ?? false;
            $access->can_delete = $data['can_delete'] ?? false;
            $access->can_upload = $data['can_upload'] ?? false;
            $access->can_share = $data['can_share'] ?? false;

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
