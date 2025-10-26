<?php

namespace App\Services\DocumentVersion;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentVersionService
{
    const PER_PAGE = 5;

    /**
     * Lay tai lieu hien thi trang phien ban tai lieu
     */
    public function getDocumentWithRelations(int $documentId): ?Document
    {
        return Document::select(['document_id', 'title', 'subject_id', 'folder_id'])
            ->with([
                'folder:folder_id,name',
                'subject:subject_id,name,department_id',
                'subject.department:department_id,name',
                'versions' => fn($q) => $q
                    ->current()
                    ->select('version_id', 'document_id', 'version_number'),
                'accesses' => fn($q) => $q
                    ->select('access_id', 'document_id', 'granted_to_type', 'granted_to_user_id', 'granted_to_role_id', 'granted_by')
                    ->with([
                        'grantedToUser:user_id,name',
                        'grantedBy:user_id,name'
                    ])
            ])
            ->withCount('versions')
            ->whereKey($documentId)
            ->first();
    }

    /**
     * Lay danh sach phien ban cua tai lieu co phan trang
     */
    public function getDocumentVersionsHasPaginated(int $documentId, array $filters = [])
    {
        return DocumentVersion::query()
            ->where('document_id', $documentId)
            ->select([
                'version_id',
                'version_number',
                'user_id',
                'created_at',
                'is_current_version',
                'file_path',
            ])
            ->with([
                'user:user_id,name'
            ])
            ->filter($filters)
            ->latestOrder()
            ->paginate(self::PER_PAGE, ['*'], 'page')
            ->withQueryString();
    }

    /**
     * Chi tiet phien ban tai lieu
     */
    public function getDocumentVersion(int $documentId, int $versionId)
    {
        return DocumentVersion::with([
            'user:user_id,name',
            'latestPreview'
        ])
            ->select('version_id', 'version_number', 'user_id', 'created_at', 'is_current_version', 'document_id', 'change_note', 'file_size', 'mime_type')
            ->byDocument($documentId)
            ->byVersion($versionId)
            ->first();
    }

    /**
     * Tai xuong phien ban tai lieu
     */
    public function downloadVersion(int $documentId, int $versionId)
    {
        try {
            // Lay phien ban can tai
            $version = DocumentVersion::query()
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
                } catch (\Throwable $e) {
                    // khong lam gian doan response neu loi log
                    report($e);
                }
            }, $fileName);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải xuống tệp.',
            ], 500);
        }
    }

    /**
     * Khoi phuc phien ban tai lieu
     */
    public function restoreVersion(int $documentId, int $versionId)
    {
        DB::beginTransaction();
        try {
            // Lay phien ban can khoi phuc cung tai lieu 
            $version = DocumentVersion::with('document')
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
            } catch (\Throwable $e) {
                // Khong lam gian doan response neu loi log
                report($e);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Đã khôi phục phiên bản #{$version->version_number} thành công.",
            ]);
        } catch (\Throwable $e) {
            //throw $th;
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi khôi phục phiên bản.',
            ], 500);
        }
    }
}
