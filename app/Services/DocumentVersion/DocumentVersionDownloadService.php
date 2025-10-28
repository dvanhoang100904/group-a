<?php

namespace App\Services\DocumentVersion;

use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentVersionDownloadService
{
    /**
     * Tai xuong phien ban tai lieu
     */
    public function downloadVersion(int $documentId, int $versionId)
    {
        try {
            // Lay phien ban can tai
            $version = DocumentVersion::query()
                ->with('document:document_id')
                ->byDocument($documentId)
                ->byVersion($versionId)
                ->select(['version_id', 'version_number', 'file_path', 'document_id'])
                ->first();

            if (!$version) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phiên bản không tồn tại.',
                ], 404);
            }

            $filePath = $version->file_path;

            // Kiem tra file ton tai
            if (empty($filePath) || !Storage::disk('public')->exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tệp cần tải xuống không tồn tại trên máy chủ.',
                ], 404);
            }

            // Lay ten file hien thi khi tai xuong
            $fileName = basename($filePath);

            // Stream file de tranh load toan bo vao bo nho
            return response()->streamDownload(function () use ($filePath, $version) {
                $stream = Storage::disk('public')->readStream($filePath);
                fpassthru($stream);
                fclose($stream);

                // Ghi log
                try {
                    $version->document?->activities()->create([
                        'action' => 'download',
                        'user_id' => Auth::id() ?? 1,
                        'action_detail' => json_encode([
                            'message' => "Tải xuống phiên bản #{$version->version_number}."
                        ]),
                    ]);
                } catch (\Throwable $th) {
                    // khong lam gian doan response neu loi log
                    report($th);
                }
            }, $fileName);
        } catch (\Throwable $th) {
            report($th);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải xuống tệp.',
            ], 500);
        }
    }
}
