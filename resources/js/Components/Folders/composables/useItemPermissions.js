import { computed } from 'vue';

export function useItemPermissions() {
    const shouldShowEditButton = (item) => {
        if (item.item_type === 'folder') {
            if (item.is_directly_shared && !item.is_owner) {
                return false;
            }
            return item.can_edit_info === true;
        }
        return item.is_owner;
    };

    const shouldShowDeleteButton = (item) => {
        if (!item) return false;

        if (item.item_type === 'document') {
            return item.is_owner;
        }

        if (item.item_type === 'folder') {
            if (item.is_directly_shared && !item.is_owner) {
                return false;
            }
            return item.can_delete === true;
        }

        return false;
    };

    const shouldShowShareButton = (item) => {
        return item.is_owner && item.item_type === 'folder';
    };

    const shouldShowNewFolderButton = (currentFolder) => {
        if (!currentFolder) {
            return true;
        }

        if (currentFolder.is_shared_folder && !currentFolder.is_owner) {
            return currentFolder.user_permission === 'edit' ||
                currentFolder.can_edit_content === true;
        }

        return true;
    };

    const getUserPermissionText = (item) => {
        if (item.is_owner) {
            return 'Chủ sở hữu';
        }
        if (item.is_directly_shared) {
            return `Được chia sẻ (${item.user_permission === 'edit' ? 'Có thể thêm folder con' : 'Chỉ xem'})`;
        }
        if (item.is_descendant_of_shared) {
            return `Folder con trong folder được share (${item.user_permission === 'edit' ? 'Có thể chỉnh sửa' : 'Chỉ xem'})`;
        }
        return 'Chỉ xem';
    };

    const getPermissionDetails = (item) => {
        if (item.is_owner) {
            return 'Bạn có toàn quyền với folder này';
        }
        if (item.is_directly_shared) {
            if (item.user_permission === 'edit') {
                return 'Bạn có thể: Tạo folder con, upload file, sửa/xóa nội dung bên trong. KHÔNG được: Sửa tên folder, xóa folder, chia sẻ folder.';
            }
            return 'Bạn chỉ có quyền xem folder này';
        }
        if (item.is_descendant_of_shared) {
            if (item.user_permission === 'edit') {
                return 'Bạn có toàn quyền với folder này (folder con trong folder được share)';
            }
            return 'Bạn chỉ có quyền xem folder này (folder con trong folder được share)';
        }
        return 'Bạn chỉ có quyền xem';
    };

    const getUploadFileUrl = (currentFolder) => {
        if (!currentFolder) {
            return '/upload';
        }

        const folderId = currentFolder.folder_id || null;
        const safeFolderId = folderId ? encodeURIComponent(folderId) : '';
        return `/upload?folder_id=${safeFolderId}`;
    };

    // Trong useItemPermissions.js, thêm method kiểm tra quyền download
    const shouldShowDownloadButton = (item) => {
        if (!item) return false;

        if (item.item_type === 'document') {
            // Document: có thể download nếu có quyền view
            return item.is_owner || item.can_download ||
                (item.folder_id && item.folder_can_view);
        }

        if (item.item_type === 'folder') {
            // Folder: có thể download nếu có quyền view và folder có ít nhất 1 file
            return (item.is_owner || item.user_permission === 'view' || item.user_permission === 'edit') &&
                (item.documents_count > 0 || item.child_folders_count > 0);
        }

        return false;
    };

    // Thêm method lấy thông tin quyền download
    const getDownloadPermission = (item) => {
        if (!item) return { can_download: false, reason: '' };

        if (item.item_type === 'folder') {
            const canView = item.is_owner || item.user_permission === 'view' || item.user_permission === 'edit';
            const hasContent = item.documents_count > 0 || item.child_folders_count > 0;

            if (!canView) {
                return { can_download: false, reason: 'Bạn không có quyền xem folder này' };
            }

            if (!hasContent) {
                return { can_download: false, reason: 'Folder trống, không có gì để tải' };
            }

            return { can_download: true, reason: '' };
        }

        return { can_download: true, reason: '' };
    };

    return {
        shouldShowEditButton,
        shouldShowDeleteButton,
        shouldShowShareButton,
        shouldShowNewFolderButton,
        getUserPermissionText,
        getPermissionDetails,
        getUploadFileUrl,
        shouldShowDownloadButton,
        getDownloadPermission,
    };
}