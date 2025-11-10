<?php

namespace App\Services\DocumentAccess;

use App\Models\Document;
use App\Models\DocumentAccess;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentAccessAddService
{
    /**
     * Them moi quyen tai lieu
     */
    public function addAccess(array $data, int $documentId, int $grantedBy): ?DocumentAccess
    {
        $document = Document::find($documentId);

        if (!$document) {
            return null;
        }

        $existing = DocumentAccess::query()
            ->where('document_id', $documentId)
            ->when($data['granted_to_type'] === 'user', function ($q) use ($data) {
                $q->where('granted_to_user_id', $data['granted_to_user_id']);
            })
            ->when($data['granted_to_type'] === 'role', function ($q) use ($data) {
                $q->where('granted_to_role_id', $data['granted_to_role_id']);
            })
            ->first();

        if ($existing) {
            return null;
        }

        try {
            DB::beginTransaction();

            $access = new DocumentAccess([
                'document_id' => $documentId,
                'granted_by' => $grantedBy,
                'granted_to_type' => $data['granted_to_type'],
                'granted_to_user_id' => $data['granted_to_user_id'] ?? null,
                'granted_to_role_id' => $data['granted_to_role_id'] ?? null,
                'no_expiry' => $data['no_expiry'] ?? false,
                'expiration_date' => $data['no_expiry'] ? null : ($data['expiration_date'] ?? null),
                'can_view' => $data['can_view'] ?? false,
                'can_download' => $data['can_download'] ?? false,
                'can_edit' => $data['can_edit'] ?? false,
                'can_delete' => $data['can_delete'] ?? false,
                'can_upload' => $data['can_upload'] ?? false,
                'can_share' => $data['can_share'] ?? false,
            ]);

            $access->save();

            DB::commit();

            return $access;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi thêm quyền chia sẻ: ' . $e->getMessage());
            return null;
        }
    }
}
