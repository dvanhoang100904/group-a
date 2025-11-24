<?php

namespace App\Services;

use App\Models\Folder;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class FolderService
{
    /**
     * Lấy danh sách thư mục với bộ lọc
     */
    public function getFoldersWithFilters(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 10;
        $page = $filters['page'] ?? 1;

        // Lấy user_id của người dùng đăng nhập
        $userId = Auth::id();

        if (!$userId) {
            throw new \Exception('User not authenticated');
        }

        // Bắt đầu query với điều kiện user_id
        $query = Folder::withCount(['childFolders', 'documents'])
            ->where('user_id', $userId);

        // Xử lý parent_id
        $parentId = $filters['parent_id'] ?? null;
        if ($parentId === null || $parentId === '') {
            // Nếu không có parent_id, lấy folders gốc (parent_folder_id IS NULL)
            $query->whereNull('parent_folder_id');
        } else {
            // Nếu có parent_id, lấy folders con của parent_id đó
            $query->where('parent_folder_id', $parentId);

            // Lấy thông tin folder hiện tại
            $currentFolder = Folder::where('folder_id', $parentId)
                ->where('user_id', $userId)
                ->first();
        }

        // Xử lý các bộ lọc khác
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Phân trang
        $folders = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Xây dựng breadcrumbs nếu có currentFolder
        $breadcrumbs = [];
        if (isset($currentFolder)) {
            $breadcrumbs = $this->buildBreadcrumbs($currentFolder);
        }

        return [
            'folders' => $folders,
            'currentFolder' => $currentFolder ?? null,
            'breadcrumbs' => $breadcrumbs,
        ];
    }

    /**
     * Lấy thông tin vị trí thư mục
     */
    public function getFolderLocationInfo($parentFolderId = null)
    {
        $userId = Auth::id();

        if (!$userId) {
            throw new \Exception('User not authenticated');
        }

        if ($parentFolderId) {
            $parentFolder = Folder::where('folder_id', $parentFolderId)
                ->where('user_id', $userId)
                ->first();

            if (!$parentFolder) {
                throw new \Exception('Thư mục cha không tồn tại');
            }

            $breadcrumbs = $this->buildBreadcrumbs($parentFolder);

            return [
                'name' => $parentFolder->name,
                'breadcrumbs' => $breadcrumbs,
            ];
        }

        return [
            'name' => 'Thư mục gốc',
            'breadcrumbs' => [],
        ];
    }

    /**
     * Tạo thư mục mới
     */
    public function createFolder(array $data): Folder
    {
        Log::info('FolderService - createFolder called with data:', $data);

        return DB::transaction(function () use ($data) {
            try {
                // Xử lý parent_folder_id
                $parentFolderId = $data['parent_folder_id'] ?? null;

                Log::info('Processing parent_folder_id:', ['raw' => $parentFolderId]);

                if ($parentFolderId === '' || $parentFolderId === 'null' || $parentFolderId === null) {
                    $parentFolderId = null;
                }

                // Kiểm tra user_id
                $userId = Auth::id();
                if (!$userId) {
                    throw new \Exception('User not authenticated');
                }

                $folderData = [
                    'name' => $data['name'],
                    'status' => $data['status'],
                    'parent_folder_id' => $parentFolderId,
                    'user_id' => $userId,
                ];

                Log::info('Final folder data for creation:', $folderData);

                // Tạo folder
                $folder = new Folder();
                $folder->name = $folderData['name'];
                $folder->status = $folderData['status'];
                $folder->parent_folder_id = $folderData['parent_folder_id'];
                $folder->user_id = $folderData['user_id'];
                $folder->save();

                Log::info('Folder saved successfully:', [
                    'folder_id' => $folder->folder_id,
                    'name' => $folder->name,
                    'status' => $folder->status,
                    'parent_folder_id' => $folder->parent_folder_id,
                    'user_id' => $folder->user_id
                ]);

                return $folder;
            } catch (\Exception $e) {
                Log::error('Error in createFolder transaction: ' . $e->getMessage());
                Log::error('Stack trace:', ['trace' => $e->getTraceAsString()]);
                throw new \Exception('Không thể tạo thư mục: ' . $e->getMessage());
            }
        });
    }

    /**
     * Lấy dữ liệu cho form chỉnh sửa - ĐÃ SỬA: Tối ưu hiệu suất
     */
    public function getFolderForEdit(string $folderId): array
    {
        $userId = Auth::id();

        if (!$userId) {
            throw new \Exception('User not authenticated');
        }

        try {
            // Lấy folder với user_id check
            $folder = Folder::where('folder_id', $folderId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // FIX: Sử dụng phương thức tối ưu để lấy descendant IDs
            $descendantIds = $this->getDescendantIdsOptimized($folderId);

            // FIX: Lấy parent folders với điều kiện user_id và loại trừ descendants
            $parentFolders = Folder::where('user_id', $userId)
                ->where('folder_id', '!=', $folderId)
                ->whereNotIn('folder_id', $descendantIds)
                ->get();

            // FIX: Xây dựng hierarchical folders với giới hạn
            $hierarchicalFolders = $this->buildHierarchicalFoldersOptimized($parentFolders);

            $breadcrumbs = $this->buildBreadcrumbs($folder);

            return [
                'folder' => $folder,
                'parentFolders' => $hierarchicalFolders,
                'descendantIds' => $descendantIds,
                'breadcrumbs' => $breadcrumbs
            ];
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Thư mục không tồn tại');
        } catch (\Exception $e) {
            Log::error('FolderService getFolderForEdit Error: ' . $e->getMessage());
            throw new \Exception('Lỗi khi tải thông tin thư mục: ' . $e->getMessage());
        }
    }

    /**
     * FIX: Phương thức tối ưu để lấy descendant IDs sử dụng recursive query
     */
    private function getDescendantIdsOptimized(string $folderId): array
    {
        try {
            // Sử dụng recursive CTE để lấy tất cả descendant IDs
            $descendantIds = DB::select("
                WITH RECURSIVE folder_tree AS (
                    SELECT folder_id, parent_folder_id
                    FROM folders 
                    WHERE folder_id = ?
                    
                    UNION ALL
                    
                    SELECT f.folder_id, f.parent_folder_id
                    FROM folders f
                    INNER JOIN folder_tree ft ON f.parent_folder_id = ft.folder_id
                )
                SELECT folder_id FROM folder_tree WHERE folder_id != ?
            ", [$folderId, $folderId]);

            return array_column($descendantIds, 'folder_id');
        } catch (\Exception $e) {
            Log::error('Error in getDescendantIdsOptimized: ' . $e->getMessage());
            // Fallback: trả về mảng rỗng nếu có lỗi
            return [];
        }
    }

    /**
     * FIX: Phương thức tối ưu để xây dựng hierarchical folders
     */
    private function buildHierarchicalFoldersOptimized($folders, $parentId = null, $level = 0, $maxLevel = 5): array
    {
        $hierarchical = [];

        // Giới hạn độ sâu để tránh đệ quy vô hạn
        if ($level >= $maxLevel) {
            return $hierarchical;
        }

        $children = $folders->where('parent_folder_id', $parentId);

        foreach ($children as $folder) {
            $indentedName = str_repeat('-- ', $level) . $folder->name;
            $folder->indented_name = $indentedName;

            $hierarchical[] = $folder;

            // Recursive for sub-children với giới hạn độ sâu
            $sub = $this->buildHierarchicalFoldersOptimized($folders, $folder->folder_id, $level + 1, $maxLevel);
            $hierarchical = array_merge($hierarchical, $sub);
        }

        return $hierarchical;
    }

    /**
     * Cập nhật thư mục
     */
    public function updateFolder(string $folderId, array $data): Folder
    {
        $userId = Auth::id();

        if (!$userId) {
            throw new \Exception('User not authenticated');
        }

        return DB::transaction(function () use ($folderId, $data, $userId) {
            try {
                $folder = Folder::where('folder_id', $folderId)
                    ->where('user_id', $userId)
                    ->firstOrFail();

                // Kiểm tra không cho phép chọn chính nó làm parent
                if (isset($data['parent_folder_id']) && $data['parent_folder_id'] == $folderId) {
                    throw new \Exception('Không thể chọn chính thư mục này làm thư mục cha!');
                }

                // Kiểm tra cycle prevention
                if (isset($data['parent_folder_id'])) {
                    $descendantIds = $this->getDescendantIdsOptimized($folderId);
                    if (in_array($data['parent_folder_id'], $descendantIds)) {
                        throw new \Exception('Không thể chọn thư mục con làm thư mục cha!');
                    }
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
    public function deleteFolder($folderId)
    {
        $userId = Auth::id();

        if (!$userId) {
            throw new \Exception('User not authenticated');
        }

        try {
            $folder = Folder::where('folder_id', $folderId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Kiểm tra điều kiện xóa
            if ($folder->documents()->count() > 0) {
                throw new \Exception('Không thể xóa thư mục có chứa file');
            }

            if ($folder->childFolders()->count() > 0) {
                throw new \Exception('Không thể xóa thư mục có chứa thư mục con');
            }

            $folderName = $folder->name;
            $folder->delete();

            return $folderName;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Thư mục không tồn tại');
        } catch (\Exception $e) {
            Log::error('Delete folder error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy breadcrumbs
     */
    public function getBreadcrumbs(?Folder $currentFolder): array
    {
        if (!$currentFolder) {
            return [];
        }

        return $this->buildBreadcrumbs($currentFolder);
    }
    /**
     * Lấy danh sách folders + documents (cho Home page)
     */
    public function getFoldersAndDocuments(array $params = [])
    {
        \Log::info('🔍 FolderService filters received:', $params);

        $user = Auth::user();
        $perPage = $params['per_page'] ?? 20;
        $currentFolderId = $params['parent_id'] ?? null;

        // Convert "null" string to null
        if ($currentFolderId === 'null' || $currentFolderId === '') {
            $currentFolderId = null;
        }

        $searchName = $params['name'] ?? '';
        $searchDate = $params['date'] ?? '';
        $searchStatus = $params['status'] ?? '';
        $searchFileType = $params['file_type'] ?? '';

        // ==================== PHÂN BIỆT CHẾ ĐỘ TÌM KIẾM ====================
        $isSearchMode = !empty($searchName) || !empty($searchDate) || !empty($searchFileType);

        if ($isSearchMode) {
            // 🔍 CHẾ ĐỘ TÌM KIẾM: Hiển thị FLAT LIST
            return $this->getSearchResults($user, $params, $perPage);
        } else {
            // 📁 CHẾ ĐỘ BÌNH THƯỜNG: Hiển thị TREE VIEW
            return $this->getTreeView($user, $currentFolderId, $params, $perPage);
        }
    }
    /**
     * 📁 CHẾ ĐỘ BÌNH THƯỜNG: Hiển thị dạng cây
     */
    private function getTreeView($user, $currentFolderId, $params, $perPage)
    {
        $searchName = $params['name'] ?? '';
        $searchDate = $params['date'] ?? '';
        $searchStatus = $params['status'] ?? '';
        $searchFileType = $params['file_type'] ?? '';

        // ==================== LẤY FOLDERS ====================
        $foldersQuery = Folder::where('user_id', $user->user_id)
            ->where('parent_folder_id', $currentFolderId);

        // Filter cho folders
        if ($searchFileType && $searchFileType !== 'folder') {
            $foldersQuery->whereRaw('1 = 0');
        } else {
            if ($searchName) {
                $foldersQuery->where('name', 'like', "%{$searchName}%");
            }
            if ($searchDate) {
                $foldersQuery->whereDate('created_at', $searchDate);
            }
            if ($searchStatus) {
                $foldersQuery->where('status', $searchStatus);
            }
        }

        $folders = $foldersQuery->withCount(['childFolders', 'documents'])->get();

        // ==================== LẤY DOCUMENTS ====================
        $documentsQuery = Document::with(['type', 'subject', 'tags'])
            ->where('user_id', $user->user_id)
            ->where('folder_id', $currentFolderId);

        // Filter cho documents
        if ($searchFileType === 'folder') {
            $documentsQuery->whereRaw('1 = 0');
        } else {
            if ($searchName) {
                $documentsQuery->where('title', 'like', "%{$searchName}%");
            }
            if ($searchDate) {
                $documentsQuery->whereDate('created_at', $searchDate);
            }
            if ($searchStatus) {
                $documentsQuery->where('status', $searchStatus);
            }
            if ($searchFileType && $searchFileType !== 'folder') {
                $documentsQuery->whereHas('type', function ($query) use ($searchFileType) {
                    $query->where('name', $searchFileType);
                });
            }
        }

        $documents = $documentsQuery->orderByDesc('created_at')->get();

        // Xử lý thông tin file cho documents
        foreach ($documents as $doc) {
            $this->processDocumentInfo($doc);
        }

        // ==================== GỘP FOLDERS + DOCUMENTS ====================
        $items = collect($folders)->map(function ($folder) {
            return [
                'id' => $folder->folder_id,
                'name' => $folder->name,
                'created_at' => $folder->created_at,
                'updated_at' => $folder->updated_at,
                'status' => $folder->status,
                'item_type' => 'folder',
                'child_folders_count' => $folder->child_folders_count ?? 0,
                'documents_count' => $folder->documents_count ?? 0,
                'size' => null,
                'type_name' => 'Thư mục',
                'folder_path' => $this->getFolderPath($folder), // Thêm path để hiển thị
            ];
        })->concat(
            collect($documents)->map(function ($doc) {
                return [
                    'id' => $doc->document_id,
                    'name' => $doc->title,
                    'created_at' => $doc->created_at,
                    'updated_at' => $doc->updated_at,
                    'status' => $doc->status,
                    'item_type' => 'document',
                    'size' => $doc->size,
                    'file_path' => $doc->file_path,
                    'file_name' => $doc->file_name,
                    'type_name' => $doc->type_name,
                    'description' => $doc->description,
                    'folder_path' => $this->getDocumentFolderPath($doc), // Thêm path để hiển thị
                ];
            })
        );

        // Filter theo file_type
        if ($searchFileType === 'folder') {
            $items = $items->filter(function ($item) {
                return $item['item_type'] === 'folder';
            });
        }

        // ==================== PHÂN TRANG ====================
        $page = $params['page'] ?? 1;
        $paginatedItems = $this->paginateItems($items, $perPage, $page);

        // ==================== BREADCRUMBS ====================
        $breadcrumbs = [];
        $currentFolder = null;

        if ($currentFolderId) {
            $currentFolder = Folder::with('parentFolder')->find($currentFolderId);
            if ($currentFolder) {
                $breadcrumbs = $this->buildBreadcrumbs($currentFolder);
            }
        }

        return [
            'items' => $paginatedItems,
            'currentFolder' => $currentFolder,
            'breadcrumbs' => $breadcrumbs,
            'isSearchMode' => false, // Đánh dấu không phải chế độ tìm kiếm
        ];
    }

    /**
     * 🔍 CHẾ ĐỘ TÌM KIẾM: Hiển thị FLAT LIST
     */
    private function getSearchResults($user, $params, $perPage)
    {
        $searchName = $params['name'] ?? '';
        $searchDate = $params['date'] ?? '';
        $searchStatus = $params['status'] ?? '';
        $searchFileType = $params['file_type'] ?? '';

        $allItems = collect();

        // ==================== TÌM TẤT CẢ FOLDERS PHÙ HỢP ====================
        if (!$searchFileType || $searchFileType === 'folder') {
            $foldersQuery = Folder::where('user_id', $user->user_id);

            // ✅ SỬA: Tìm kiếm theo name trong folders
            if ($searchName) {
                $foldersQuery->where(function ($query) use ($searchName) {
                    $query->where('name', 'like', "%{$searchName}%");
                });
            }

            if ($searchDate) {
                $foldersQuery->whereDate('created_at', $searchDate);
            }
            if ($searchStatus) {
                $foldersQuery->where('status', $searchStatus);
            }

            $folders = $foldersQuery->withCount(['childFolders', 'documents'])->get();

            $folderItems = $folders->map(function ($folder) {
                return [
                    'id' => $folder->folder_id,
                    'name' => $folder->name,
                    'created_at' => $folder->created_at,
                    'updated_at' => $folder->updated_at,
                    'status' => $folder->status,
                    'item_type' => 'folder',
                    'child_folders_count' => $folder->child_folders_count ?? 0,
                    'documents_count' => $folder->documents_count ?? 0,
                    'size' => null,
                    'type_name' => 'Thư mục',
                    'folder_path' => $this->getFolderPath($folder),
                    'is_search_result' => true,
                ];
            });

            $allItems = $allItems->concat($folderItems);
        }

        // ==================== TÌM TẤT CẢ DOCUMENTS PHÙ HỢP ====================
        if (!$searchFileType || $searchFileType !== 'folder') {
            $documentsQuery = Document::with(['type', 'subject', 'tags'])
                ->where('user_id', $user->user_id);

            // ✅ SỬA: Tìm kiếm theo name/title trong documents
            if ($searchName) {
                $documentsQuery->where(function ($query) use ($searchName) {
                    $query->where('title', 'like', "%{$searchName}%")
                        ->orWhere('description', 'like', "%{$searchName}%");
                });
            }

            if ($searchDate) {
                $documentsQuery->whereDate('created_at', $searchDate);
            }
            if ($searchStatus) {
                $documentsQuery->where('status', $searchStatus);
            }
            if ($searchFileType && $searchFileType !== 'folder') {
                $documentsQuery->whereHas('type', function ($query) use ($searchFileType) {
                    $query->where('name', $searchFileType);
                });
            }

            $documents = $documentsQuery->orderByDesc('created_at')->get();

            // Xử lý thông tin file cho documents
            foreach ($documents as $doc) {
                $this->processDocumentInfo($doc);
            }

            $documentItems = $documents->map(function ($doc) {
                return [
                    'id' => $doc->document_id,
                    'name' => $doc->title,
                    'created_at' => $doc->created_at,
                    'updated_at' => $doc->updated_at,
                    'status' => $doc->status,
                    'item_type' => 'document',
                    'size' => $doc->size,
                    'file_path' => $doc->file_path,
                    'file_name' => $doc->file_name,
                    'type_name' => $doc->type_name,
                    'description' => $doc->description,
                    'folder_path' => $this->getDocumentFolderPath($doc),
                    'is_search_result' => true,
                ];
            });

            $allItems = $allItems->concat($documentItems);
        }

        // ==================== SẮP XẾP THEO NGÀY TẠO ====================
        $allItems = $allItems->sortByDesc('created_at');

        // ==================== PHÂN TRANG ====================
        $page = $params['page'] ?? 1;
        $paginatedItems = $this->paginateItems($allItems, $perPage, $page);

        return [
            'items' => $paginatedItems,
            'currentFolder' => null,
            'breadcrumbs' => $this->getSearchBreadcrumbs($searchName),
            'isSearchMode' => true,
        ];
    }

    /**
     * Breadcrumbs cho chế độ tìm kiếm
     */
    private function getSearchBreadcrumbs($searchName)
    {
        return [
            [
                'folder_id' => null,
                'name' => 'Kết quả tìm kiếm: "' . $searchName . '"'
            ]
        ];
    }
    /**
     * Lấy đường dẫn thư mục cho folder
     */
    private function getFolderPath(Folder $folder)
    {
        $path = [];
        $current = $folder;
        $maxDepth = 5;
        $depth = 0;

        while ($current && $depth < $maxDepth) {
            $path[] = $current->name;
            $current = $current->parentFolder;
            $depth++;
        }

        return implode(' / ', array_reverse($path));
    }
    /**
     * Lấy đường dẫn thư mục cho document
     */
    private function getDocumentFolderPath(Document $document)
    {
        if (!$document->folder_id) {
            return 'Thư mục gốc';
        }

        $folder = Folder::find($document->folder_id);
        return $folder ? $this->getFolderPath($folder) : 'Thư mục gốc';
    }

    /**
     * Xử lý thông tin document
     */
    private function processDocumentInfo($doc)
    {
        $latestVersion = \App\Models\DocumentVersion::where('document_id', $doc->document_id)
            ->orderByDesc('version_number')
            ->first();

        if ($latestVersion) {
            $filePath = base_path('app/Public_UploadFile/' . $latestVersion->file_name);
            $doc->size = file_exists($filePath) ? filesize($filePath) : 0;
            $doc->file_name = $latestVersion->file_name;
            $doc->file_path = file_exists($filePath)
                ? asset('app/Public_UploadFile/' . $latestVersion->file_name)
                : null;
        } else {
            $doc->size = 0;
            $doc->file_name = null;
            $doc->file_path = null;
        }

        $doc->type_name = $doc->type->name ?? 'Unknown';
        $doc->item_type = 'document';
    }

    /**
     * Phân trang items
     */
    private function paginateItems($items, $perPage, $page)
    {
        $total = $items->count();
        $offset = ($page - 1) * $perPage;

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items->slice($offset, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );
    }

    /**
     * Xây dựng breadcrumbs
     */
    private function buildBreadcrumbs(Folder $folder): array
    {
        $breadcrumbs = [];
        $current = $folder;
        $maxDepth = 10;
        $depth = 0;

        while ($current && $depth < $maxDepth) {
            $breadcrumbs[] = [
                'folder_id' => $current->folder_id,
                'name' => $current->name,
            ];
            $current = $current->parentFolder;
            $depth++;
        }

        return array_reverse($breadcrumbs);
    }
}
