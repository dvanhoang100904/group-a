<?php

namespace App\Services\Folders;

use App\Models\Folder;
use App\Models\Document;

class ShareService
{
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
                // Chỉ được sửa nếu folder CHA được share với quyền edit
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

        // Kiểm tra chia sẻ (view hoặc edit)
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

        return $this->checkParentFolderViewAccess($parentFolder, $userId, $depth + 1);
    }

    /**
     * Kiểm tra user có quyền truy cập folder thông qua kế thừa
     */
    public function canAccessFolderThroughInheritance($folderId, $userId): bool
    {
        $folder = Folder::find($folderId);
        if (!$folder) return false;

        if ($folder->user_id === $userId) {
            return true;
        }
        if ($folder->shares()->where('shared_with_id', $userId)->exists()) {
            return true;
        }
        return $this->checkParentFolderAccess($folder, $userId);
    }

    /**
     * Kiểm tra đệ quy quyền truy cập từ folder cha
     */
    private function checkParentFolderAccess(Folder $folder, $userId, $depth = 0): bool
    {
        if ($depth > 10) return false;

        if (!$folder->parent_folder_id) {
            return false;
        }

        $parentFolder = Folder::find($folder->parent_folder_id);
        if (!$parentFolder) {
            return false;
        }
        if ($parentFolder->shares()->where('shared_with_id', $userId)->exists()) {
            return true;
        }
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
     * Kiểm tra user có quyền chỉnh sửa NỘI DUNG folder (không phải folder gốc)
     */
    public function canEditFolderContent($folderId, $userId): bool
    {
        $folder = Folder::with('shares')->find($folderId);
        if (!$folder) return false;

        if ($folder->user_id === $userId) {
            return true;
        }

        $directShare = $folder->shares->where('shared_with_id', $userId)->first();
        if ($directShare && $directShare->permission === 'edit') {
            return true;
        }

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
        if (!$parentFolderId || $parentFolderId === 0) {
            return true; // 
        }

        $parentFolder = Folder::with('shares')->find($parentFolderId);
        if (!$parentFolder) return false;

        if ($parentFolder->user_id === $userId) {
            return true;
        }

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
            'can_edit_content' => $canEditContent,
            'can_edit_info' => $canEditInfo,
            'can_delete' => $canDelete,
            'can_create_subfolder' => $canCreateSubfolder,
            'is_owner' => $isOwner,
            'is_shared_folder' => $isSharedFolder,
            'permission' => $directShare ? $directShare->permission : null,
            'user_permission' => $directShare ? $directShare->permission : ($canEditContent ? 'edit' : 'view')
        ];
    }

    /**
     * Kiểm tra user có quyền chỉnh sửa folder con bên trong folder được share
     */
    public function canEditDescendantFolder($folderId, $userId): bool
    {
        $folder = Folder::with('shares')->find($folderId);
        if (!$folder) return false;

        if ($folder->user_id === $userId) {
            return true;
        }

        $directShare = $folder->shares->where('shared_with_id', $userId)->first();
        if ($directShare) {
            return false;
        }

        return $folder->isDescendantOfSharedFolder($userId);
    }

    /**
     * Kiểm tra user có quyền xóa folder con bên trong folder được share
     */
    public function canDeleteDescendantFolder($folderId, $userId): bool
    {
        $folder = Folder::with('shares')->find($folderId);
        if (!$folder) return false;

        if ($folder->user_id === $userId) {
            return true;
        }

        $directShare = $folder->shares->where('shared_with_id', $userId)->first();
        if ($directShare) {
            return false;
        }

        return $folder->isDescendantOfSharedFolder($userId);
    }

    /**
     * Kiểm tra user có quyền xem document
     */
    public function canViewDocument($documentId, $userId): bool
    {
        $document = Document::find($documentId);
        if (!$document) return false;

        if ($document->user_id === $userId) {
            return true;
        }
        if ($document->folder_id) {
            return $this->canAccessFolderThroughInheritance($document->folder_id, $userId);
        }
        return false;
    }
}
