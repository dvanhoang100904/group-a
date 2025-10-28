<?php

namespace App\Services\DocumentVersion;

use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentVersionDeleteService
{
    /**
     * Xoa phien ban tai lieu
     */
    public function deleteVersion(int $documentId, int $versionId)
    {
        DB::beginTransaction();
        try {
            $version = DocumentVersion::query()
                ->with('document:document_id')
                ->byDocument($documentId)
                ->byVersion($versionId)
                ->select(['version_id', 'file_path', 'is_current_version', 'version_number', 'document_id'])
                ->first();

            if (!$version) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Phiên bản không tồn tại hoặc đã bị xó.',
                ], 404);
            }

            // Khong cho xoa phien ban hien tai
            if (!$version->isDeletable()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Không thể xóa phiên bản hiện tại (#{$version->version_number}).",
                ]);
            }

            // Xoa file neu ton tai
            if ($version->file_path && Storage::disk('public')->exists($version->file_path)) {
                Storage::disk('public')->delete($version->file_path);
            }

            // Xoa ban ghi
            $version->delete();

            // Ghi log
            try {
                $version->document?->activities()->create([
                    'action' => 'delete',
                    'user_id' => Auth::id() ?? 1,
                    'action_detail' => json_encode([
                        'message' => "Đã xóa phiên bản #{$version->version_number}.",
                    ], JSON_UNESCAPED_UNICODE),
                ]);
            } catch (\Throwable $th) {
                report($th);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Phiên bản #{$version->version_number} đã được xóa thành công.",
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa phiên bản.',
            ], 500);
        }
    }
}
