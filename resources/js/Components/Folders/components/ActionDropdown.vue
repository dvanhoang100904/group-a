<template>
  <div v-if="activeMenu" class="fixed bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 w-56 z-[10001] action-dropdown-fixed"
     :style="actionDropdownStyle">
  <div class="py-1">
    <!-- Hiển thị quyền hiện tại -->
    <div class="px-4 py-2 text-xs text-gray-500 border-b">
      <div class="font-medium">{{ getUserPermissionText(activeMenu) }}</div>
      <div class="text-xs mt-1">{{ getPermissionDetails(activeMenu) }}</div>
    </div>
    
    <!-- NÚT CHO FOLDER -->
    <template v-if="activeMenu.item_type === 'folder'">
      <!-- Nút Chia sẻ - CHỈ chủ sở hữu -->
      <button v-if="shouldShowShareButton(activeMenu)" 
              @click.stop="shareFolder(activeMenu)"
              class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left transition-colors">
        <i class="fas fa-share-alt mr-3 text-green-500"></i>Chia sẻ
      </button>
      
      <!-- Nút Chỉnh sửa - CHỈ khi có quyền sửa thông tin -->
      <button v-if="shouldShowEditButton(activeMenu)" 
              @click.stop="editFolder(activeMenu)"
              class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left transition-colors">
        <i class="fas fa-edit mr-3 text-blue-500"></i>Chỉnh sửa thông tin
      </button>
<!-- Nút tải fodler -->
    <button v-if="shouldShowDownloadButton(activeMenu)" 
            @click="$emit('download-folder', activeMenu)"
            class="flex items-center px-4 py-2 text-sm hover:bg-gray-100 w-full text-left text-gray-700">
      <i class="fas fa-download text-green-500 mr-3"></i>
      Tải folder (ZIP)
    </button>
    </template>
    
    
    <!-- NÚT CHO DOCUMENT -->
    <template v-else-if="activeMenu.item_type === 'document'">
      <!-- Xem chi tiết -->
      <button @click.stop="viewDocument(activeMenu)"
              class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left transition-colors">
        <i class="fas fa-eye mr-3 text-blue-500"></i>Xem chi tiết
      </button>
      
      <!-- Tải xuống -->
      <button @click.stop="downloadDocument(activeMenu)"
              class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left transition-colors">
        <i class="fas fa-download mr-3 text-green-500"></i>Tải xuống
      </button>
      
      <!-- Chỉnh sửa (chỉ owner) -->
      <button v-if="activeMenu.is_owner" 
              @click.stop="editDocument(activeMenu)"
              class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left transition-colors">
        <i class="fas fa-edit mr-3 text-yellow-500"></i>Chỉnh sửa
      </button>
    </template>
    
    <!-- Nút Xóa (cho cả folder và document) -->
    <button v-if="shouldShowDeleteButton(activeMenu)" 
            @click.stop="showDeleteConfirmation(activeMenu)"
            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left transition-colors">
      <i class="fas fa-trash mr-3 text-red-500"></i>Xóa
    </button>
    
    <!-- Thông báo đặc biệt cho folder được share với quyền edit -->
    <div v-if="activeMenu.item_type === 'folder' && activeMenu.is_shared_folder && !activeMenu.is_owner && activeMenu.user_permission === 'edit'" 
         class="px-4 py-2 text-sm text-blue-600 text-center bg-blue-50">
      <i class="fas fa-info-circle mr-1"></i>
      Bạn có thể: Tạo folder con, upload file
    </div>
  </div>
</div>
</template>

<script>
import { useItemPermissions } from '../composables/useItemPermissions';

export default {
  name: 'ActionDropdown',
  props: {
    activeMenu: {
      type: Object,
      default: null
    },
    actionDropdownStyle: {
      type: Object,
      default: () => ({})
    }
  },
  emits: [
    'share-folder',
    'edit-folder',
    'view-document',
    'download-document',
    'edit-document',
    'delete-item',
    'close',
    'download-folder',
  ],
  setup(props, { emit }) {
    const { 
      shouldShowEditButton, 
      shouldShowDeleteButton, 
      shouldShowShareButton,
      getUserPermissionText,
      getPermissionDetails,
      shouldShowDownloadButton,
      getDownloadPermission,
    } = useItemPermissions();

    const shareFolder = (item) => {
      emit('share-folder', item);
    };

    const editFolder = (item) => {
      emit('edit-folder', item);
    };

    const viewDocument = (item) => {
      emit('view-document', item);
    };

    const downloadDocument = (item) => {
      emit('download-document', item);
    };

    const editDocument = (item) => {
      emit('edit-document', item);
    };

    const showDeleteConfirmation = (item) => {
      emit('delete-item', item);
    };

    return {
      shouldShowEditButton,
      shouldShowDeleteButton,
      shouldShowShareButton,
      getUserPermissionText,
      getPermissionDetails,
      shareFolder,
      editFolder,
      viewDocument,
      downloadDocument,
      editDocument,
      showDeleteConfirmation,
      shouldShowDownloadButton,
      getDownloadPermission,
    };
  }
}
</script>

<style scoped>
.action-dropdown-fixed {
  position: fixed;
  z-index: 10001;
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15), 0 5px 10px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  animation: fadeInScale 0.15s ease-out;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95);
}

/* Animations */
@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-5px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}
</style>