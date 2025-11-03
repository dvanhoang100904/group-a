<?php

namespace App\Services\DocumentAccess;

use App\Models\DocumentAccess;
use Illuminate\Support\Facades\DB;
use Throwable;

class DocumentAccessDeleteService
{
    /**
     * Xoa quyen tai lieu
     */
    public function deleteAccess($documentId, $accessId): bool
    {
        DB::beginTransaction();

        try {
            $access = DocumentAccess::where('document_id', $documentId)
                ->where('access_id', $accessId)
                ->first();

            if (!$access) {
                DB::rollBack();
                return false;
            }

            $access->delete();

            DB::commit();
            return true;
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);
            return false;
        }
    }
}
