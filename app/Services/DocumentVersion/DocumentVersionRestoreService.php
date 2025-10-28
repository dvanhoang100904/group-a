<?php

namespace App\Services\DocumentVersion;

use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentVersionRestoreService
{
    /**
     * Khoi phuc phien ban tai lieu
     */
    public function restoreVersion(int $documentId, int $versionId)
    {
        DB::beginTransaction();
        try {
            // Lay phien ban can khoi phuc cung tai lieu 
            $version = DocumentVersion::query()
                ->with('document:document_id')
                ->byDocument($documentId)
                ->byVersion($versionId)
                ->select(['version_id', 'version_number', 'document_id', 'is_current_version'])
                ->first();

            if (!$version) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Phiên bản cần khôi phục không tồn tại hoặc đã bị xóa.',
                ], 404);
            }

            // Neu la phien ban hien tai thi bo qua cap nhat
            if ($version->is_current_version) {
                DB::rollBack();
                return response()->json([
                    'success' => true,
                    'message' => "Phiên bản #{$version->version_number} đã là phiên bản hiện tại.",
                ], JSON_UNESCAPED_UNICODE);
            }

            // Cap nhat chi khi co thay doi bo current cu, set current moi
            DocumentVersion::clearCurrent($documentId);
            $version->markAsCurrent();

            // Ghi log
            try {
                $version->document?->activities()->create([
                    'action' => 'restore',
                    'user_id' => Auth::id() ?? 1,
                    'action_detail' => json_encode([
                        'message' => "Khôi phục phiên bản #{$version->version_number} làm phiên bản hiện tại."
                    ], JSON_UNESCAPED_UNICODE),
                ]);
            } catch (\Throwable $th) {
                // Khong lam gian doan response neu loi log
                report($th);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Đã khôi phục phiên bản #{$version->version_number} thành công.",
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            report($th);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi khôi phục phiên bản.',
            ], 500);
        }
    }
}
