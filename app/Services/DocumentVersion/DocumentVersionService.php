<?php

namespace App\Services\DocumentVersion;

use App\Models\Activity;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentVersionService
{
    // Dinh nghia so luong phien ban tai lieu
    const PER_PAGE = 5;

    /**
     * Lay tai lieu hien thi trang phien ban tai lieu
     */
    public function getDocument(int $documentId): ?Document
    {
        $document = Document::query()
            ->select([
                'document_id',
                'title',
                'subject_id'
            ])
            ->with([
                'subject:subject_id,name,department_id',
                'subject.department:department_id,name',
            ])
            ->withCount('versions')
            ->find($documentId);

        if (!$document) {
            return null;
        }

        return $document;
    }

    /**
     * Lay tai lieu theo id
     */
    public function getDocumentById(int $documentId): ?Document
    {
        $document = Document::find($documentId);

        if (!$document) {
            return null;
        }

        return $document;
    }

    /**
     * Lay danh sach phien ban cua tai lieu co loc tim kiem phan trang
     */
    public function getDocumentVersionsHasPaginated(int $documentId, array $filters = []): LengthAwarePaginator
    {
        return DocumentVersion::query()
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
            ->where('document_id', $documentId)
            ->when(!empty($filters['keyword']), function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('change_note', 'like', "%{$filters['keyword']}%")
                        ->orWhere('version_number', 'like', "%{$filters['keyword']}%");
                });
            })
            ->when(!empty($filters['user_id']), function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            })
            ->when(isset($filters['status']) && $filters['status'] !== '', function ($q) use ($filters) {
                $q->where('is_current_version', filter_var($filters['status'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(!empty($filters['from_date']), function ($q) use ($filters) {
                $q->whereDate('created_at', '>=', $filters['from_date']);
            })
            ->when(!empty($filters['to_date']), function ($q) use ($filters) {
                $q->whereDate('created_at', '<=', $filters['to_date']);
            })
            ->orderByDesc('version_number')
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE, ['*'], 'page')
            ->withQueryString();
    }

    /**
     * Chi tiet phien ban tai lieu
     */
    public function getDocumentVersion(int $documentId, int $versionId): ?DocumentVersion
    {
        $documentVersion = DocumentVersion::query()
            ->select([
                'version_id',
                'version_number',
                'user_id',
                'document_id',
                'change_note',
                'file_size',
                'mime_type',
                'is_current_version',
                'created_at'
            ])
            ->with([
                'user:user_id,name',
            ])
            ->where('document_id', $documentId)
            ->where('version_id', $versionId)
            ->first();

        if (!$documentVersion) {
            return null;
        }

        return $documentVersion;
    }

    /**
     * Lay danh sach người dung de loc phien ban tai lieu
     */
    public function getUsersForDocumentVersion(int $documentId)
    {
        return User::whereHas('documentVersions', function ($query) use ($documentId) {
            $query->where('document_id', $documentId);
        })
            ->orderBy('name')
            ->distinct()
            ->get([
                'user_id',
                'name'
            ]);
    }

    /**
     * Tai xuong phien ban tai lieu
     */
    public function downloadVersion(int $documentId, int $versionId)
    {
        $version = DocumentVersion::query()
            ->select([
                'version_id',
                'version_number',
                'file_path',
                'document_id'
            ])
            ->with('document:document_id')
            ->where('document_id', $documentId)
            ->where('version_id', $versionId)
            ->first();

        if (!$version) {
            return null;
        }

        $filePath = $version->file_path;

        // Kiem tra file ton tai
        if (empty($filePath) || !Storage::disk('public')->exists($filePath)) {
            return null;
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
                DB::beginTransaction();
                $activity = new Activity([
                    'action' => 'download',
                    'user_id' => auth()->id() ?? 1,
                    'action_detail' => json_encode([
                        'message' => "Tải xuống phiên bản #{$version->version_number}."
                    ]),
                ]);
                $version->document?->activities()->save($activity);

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Lỗi tải xuống phiên bản tài liệu: ' . $e->getMessage());
                return null;
            }
        }, $fileName);
    }


    /**
     * Khoi phuc phien ban tai lieu
     */
    public function restoreVersion(int $documentId, int $versionId)
    {
        // Lay phien ban can khoi phuc cung tai lieu 
        $version = DocumentVersion::query()
            ->select([
                'version_id',
                'version_number',
                'document_id',
                'is_current_version'
            ])
            ->with('document:document_id')
            ->where('document_id', $documentId)
            ->where('version_id', $versionId)
            ->first();

        if (!$version) {
            return null;
        }

        try {
            DB::beginTransaction();

            // Neu la phien ban hien tai thi bo qua cap nhat
            if ($version->is_current_version) {
                return $version;
            }

            // Cap nhat chi khi co thay doi bo current cu, set current moi
            $currentVersions = DocumentVersion::where('document_id', $documentId)
                ->where('is_current_version', true)
                ->get();

            foreach ($currentVersions as $currentVersion) {
                $currentVersion->is_current_version = false;
                $currentVersion->save();
            }

            $version->is_current_version = true;
            $version->save();

            // Ghi log
            try {
                $activity = new Activity([
                    'action' => 'restore',
                    'user_id' => auth()->id() ?? 1,
                    'action_detail' => json_encode([
                        'message' => "Khôi phục phiên bản #{$version->version_number} làm phiên bản hiện tại."
                    ], JSON_UNESCAPED_UNICODE),
                ]);
                $version->document?->activities()->save($activity);
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Lỗi khôi phục phiên bản tài liệu: ' . $e->getMessage());
                return null;
            }

            DB::commit();

            return $version;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khôi phục phiên bản tài liệu: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Xoa phien ban tai lieu
     */
    public function deleteVersion(int $documentId, int $versionId)
    {
        $version = DocumentVersion::query()
            ->select([
                'version_id',
                'version_number',
                'document_id',
                'is_current_version'
            ])
            ->with('document:document_id')
            ->where('document_id', $documentId)
            ->where('version_id', $versionId)
            ->first();

        if (!$version) {
            return null;
        }

        // Khong cho xoa phien ban hien tai
        if ($version->is_current_version) {
            return null;
        }

        try {
            DB::beginTransaction();

            // Xoa file neu ton tai
            if ($version->file_path && Storage::disk('public')->exists($version->file_path)) {
                Storage::disk('public')->delete($version->file_path);
            }

            // Xoa ban ghi
            $version->delete();

            // Ghi log
            try {
                $activity = new Activity([
                    'action' => 'delete',
                    'user_id' => auth()->id() ?? 1,
                    'action_detail' => json_encode([
                        'message' => "Đã xóa phiên bản #{$version->version_number}.",
                    ], JSON_UNESCAPED_UNICODE),
                ]);
                $version->document?->activities()->save($activity);
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Lỗi Xóa phiên bản tài liệu: ' . $e->getMessage());
                return null;
            }

            DB::commit();

            return $version;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi Xóa phiên bản tài liệu: ' . $e->getMessage());
            return null;
        }
    }
}
