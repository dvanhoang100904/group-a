<?php

namespace App\Services\DocumentAccess;

use App\Models\Document;
use App\Models\DocumentAccess;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentAccessDeleteService
{
    /**
     * Xoa quyen tai lieu
     */
    public function deleteAccess($documentId, $accessId): ?DocumentAccess
    {
        $access = DocumentAccess::query()
            ->where('document_id', $documentId)
            ->where('access_id', $accessId)
            ->first();

        if (!$access) {
            return null;
        }

        try {
            DB::beginTransaction();

            $access->delete();

            DB::commit();

            return $access;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lổi xóa quyền chia sẻ ' . $e->getMessage());
            return null;
        }
    }
}
