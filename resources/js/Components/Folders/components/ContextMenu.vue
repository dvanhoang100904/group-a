<template>
  <!-- Context Menu - CẬP NHẬT -->
  <div v-if="contextMenu.visible" class="context-menu" :style="contextMenuStyle">
    <div class="context-menu-header">
      <i :class="getItemIcon(contextMenu.item)" class="mr-2"></i>
      <span class="font-medium text-sm truncate">{{ contextMenu.item.name }}</span>
      <span v-if="contextMenu.item.is_shared_folder && !contextMenu.item.is_owner" 
            class="text-xs text-blue-600 ml-2">
        ({{ contextMenu.item.user_permission === 'edit' ? 'Chỉnh sửa nội dung' : 'Chỉ xem' }})
      </span>
    </div>
    
    <div class="py-2">
      <!-- CHO FOLDER -->
      <template v-if="contextMenu.item.item_type === 'folder'">
        <button @click="openContextItem" class="context-menu-item">
          <i class="fas fa-folder-open text-blue-500 mr-3" style="width: 16px;"></i>
          Mở thư mục
        </button>
        
        <!-- ✅ Sửa thông tin folder -->
        <button v-if="shouldShowEditButton(contextMenu.item)" 
                @click="editFolder(contextMenu.item)" 
                class="context-menu-item">
          <i class="fas fa-edit text-blue-500 mr-3" style="width: 16px;"></i>Chỉnh sửa thông tin
        </button>
        
        <!-- ✅ Chia sẻ folder -->
        <button v-if="shouldShowShareButton(contextMenu.item)" 
                @click="shareFolder(contextMenu.item)" 
                class="context-menu-item">
          <i class="fas fa-share-alt text-green-500 mr-3" style="width: 16px;"></i>Chia sẻ
        </button>
      </template>
      
      <!-- CHO DOCUMENT -->
      <template v-else-if="contextMenu.item.item_type === 'document'">
        <button @click="viewDocument(contextMenu.item)" class="context-menu-item">
          <i class="fas fa-eye text-blue-500 mr-3" style="width: 16px;"></i>Xem chi tiết
        </button>
        
        <button @click="downloadDocument(contextMenu.item)" class="context-menu-item">
          <i class="fas fa-download text-green-500 mr-3" style="width: 16px;"></i>Tải xuống
        </button>
        
        <button v-if="contextMenu.item.is_owner" 
                @click="editDocument(contextMenu.item)" 
                class="context-menu-item">
          <i class="fas fa-edit text-yellow-500 mr-3" style="width: 16px;"></i>Chỉnh sửa
        </button>
      </template>
      
      <div class="context-menu-divider"></div>
      
      <!-- Nút Xóa -->
      <button v-if="shouldShowDeleteButton(contextMenu.item)" 
              @click="showDeleteConfirmation(contextMenu.item)" 
              class="context-menu-item context-menu-item-danger w-full text-left">
        <i class="fas fa-trash text-red-500 mr-3" style="width: 16px;"></i>Xóa
      </button>
    </div>
  </div>
  
  <div v-if="contextMenu.visible" class="context-menu-overlay" @click="hideContextMenu"></div>
</template>

<script>
import { getItemIcon } from '../utils/folderUtils';
import { useItemPermissions } from '../composables/useItemPermissions';

export default {
  name: 'ContextMenu',
  props: {
    contextMenu: {
      type: Object,
      default: () => ({ visible: false, x: 0, y: 0, item: null })
    },
    contextMenuStyle: {
      type: Object,
      default: () => ({})
    }
  },
  emits: [
    'open-item',
    'edit-folder',
    'share-folder',
    'view-document',
    'download-document',
    'edit-document',
    'delete-item',
    'close'
  ],
  setup(props, { emit }) {
    const { 
      shouldShowEditButton, 
      shouldShowDeleteButton, 
      shouldShowShareButton 
    } = useItemPermissions();

    const openContextItem = () => {
      emit('open-item', props.contextMenu.item);
    };

    const editFolder = (item) => {
      emit('edit-folder', item);
    };

    const shareFolder = (item) => {
      emit('share-folder', item);
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

    const hideContextMenu = () => {
      emit('close');
    };

    return {
      getItemIcon,
      shouldShowEditButton,
      shouldShowDeleteButton,
      shouldShowShareButton,
      openContextItem,
      editFolder,
      shareFolder,
      viewDocument,
      downloadDocument,
      editDocument,
      showDeleteConfirmation,
      hideContextMenu
    };
  }
}
</script>

<style scoped>
/* Context Menu Styles */
.context-menu {
  position: fixed;
  z-index: 9999;
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15), 0 5px 10px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  animation: fadeInScale 0.15s ease-out;
  min-width: 240px;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95);
}

.context-menu-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 9998;
  background: transparent;
}

.context-menu-header {
  padding: 12px 16px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e2e8f0;
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
  display: flex;
  align-items: center;
}

.context-menu-item {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 8px 16px;
  font-size: 14px;
  color: #374151;
  background: none;
  border: none;
  text-align: left;
  transition: all 0.15s ease;
  cursor: pointer;
  border-radius: 4px;
  margin: 2px 4px;
}

.context-menu-item:hover {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  transform: translateX(2px);
}

.context-menu-item:hover i {
  color: white !important;
}

.context-menu-item-danger {
  color: #dc2626;
}

.context-menu-item-danger:hover {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  color: white;
}

.context-menu-divider {
  border-top: 1px solid #f1f5f9;
  margin: 6px 8px;
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