<?php

namespace App\Services;

use App\Models\Folder;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\FolderShare;

class FolderService
{
// Trong FolderService.php - THÊM METHODS KIỂM TRA QUYỀN

    /**
     * Kiểm tra user có quyền chỉnh sửa folder không
     */
    public function canEditFolder($folderId, $userId): bool
    {
        $folder = Folder::with('shares')->find($folderId);
        if (!$folder) return false;

        // Chủ sở hữu có toàn quyền
        if ($folder->user_id === $userId) {
            return true;
        }

        $directShare = $folder->shares->where('shared_with_id', $userId)->first();
        if ($directShare) {
            return false;
        }

        if ($folder->parent_folder_id) {
            $parentFolder = Folder::with('shares')->find($folder->parent_folder_id);
            if ($parentFolder) {
                $parentShare = $parentFolder->shares->where('shared_with_id', $userId)->first();
                // ✅ Chỉ được sửa nếu folder CHA được share với quyền edit
                return $parentShare && $parentShare->permission === 'edit';
            }
        }

        return false;
    }

    /**
     * Kiểm tra user có quyền CHỈNH SỬA folder ĐƯỢC SHARE (folder cha)
     */
    public function canEditSharedFolder($folderId, $userId): bool
    {
        $folder = Folder::with('shares')->find($folderId);
        if (!$folder) return false;

        // Chủ sở hữu có toàn quyền
        if ($folder->user_id === $userId) {
            return true;
        }

        // ✅ SỬA: CHỈ được sửa folder được share nếu có quyền edit TRỰC TIẾP
        $directShare = $folder->shares->where('shared_with_id', $userId)->first();
        return $directShare && $directShare->permission === 'edit';
    }

    /**
     * Kiểm tra user có quyền XEM folder
     */
    public function canViewFolder($folderId, $userId): bool
    {
        $folder = Folder::with('shares')->find($folderId);
        if (!$folder) return false;

        // Chủ sở hữu có toàn quyền
        if ($folder->user_id === $userId) {
            return true;
        }

        // Kiểm tra chia sẻ TRỰC TIẾP (view hoặc edit)
        $directShare = $folder->shares->where('shared_with_id', $userId)->first();
        if ($directShare) {
            return in_array($directShare->permission, ['view', 'edit']);
        }

        // Kiểm tra kế thừa từ folder cha
        return $this->checkParentFolderViewAccess($folder, $userId);
    }

    /**
     * Kiểm tra quyền kế thừa từ folder cha
     */
    private function checkParentFolderViewAccess(Folder $folder, $userId, $depth = 0): bool
    {
        if ($depth > 5) return false;

        if (!$folder->parent_folder_id) {
            return false;
        }

        $parentFolder = Folder::with('shares')->find($folder->parent_folder_id);
        if (!$parentFolder) {
            return false;
        }

        // Kiểm tra parent folder có được chia sẻ không
        $parentShare = $parentFolder->shares->where('shared_with_id', $userId)->first();
        if ($parentShare) {
            return in_array($parentShare->permission, ['view', 'edit']);
        }

        // Đệ quy kiểm tra lên cấp cao hơn
        return $this->checkParentFolderViewAccess($parentFolder, $userId, $depth + 1);
    }

    /**
     * Validate và sanitize folder ID
     */
    private function validateFolderId($folderId): int
    {
        if (!is_numeric($folderId) || $folderId <= 0) {
            throw new \Exception('ID thư mục không hợp lệ');
        }

        return (int)$folderId;
    }

    /**
     * Validate và sanitize input parameters    
     */
    private function validateSearchParams(array $params): array
    {
        $validator = Validator::make($params, [
            'name' => 'nullable|string|max:255',
            'date' => 'nullable|date_format:Y-m-d',
            'file_type' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|min:0',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        // Sanitize inputs
        if (isset($validated['name'])) {
            $validated['name'] = $this->sanitizeInput($validated['name']);
        }

        if (isset($validated['file_type'])) {
            $validated['file_type'] = $this->sanitizeInput($validated['file_type']);
        }

        return $validated;
    }

    /**
     * Sanitize input để tránh XSS
     */
    private function sanitizeInput(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape output để tránh XSS - FIXED: Cho phép null
     */
    private function escapeOutput(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Lấy danh sách thư mục với bộ lọc (ĐÃ BẢO MẬT)
     */
    public function getFoldersWithFilters(array $filters = [])
    {
        try {
            // Validate input
            $validatedFilters = $this->validateSearchParams($filters);
        } catch (ValidationException $e) {
            throw new \Exception('Tham số tìm kiếm không hợp lệ: ' . $e->getMessage());
        }

        $perPage = $validatedFilters['per_page'] ?? 10;
        $page = $validatedFilters['page'] ?? 1;

        // Lấy user_id của người dùng đăng nhập
        $userId = Auth::id();
        if (!$userId) {
            throw new \Exception('User not authenticated');
        }

        // Bắt đầu query với điều kiện user_id
        $query = Folder::withCount(['childFolders', 'documents'])
            ->where('user_id', $userId);

        // Xử lý parent_id với validation
        $parentId = $validatedFilters['parent_id'] ?? null;
        if ($parentId !== null) {
            $parentId = $this->validateFolderId($parentId);
            $query->where('parent_folder_id', $parentId);

            // Lấy thông tin folder hiện tại
            $currentFolder = Folder::where('folder_id', $parentId)
                ->where('user_id', $userId)
                ->first();
        } else {
            // Nếu không có parent_id, lấy folders gốc
            $query->whereNull('parent_folder_id');
        }

        // Xử lý các bộ lọc khác với prepared statements
        if (!empty($validatedFilters['name'])) {
            $query->where('name', 'LIKE', '%' . $validatedFilters['name'] . '%');
        }

        if (!empty($validatedFilters['date'])) {
            $query->whereDate('created_at', $validatedFilters['date']);
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
     * Lấy thông tin vị trí thư mục (ĐÃ BẢO MẬT)
     */
    public function getFolderLocationInfo($parentFolderId = null)
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new \Exception('User not authenticated');
        }

        if ($parentFolderId) {
            $parentFolderId = $this->validateFolderId($parentFolderId);

            $parentFolder = Folder::where('folder_id', $parentFolderId)
                ->where('user_id', $userId)
                ->first();

            if (!$parentFolder) {
                throw new \Exception('Thư mục cha không tồn tại');
            }

            $breadcrumbs = $this->buildBreadcrumbs($parentFolder);

            return [
                'name' => $this->escapeOutput($parentFolder->name),
                'breadcrumbs' => $breadcrumbs,
            ];
        }

        return [
            'name' => 'Thư mục gốc',
            'breadcrumbs' => [],
        ];
    }
    /**
     * Tạo thư mục mới - ĐÃ CẬP NHẬT CHO SHARE
     */
    public function createFolder(array $data): Folder
    {
        Log::info('FolderService - createFolder called with data:', $data);

        return DB::transaction(function () use ($data) {
            try {
                $validator = Validator::make($data, [
                    'name' => 'required|string|max:255',
                    'parent_folder_id' => 'nullable|integer|min:0'
                ]);

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                $validatedData = $validator->validated();
                $validatedData['name'] = $this->sanitizeInput($validatedData['name']);

                $parentFolderId = $validatedData['parent_folder_id'] ?? null;
                Log::info('Processing parent_folder_id:', ['raw' => $parentFolderId]);

                $userId = Auth::id();
                if (!$userId) {
                    throw new \Exception('User not authenticated');
                }

                $parentFolder = null;

                if ($parentFolderId !== null && $parentFolderId !== '') {
                    $parentFolderId = $this->validateFolderId($parentFolderId);

                    // Kiểm tra user có quyền tạo folder trong folder này không
                    $parentFolder = Folder::accessibleByWithInheritance($userId)
                        ->where('folder_id', $parentFolderId)
                        ->first();

                    if (!$parentFolder) {
                        throw new \Exception('Thư mục cha không tồn tại hoặc không có quyền truy cập');
                    }

                    // Kiểm tra quyền edit (phải có quyền edit mới được tạo folder con)
                    if (!$parentFolder->canUserEditContent($userId)) {
                        throw new \Exception('Bạn không có quyền tạo thư mục trong thư mục này');
                    }
                }

                // ✅ QUAN TRỌNG: Folder mới luôn thuộc về user tạo ra nó
                $folderData = [
                    'name' => $validatedData['name'],
                    'parent_folder_id' => $parentFolderId,
                    'user_id' => $userId, // ✅ User tạo là owner của folder con
                ];

                Log::info('Final folder data for creation:', $folderData);

                $folder = new Folder();
                $folder->name = $folderData['name'];
                $folder->parent_folder_id = $folderData['parent_folder_id'];
                $folder->user_id = $folderData['user_id'];
                $folder->save();

                Log::info('Folder saved successfully:', [
                    'folder_id' => $folder->folder_id,
                    'name' => $folder->name,
                    'parent_folder_id' => $folder->parent_folder_id,
                    'user_id' => $folder->user_id
                ]);

                return $folder;
            } catch (ValidationException $e) {
                throw $e;
            } catch (\Exception $e) {
                Log::error('Error in createFolder transaction: ' . $e->getMessage());
                throw new \Exception('Không thể tạo thư mục: ' . $e->getMessage());
            }
        });
    }

    /**
     * PHƯƠNG THỨC AN TOÀN: Lấy descendant IDs sử dụng Eloquent (không dùng raw SQL)
     */
    private function getDescendantIdsSecure(string $folderId, int $userId): array
    {
        try {
            // Sử dụng Eloquent thay vì raw SQL để tránh SQL Injection
            $descendants = collect();
            $currentLevel = Folder::where('parent_folder_id', $folderId)
                ->where('user_id', $userId)
                ->get();

            $maxDepth = 10;
            $depth = 0;

            while ($currentLevel->isNotEmpty() && $depth < $maxDepth) {
                $descendants = $descendants->merge($currentLevel);

                $currentLevelIds = $currentLevel->pluck('folder_id')->toArray();
                $currentLevel = Folder::whereIn('parent_folder_id', $currentLevelIds)
                    ->where('user_id', $userId)
                    ->get();

                $depth++;
            }

            return $descendants->pluck('folder_id')->toArray();
        } catch (\Exception $e) {
            Log::error('Error in getDescendantIdsSecure: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * PHƯƠNG THỨC AN TOÀN: Xây dựng hierarchical folders
     */
    private function buildHierarchicalFoldersSecure($folders, $parentId = null, $level = 0, $maxLevel = 5): array
    {
        $hierarchical = [];

        // Giới hạn độ sâu để tránh đệ quy vô hạn
        if ($level >= $maxLevel) {
            return $hierarchical;
        }

        $children = $folders->where('parent_folder_id', $parentId);

        foreach ($children as $folder) {
            // Escape output để tránh XSS
            $indentedName = str_repeat('-- ', $level) . $this->escapeOutput($folder->name);
            $folder->indented_name = $indentedName;

            $hierarchical[] = $folder;

            // Recursive for sub-children với giới hạn độ sâu
            $sub = $this->buildHierarchicalFoldersSecure($folders, $folder->folder_id, $level + 1, $maxLevel);
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
                $validator = Validator::make($data, [
                    'name' => 'required|string|max:255',
                    'parent_folder_id' => 'nullable|integer|min:1'
                ]);

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                $validatedData = $validator->validated();
                $validatedData['name'] = $this->sanitizeInput($validatedData['name']);
                $folderId = $this->validateFolderId($folderId);

                // ✅ SỬA: Kiểm tra quyền CHỈNH SỬA THÔNG TIN
                $permission = $this->getUserFolderPermission($folderId, $userId);

                if (!$permission['can_edit_info']) {
                    throw new \Exception('Bạn không có quyền chỉnh sửa thông tin thư mục này');
                }

                $folder = Folder::where('folder_id', $folderId)->firstOrFail();

                // Kiểm tra không cho phép chọn chính nó làm parent
                if (isset($validatedData['parent_folder_id']) && $validatedData['parent_folder_id'] == $folderId) {
                    throw new \Exception('Không thể chọn chính thư mục này làm thư mục cha!');
                }

                // Kiểm tra cycle prevention
                if (isset($validatedData['parent_folder_id'])) {
                    $descendantIds = $this->getDescendantIdsSecure($folderId, $userId);
                    if (in_array($validatedData['parent_folder_id'], $descendantIds)) {
                        throw new \Exception('Không thể chọn thư mục con làm thư mục cha!');
                    }

                    // Verify parent folder exists and user has access
                    if ($validatedData['parent_folder_id']) {
                        $parentFolder = Folder::accessibleBy($userId)
                            ->where('folder_id', $validatedData['parent_folder_id'])
                            ->first();

                        if (!$parentFolder) {
                            throw new \Exception('Thư mục cha không tồn tại hoặc không có quyền truy cập');
                        }
                    }
                }

                $folder->update([
                    'name' => $validatedData['name'],
                    'parent_folder_id' => $validatedData['parent_folder_id'] ?? null,
                ]);

                return $folder;
            } catch (ModelNotFoundException $e) {
                throw new \Exception('Thư mục không tồn tại');
            } catch (ValidationException $e) {
                throw $e;
            }
        });
    }

    /**
     * Xóa thư mục - ĐÃ BẢO MẬT VÀ HỖ TRỢ SHARE
     */
    public function deleteFolder($folderId)
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new \Exception('User not authenticated');
        }

        try {
            $folderId = $this->validateFolderId($folderId);

            // ✅ SỬA: Sử dụng scope AccessibleBy thay vì chỉ tìm theo user_id
            $folder = Folder::accessibleBy($userId)
                ->where('folder_id', $folderId)
                ->first();

            if (!$folder) {
                throw new \Exception('Thư mục không tồn tại hoặc bạn không có quyền truy cập');
            }

            // ✅ SỬA: Kiểm tra quyền XÓA thay vì chỉ kiểm tra owner
            $canDelete = $this->canDeleteFolder($folderId, $userId);
            if (!$canDelete) {
                throw new \Exception('Bạn không có quyền xóa thư mục này');
            }

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
     * Lấy danh sách folders + documents (cho Home page) - ĐÃ BẢO MẬT
     */
    public function getFoldersAndDocuments(array $params = [])
    {
        try {
            // Validate input parameters
            $validatedParams = $this->validateSearchParams($params);
        } catch (ValidationException $e) {
            throw new \Exception('Tham số không hợp lệ: ' . $e->getMessage());
        }
        $user = Auth::user();
        $perPage = $validatedParams['per_page'] ?? 20;
        $currentFolderId = $validatedParams['parent_id'] ?? null;

        // Convert "null" string to null
        if ($currentFolderId === 'null' || $currentFolderId === '') {
            $currentFolderId = null;
        } else if ($currentFolderId) {
            $currentFolderId = $this->validateFolderId($currentFolderId);
        }

        $searchName = $validatedParams['name'] ?? '';
        $searchDate = $validatedParams['date'] ?? '';
        $searchFileType = $validatedParams['file_type'] ?? '';

        // Phân biệt chế độ tìm kiếm
        $isSearchMode = !empty($searchName) || !empty($searchDate) || !empty($searchFileType);

        if ($isSearchMode) {
            return $this->getSearchResults($user, $validatedParams, $perPage);
        } else {
            return $this->getTreeView($user, $currentFolderId, $validatedParams, $perPage);
        }
    }

   // App\Services\FolderService.php

    /**
     * 📁 CHẾ ĐỘ BÌNH THƯỜNG: Hiển thị dạng cây - ĐÃ CẬP NHẬT HỖ TRỢ KẾ THỪA
     */
    private function getTreeView($user, $currentFolderId, $params, $perPage)
    {
        $searchName = $params['name'] ?? '';
        $searchDate = $params['date'] ?? '';
        $searchFileType = $params['file_type'] ?? '';

        // ==================== LẤY FOLDERS (SỞ HỮU + ĐƯỢC CHIA SẺ + KẾ THỪA) ====================
        $foldersQuery = Folder::accessibleByWithInheritance($user->user_id)
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
        }

        $folders = $foldersQuery->withCount(['childFolders', 'documents'])
            ->with(['shares' => function ($query) use ($user) {
                $query->where('shared_with_id', $user->user_id);
            }])
            ->with(['parentFolder.shares' => function ($query) use ($user) {
                $query->where('shared_with_id', $user->user_id);
            }])
            ->get();

        // ==================== LẤY DOCUMENTS ====================
        $documentsQuery = Document::with(['type', 'subject', 'tags'])
            ->where('folder_id', $currentFolderId)
            ->where(function ($query) use ($user) {
                // Documents của user hoặc trong folder được chia sẻ kế thừa
                $query->where('user_id', $user->user_id)
                    ->orWhereHas('folder', function ($folderQuery) use ($user) {
                        $folderQuery->accessibleByWithInheritance($user->user_id);
                    });
            });

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
        $folderItems = $folders->map(function ($folder) use ($user) {
            $isOwner = $folder->user_id === $user->user_id;

            // ✅ QUAN TRỌNG: Kiểm tra quyền kế thừa
            $canEditContent = $folder->canUserEditContent($user->user_id);
            $isInherited = !$isOwner && $folder->user_id != $user->user_id;

            $shareInfo = null;
            $userPermission = 'view';

            if ($canEditContent) {
                $userPermission = 'edit';
            }

            if (!$isOwner) {
                // Tìm share trực tiếp
                $share = $folder->shares->first();
                if (!$share && $folder->parentFolder) {
                    // Tìm share từ parent (kế thừa)
                    $share = $folder->parentFolder->shares->first();
                }

                if ($share) {
                    $shareInfo = [
                        'shared_by' => $share->owner->name ?? 'Unknown',
                        'permission' => $share->permission,
                        'shared_at' => $share->created_at,
                        'is_inherited' => !$folder->shares->contains('shared_with_id', $user->user_id)
                    ];
                }
            }

            return [
                'id' => $folder->folder_id,
                'name' => $this->escapeOutput($folder->name),
                'created_at' => $folder->created_at,
                'updated_at' => $folder->updated_at,
                'item_type' => 'folder',
                'child_folders_count' => $folder->child_folders_count ?? 0,
                'documents_count' => $folder->documents_count ?? 0,
                'size' => null,
                'type_name' => 'Thư mục',
                'folder_path' => $this->getFolderPath($folder),
                'is_owner' => $isOwner,
                'shared_info' => $shareInfo,
                'user_permission' => $userPermission,
                'is_shared_folder' => $isInherited || ($shareInfo !== null),
                'can_edit_content' => $canEditContent,
                'can_edit_info' => $isOwner, // Chỉ owner mới được sửa thông tin folder
                'can_delete' => $isOwner, // Chỉ owner mới được xóa folder
                'can_create_subfolder' => $canEditContent, // Được tạo folder con nếu có quyền edit
                'owner_name' => $folder->user->name ?? 'Unknown',
                'is_inherited' => $isInherited
            ];
        });

        $documentItems = collect($documents)->map(function ($doc) use ($user) {
            $isOwner = $doc->user_id === $user->user_id;

            // Kiểm tra quyền edit document (chỉ owner)
            $canEditDocument = $isOwner;

            return [
                'id' => $doc->document_id,
                'name' => $this->escapeOutput($doc->title),
                'created_at' => $doc->created_at,
                'updated_at' => $doc->updated_at,
                'item_type' => 'document',
                'size' => $doc->size,
                'file_path' => $doc->file_path,
                'file_name' => $this->escapeOutput($doc->file_name ?? ''),
                'type_name' => $this->escapeOutput($doc->type_name ?? 'Unknown'),
                'description' => $this->escapeOutput($doc->description ?? ''),
                'folder_path' => $this->getDocumentFolderPath($doc),
                'is_owner' => $isOwner,
                'owner_name' => $doc->user->name ?? 'Unknown',
                'can_edit' => $canEditDocument,
                'can_delete' => $canEditDocument
            ];
        });

        // ✅ SỬA: Gộp folderItems và documentItems thành items
        $items = $folderItems->concat($documentItems);

        // ✅ SỬA: Filter theo file_type - sử dụng biến $items
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
            $currentFolder = Folder::accessibleByWithInheritance($user->user_id)->find($currentFolderId);
            if ($currentFolder) {
                $breadcrumbs = $this->buildBreadcrumbs($currentFolder);
            }
        }

        return [
            'items' => $paginatedItems,
            'currentFolder' => $currentFolder,
            'breadcrumbs' => $breadcrumbs,
            'isSearchMode' => false,
        ];
    }
    /**
     * 🔍 CHẾ ĐỘ TÌM KIẾM: Hiển thị FLAT LIST - ĐÃ CẬP NHẬT HỖ TRỢ SHARE
     */
    private function getSearchResults($user, $params, $perPage)
    {
        $searchName = $params['name'] ?? '';
        $searchDate = $params['date'] ?? '';
        $searchFileType = $params['file_type'] ?? '';

        $allItems = collect();

        // ==================== TÌM TẤT CẢ FOLDERS PHÙ HỢP (SỞ HỮU + ĐƯỢC CHIA SẺ) ====================
        if (!$searchFileType || $searchFileType === 'folder') {
            $foldersQuery = Folder::accessibleBy($user->user_id);

            if ($searchName) {
                $foldersQuery->where(function ($query) use ($searchName) {
                    $query->where('name', 'like', "%{$searchName}%");
                });
            }

            if ($searchDate) {
                $foldersQuery->whereDate('created_at', $searchDate);
            }

            $folders = $foldersQuery->withCount(['childFolders', 'documents'])
                ->with(['shares' => function ($query) use ($user) {
                    $query->where('shared_with_id', $user->user_id);
                }])
                ->get();

            $folderItems = $folders->map(function ($folder) use ($user) {
                $isOwner = $folder->user_id === $user->user_id;
                $shareInfo = null;
                $userPermission = 'view';

                if (!$isOwner) {
                    $share = $folder->shares->first();
                    if ($share) {
                        $shareInfo = [
                            'shared_by' => $share->owner->name ?? 'Unknown',
                            'permission' => $share->permission,
                            'shared_at' => $share->created_at
                        ];
                        $userPermission = $share->permission;
                    }
                }

                // ✅ THÊM: Sử dụng getUserFolderPermission để lấy thông tin quyền đầy đủ
                $permission = $this->getUserFolderPermission($folder->folder_id, $user->user_id);

                return [
                    'id' => $folder->folder_id,
                    'name' => $this->escapeOutput($folder->name),
                    'created_at' => $folder->created_at,
                    'updated_at' => $folder->updated_at,
                    'item_type' => 'folder',
                    'child_folders_count' => $folder->child_folders_count ?? 0,
                    'documents_count' => $folder->documents_count ?? 0,
                    'size' => null,
                    'type_name' => 'Thư mục',
                    'folder_path' => $this->getFolderPath($folder),
                    'is_search_result' => true,
                    'is_owner' => $isOwner,
                    'shared_info' => $shareInfo,
                    'user_permission' => $userPermission,
                    'is_shared_folder' => $permission['is_shared_folder'], // ✅ THÊM
                    'can_edit_content' => $permission['can_edit_content'],  // ✅ THÊM
                    'can_edit_info' => $permission['can_edit_info'],        // ✅ THÊM
                    'can_delete' => $permission['can_delete'],              // ✅ THÊM
                    'owner_name' => $folder->user->name ?? 'Unknown'
                ];
            });

            $allItems = $allItems->concat($folderItems);
        }

        // ==================== TÌM TẤT CẢ DOCUMENTS PHÙ HỢP ====================
        if (!$searchFileType || $searchFileType !== 'folder') {
            $documentsQuery = Document::with(['type', 'subject', 'tags'])
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->user_id)
                        ->orWhereHas('folder', function ($folderQuery) use ($user) {
                            $folderQuery->accessibleBy($user->user_id);
                        });
                });

            if ($searchName) {
                $documentsQuery->where(function ($query) use ($searchName) {
                    $query->where('title', 'like', "%{$searchName}%")
                        ->orWhere('description', 'like', "%{$searchName}%");
                });
            }

            if ($searchDate) {
                $documentsQuery->whereDate('created_at', $searchDate);
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

            $documentItems = $documents->map(function ($doc) use ($user) {
                $isOwner = $doc->user_id === $user->user_id;

                return [
                    'id' => $doc->document_id,
                    'name' => $this->escapeOutput($doc->title),
                    'created_at' => $doc->created_at,
                    'updated_at' => $doc->updated_at,
                    'item_type' => 'document',
                    'size' => $doc->size,
                    'file_path' => $doc->file_path,
                    'file_name' => $this->escapeOutput($doc->file_name ?? ''),
                    'type_name' => $this->escapeOutput($doc->type_name ?? 'Unknown'),
                    'description' => $this->escapeOutput($doc->description ?? ''),
                    'folder_path' => $this->getDocumentFolderPath($doc),
                    'is_search_result' => true,
                    'is_owner' => $isOwner,
                    'owner_name' => $doc->user->name ?? 'Unknown'
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
     * Lấy dữ liệu cho form chỉnh sửa - ĐÃ SỬA
     */
    public function getFolderForEdit(string $folderId): array
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new \Exception('User not authenticated');
        }

        try {
            $folderId = $this->validateFolderId($folderId);

            // ✅ SỬA: Kiểm tra quyền CHỈNH SỬA THÔNG TIN
            $permission = $this->getUserFolderPermission($folderId, $userId);

            if (!$permission['can_edit_info']) {
                throw new \Exception('Bạn không có quyền chỉnh sửa thông tin thư mục này');
            }

            // Lấy folder
            $folder = Folder::accessibleBy($userId)->findOrFail($folderId);

            // Sử dụng phương thức an toàn để lấy descendant IDs
            $descendantIds = $this->getDescendantIdsSecure($folderId, $userId);

            // Lấy parent folders với điều kiện user có quyền truy cập
            $parentFolders = Folder::accessibleBy($userId)
                ->where('folder_id', '!=', $folderId)
                ->whereNotIn('folder_id', $descendantIds)
                ->get();

            $hierarchicalFolders = $this->buildHierarchicalFoldersSecure($parentFolders);
            $breadcrumbs = $this->buildBreadcrumbs($folder);

            return [
                'folder' => $folder,
                'parentFolders' => $hierarchicalFolders,
                'descendantIds' => $descendantIds,
                'breadcrumbs' => $breadcrumbs,
                'user_permission' => $permission // ✅ THÊM thông tin quyền
            ];
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Thư mục không tồn tại');
        } catch (\Exception $e) {
            Log::error('FolderService getFolderForEdit Error: ' . $e->getMessage());
            throw new \Exception('Lỗi khi tải thông tin thư mục: ' . $e->getMessage());
        }
    }

    /**
     * Breadcrumbs cho chế độ tìm kiếm
     */
    private function getSearchBreadcrumbs($searchName)
    {
        return [
            [
                'folder_id' => null,
                'name' => 'Kết quả tìm kiếm: "' . $this->escapeOutput($searchName) . '"'
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
            $path[] = $this->escapeOutput($current->name);
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
                'name' => $this->escapeOutput($current->name),
            ];
            $current = $current->parentFolder;
            $depth++;
        }

        return array_reverse($breadcrumbs);
    }

    /**
     * Kiểm tra user có quyền truy cập folder thông qua kế thừa
     */
    public function canAccessFolderThroughInheritance($folderId, $userId): bool
    {
        $folder = Folder::find($folderId);
        if (!$folder) return false;

        // Kiểm tra chủ sở hữu
        if ($folder->user_id === $userId) {
            return true;
        }

        // Kiểm tra chia sẻ trực tiếp
        if ($folder->shares()->where('shared_with_id', $userId)->exists()) {
            return true;
        }

        // Kiểm tra kế thừa từ folder cha
        return $this->checkParentFolderAccess($folder, $userId);
    }

    /**
     * Kiểm tra đệ quy quyền truy cập từ folder cha
     */
    private function checkParentFolderAccess(Folder $folder, $userId, $depth = 0): bool
    {
        if ($depth > 10) return false; // Giới hạn độ sâu

        if (!$folder->parent_folder_id) {
            return false;
        }

        $parentFolder = Folder::find($folder->parent_folder_id);
        if (!$parentFolder) {
            return false;
        }

        // Kiểm tra parent folder có được chia sẻ không
        if ($parentFolder->shares()->where('shared_with_id', $userId)->exists()) {
            return true;
        }

        // Đệ quy kiểm tra lên cấp cao hơn
        return $this->checkParentFolderAccess($parentFolder, $userId, $depth + 1);
    }

    /**
     * Kiểm tra user có quyền xem folder và nội dung bên trong
     */
    public function canViewFolderContent($folderId, $userId): bool
    {
        return $this->canAccessFolderThroughInheritance($folderId, $userId);
    }

    /**
     * Kiểm tra user có quyền xem document
     */
    public function canViewDocument($documentId, $userId): bool
    {
        $document = Document::find($documentId);
        if (!$document) return false;

        // Document của chính user
        if ($document->user_id === $userId) {
            return true;
        }

        // Document trong folder mà user có quyền truy cập
        if ($document->folder_id) {
            return $this->canAccessFolderThroughInheritance($document->folder_id, $userId);
        }

        return false;
    }
    /**
     * Kiểm tra user có quyền chỉnh sửa NỘI DUNG folder (không phải folder gốc)
     */
    public function canEditFolderContent($folderId, $userId): bool
    {
        $folder = Folder::with('shares')->find($folderId);
        if (!$folder) return false;

        // Chủ sở hữu có toàn quyền
        if ($folder->user_id === $userId) {
            return true;
        }

        // ✅ Kiểm tra chia sẻ TRỰC TIẾP với quyền edit
        $directShare = $folder->shares->where('shared_with_id', $userId)->first();
        if ($directShare && $directShare->permission === 'edit') {
            return true; // ✅ Được sửa nội dung folder được share
        }

        // ✅ Kiểm tra kế thừa từ folder cha
        if ($folder->parent_folder_id) {
            $parentFolder = Folder::with('shares')->find($folder->parent_folder_id);
            if ($parentFolder) {
                $parentShare = $parentFolder->shares->where('shared_with_id', $userId)->first();
                return $parentShare && $parentShare->permission === 'edit';
            }
        }

        return false;
    }

    /**
     * Kiểm tra user có quyền chỉnh sửa THÔNG TIN folder (tên, parent)
     */
    public function canEditFolderInfo($folderId, $userId): bool
    {
        $folder = Folder::with('shares')->find($folderId);
        if (!$folder) return false;

        // Chủ sở hữu có toàn quyền
        if ($folder->user_id === $userId) {
            return true;
        }

        // ✅ NGƯỜI ĐƯỢC CHIA SẺ: KHÔNG được sửa thông tin folder được share
        $directShare = $folder->shares->where('shared_with_id', $userId)->first();
        if ($directShare) {
            return false; // ❌ KHÔNG được sửa tên folder được share
        }

        // ✅ Chỉ được sửa folder con BÊN TRONG folder được share
        if ($folder->parent_folder_id) {
            $parentFolder = Folder::with('shares')->find($folder->parent_folder_id);
            if ($parentFolder) {
                $parentShare = $parentFolder->shares->where('shared_with_id', $userId)->first();
                return $parentShare && $parentShare->permission === 'edit';
            }
        }

        return false;
    }

    /**
     * Kiểm tra user có quyền xóa folder
     */
    public function canDeleteFolder($folderId, $userId): bool
    {
        $folder = Folder::with('shares')->find($folderId);
        if (!$folder) return false;

        // Chủ sở hữu có toàn quyền
        if ($folder->user_id === $userId) {
            return true;
        }

        // ✅ NGƯỜI ĐƯỢC CHIA SẺ: KHÔNG được xóa folder được share TRỰC TIẾP
        $directShare = $folder->shares->where('shared_with_id', $userId)->first();
        if ($directShare) {
            return false; // ❌ KHÔNG được xóa folder được chia sẻ trực tiếp
        }

        // ✅ Được xóa folder con BÊN TRONG folder được share (nếu có quyền edit)
        if ($folder->parent_folder_id) {
            $parentFolder = Folder::with('shares')->find($folder->parent_folder_id);
            if ($parentFolder) {
                $parentShare = $parentFolder->shares->where('shared_with_id', $userId)->first();
                return $parentShare && $parentShare->permission === 'edit';
            }
        }

        return false;
    }

    /**
     * Kiểm tra user có quyền tạo folder con
     */
    public function canCreateFolderIn($parentFolderId, $userId): bool
    {
        // ✅ SỬA: Tạo folder gốc - CHỈ chủ sở hữu của folder đó
        if (!$parentFolderId || $parentFolderId === 0) {
            return true; // 
        }

        $parentFolder = Folder::with('shares')->find($parentFolderId);
        if (!$parentFolder) return false;

        // Chủ sở hữu có toàn quyền
        if ($parentFolder->user_id === $userId) {
            return true;
        }

        // ✅ Được tạo folder con nếu folder cha được share với quyền edit
        $parentShare = $parentFolder->shares->where('shared_with_id', $userId)->first();
        return $parentShare && $parentShare->permission === 'edit';
    }

    /**
     * Cập nhật phương thức getUserFolderPermission
     */
    public function getUserFolderPermission($folderId, $userId): array
    {
        $folder = Folder::with('shares')->find($folderId);
        if (!$folder) {
            return ['can_view' => false, 'can_edit_content' => false, 'can_edit_info' => false, 'can_delete' => false, 'can_create_subfolder' => false, 'is_owner' => false, 'is_shared_folder' => false];
        }

        $isOwner = $folder->user_id === $userId;
        $directShare = $folder->shares->where('shared_with_id', $userId)->first();
        $isSharedFolder = !$isOwner && $directShare; // Đây có phải folder được share trực tiếp không

        $canEditContent = false;
        $canEditInfo = false;
        $canDelete = false;
        $canCreateSubfolder = false;

        if ($isOwner) {
            // Chủ sở hữu có toàn quyền
            $canEditContent = true;
            $canEditInfo = true;
            $canDelete = true;
            $canCreateSubfolder = true;
        } elseif ($isSharedFolder) {
            // Folder được share trực tiếp
            if ($directShare->permission === 'edit') {
                $canEditContent = true;
                $canEditInfo = false;
                $canDelete = false;
                $canCreateSubfolder = true;
            } else {
                $canEditContent = false; // Chỉ xem
                $canEditInfo = false;
                $canDelete = false;
                $canCreateSubfolder = false;
            }
        } else {
            // Folder con bên trong folder được share
            if ($folder->parent_folder_id) {
                $parentFolder = Folder::with('shares')->find($folder->parent_folder_id);
                if ($parentFolder) {
                    $parentShare = $parentFolder->shares->where('shared_with_id', $userId)->first();
                    if ($parentShare && $parentShare->permission === 'edit') {
                        $canEditContent = true;
                        $canEditInfo = true;
                        $canDelete = true;
                        $canCreateSubfolder = true;
                    }
                }
            }
        }

        return [
            'can_view' => $isOwner || $directShare || $canEditContent,
            'can_edit_content' => $canEditContent,  // Quyền sửa nội dung
            'can_edit_info' => $canEditInfo,        // Quyền sửa thông tin (tên, parent)
            'can_delete' => $canDelete,
            'can_create_subfolder' => $canCreateSubfolder,
            'is_owner' => $isOwner,
            'is_shared_folder' => $isSharedFolder,
            'permission' => $directShare ? $directShare->permission : null,
            'user_permission' => $directShare ? $directShare->permission : ($canEditContent ? 'edit' : 'view')
        ];
    }
}
