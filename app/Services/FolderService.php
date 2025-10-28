<?php

namespace App\Services;

use App\Models\Folder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FolderService
{
    /**
     * Lấy danh sách thư mục với bộ lọc
     */
    public function getFoldersWithFilters(array $params): array
    {
        try {
            \Log::info('FolderService - getFoldersWithFilters called with params:', $params);

            $searchName = $params['name'] ?? null;
            $searchDate = $params['date'] ?? null;
            $filterStatus = $params['status'] ?? null;
            $parentFolderId = $params['parent_id'] ?? null;
            $perPage = $params['per_page'] ?? 10;

            $query = Folder::withCount(['childFolders', 'documents'])
                ->with(['user']);

            // Áp dụng bộ lọc
            $this->applyFilters($query, $searchName, $searchDate, $filterStatus);

            // Xác định thư mục hiện tại
            $currentFolder = $this->getCurrentFolder($parentFolderId);

            // Filter by parent folder
            if ($parentFolderId) {
                $query->where('parent_folder_id', $parentFolderId);
            } else {
                $query->whereNull('parent_folder_id');
            }

            // Phân trang và sắp xếp
            $folders = $query->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->appends($params);

            // Breadcrumbs
            $breadcrumbs = $this->getBreadcrumbs($currentFolder);

            \Log::info('FolderService - Found ' . $folders->total() . ' folders');

            return [
                'folders' => $folders,
                'currentFolder' => $currentFolder,
                'breadcrumbs' => $breadcrumbs
            ];
        } catch (\Exception $e) {
            \Log::error('FolderService - Error: ' . $e->getMessage());
            throw new \Exception('Không thể lấy danh sách thư mục: ' . $e->getMessage());
        }
    }


    /**
     * Áp dụng các bộ lọc tìm kiếm
     */
    private function applyFilters($query, $name, $date, $status): void
    {
        if ($name) {
            $query->where('name', 'like', '%' . $name . '%');
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        if ($status && in_array($status, ['public', 'private'])) {
            $query->where('status', $status);
        }
    }

    /**
     * Lấy thư mục hiện tại
     */
    private function getCurrentFolder(?string $parentFolderId): ?Folder
    {
        if (!$parentFolderId) {
            return null;
        }

        try {
            return Folder::with(['parentFolder'])->findOrFail($parentFolderId);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Thư mục không tồn tại');
        }
    }

    /**
     * Tạo breadcrumbs
     */
    public function getBreadcrumbs(?Folder $currentFolder): array
    {
        $breadcrumbs = [];

        if ($currentFolder) {
            $folder = $currentFolder;
            while ($folder) {
                $breadcrumbs[] = $folder;
                $folder = $folder->parentFolder;
            }
            $breadcrumbs = array_reverse($breadcrumbs);
        }

        return $breadcrumbs;
    }

    /**
     * Lấy thông tin vị trí thư mục
     */
    public function getFolderLocationInfo(?string $parentFolderId): array
    {
        try {
            $parentFolderName = 'Thư mục gốc';
            $breadcrumbs = [];

            // FIX: Kiểm tra parentFolderId hợp lệ
            if ($parentFolderId && $parentFolderId !== 'null' && $parentFolderId !== '') {
                $parentFolder = Folder::find($parentFolderId);
                if ($parentFolder) {
                    $parentFolderName = $parentFolder->name;
                    $breadcrumbs = $this->getBreadcrumbs($parentFolder);
                } else {
                    throw new \Exception('Thư mục cha không tồn tại');
                }
            }

            return [
                'name' => $parentFolderName,
                'breadcrumbs' => $breadcrumbs
            ];
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Thư mục cha không tồn tại');
        } catch (\Exception $e) {
            throw new \Exception('Lỗi khi lấy thông tin vị trí: ' . $e->getMessage());
        }
    }

    /**
     * Tạo thư mục mới
     */
    public function createFolder(array $data): Folder
    {
        return DB::transaction(function () use ($data) {
            try {
                return Folder::create([
                    'name' => $data['name'],
                    'status' => $data['status'],
                    'parent_folder_id' => $data['parent_folder_id'] ?? null,
                    'user_id' => Auth::id() ?? 1,
                ]);
            } catch (\Exception $e) {
                throw new \Exception('Không thể tạo thư mục: ' . $e->getMessage());
            }
        });
    }

    /**
     * Lấy dữ liệu cho form chỉnh sửa
     */
    public function getFolderForEdit(string $folderId): array
    {
        try {
            $folder = Folder::findOrFail($folderId);

            $parentFolders = Folder::whereNull('parent_folder_id')
                ->where('folder_id', '!=', $folderId)
                ->get();

            $breadcrumbs = $this->getBreadcrumbs($folder);

            return [
                'folder' => $folder,
                'parentFolders' => $parentFolders,
                'breadcrumbs' => $breadcrumbs
            ];
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Thư mục không tồn tại');
        }
    }

    /**
     * Cập nhật thư mục
     */
    public function updateFolder(string $folderId, array $data): Folder
    {
        return DB::transaction(function () use ($folderId, $data) {
            try {
                $folder = Folder::findOrFail($folderId);

                // Kiểm tra không cho phép chọn chính nó làm parent
                if (isset($data['parent_folder_id']) && $data['parent_folder_id'] == $folderId) {
                    throw new \Exception('Không thể chọn chính thư mục này làm thư mục cha!');
                }

                $folder->update([
                    'name' => $data['name'],
                    'status' => $data['status'],
                    'parent_folder_id' => $data['parent_folder_id'] ?? null,
                ]);

                return $folder;
            } catch (ModelNotFoundException $e) {
                throw new \Exception('Thư mục không tồn tại');
            }
        });
    }

    /**
     * Xóa thư mục
     */
    public function deleteFolder(string $folderId): string
    {
        return DB::transaction(function () use ($folderId) {
            try {
                $folder = Folder::withCount(['childFolders', 'documents'])->findOrFail($folderId);

                if ($folder->child_folders_count > 0) {
                    throw new \Exception('Không thể xóa thư mục có chứa thư mục con!');
                }

                if ($folder->documents_count > 0) {
                    throw new \Exception('Không thể xóa thư mục có chứa tài liệu!');
                }

                $folderName = $folder->name;
                $folder->delete();

                return $folderName;
            } catch (ModelNotFoundException $e) {
                throw new \Exception('Thư mục không tồn tại');
            }
        });
    }
}
