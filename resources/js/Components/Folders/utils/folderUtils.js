export const getItemIcon = (item) => {
    if (!item) return 'fas fa-question-circle text-gray-400';

    if (item.item_type === 'folder') {
        if (item.shared_info && !item.is_owner) {
            return 'fas fa-share-alt text-blue-500';
        }
        return 'fas fa-folder text-yellow-500';
    }

    const icons = {
        'PDF': 'fas fa-file-pdf text-red-500',
        'Word': 'fas fa-file-word text-blue-500',
        'Excel': 'fas fa-file-excel text-green-500',
        'PowerPoint': 'fas fa-file-powerpoint text-orange-500',
        'Image': 'fas fa-file-image text-purple-500',
        'Video': 'fas fa-file-video text-pink-500',
        'Audio': 'fas fa-file-audio text-indigo-500',
    };

    return icons[item.type_name] || 'fas fa-file text-gray-500';
};

export const getItemTypeDisplay = (item) => {
    if (!item) return 'Unknown';

    if (item.item_type === 'folder') {
        return 'Thư mục';
    }

    return item?.type_name || 'Tài liệu';
};

export const formatFolderPath = (fullPath) => {
    if (!fullPath) return 'Thư mục gốc';

    const pathParts = fullPath.split('/').filter(part => part.trim());

    if (pathParts.length <= 2) {
        return pathParts.join('/');
    }

    return `${pathParts[0]}/.../${pathParts[pathParts.length - 1]}`;
};

export const getItemKey = (item) => {
    if (!item) return 'null-item';
    return `${item.item_type}-${item.id}`;
};