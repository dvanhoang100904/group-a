<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Header với nút Mới -->
    <div class="flex items-center justify-between mb-6">
      <!-- Nút Mới -->
     <div class="flex-shrink-0 relative">
    <button @click="handleNewButtonClick"
            :disabled="!shouldShowNewFolderButton"
            :title="!shouldShowNewFolderButton ? 'Bạn không có quyền tạo thư mục/file trong folder này' : ''"
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
        <i class="fas fa-plus mr-2"></i>Mới
        <i class="fas fa-chevron-down ml-2 text-xs"></i>
    </button>
    
    <!-- Dropdown menu -->
    <div v-if="showNewDropdown && shouldShowNewFolderButton" class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-1 z-30">
        <button @click="openCreateFolder" 
                class="flex items-center px-4 py-2 text-sm hover:bg-gray-100 w-full text-left text-gray-700">
            <i class="fas fa-folder-plus text-blue-500 mr-3"></i>Tạo thư mục
        </button>
        <a :href="uploadFileUrl" 
           class="flex items-center px-4 py-2 text-sm hover:bg-gray-100 no-underline text-gray-700">
            <i class="fas fa-file-upload text-green-500 mr-3"></i>Tải file lên
        </a>
    </div>
    
    <!-- Thông báo khi không có quyền -->
    <div v-if="showNewDropdown && !shouldShowNewFolderButton" 
      class="absolute top-full left-0 mt-2 w-56 bg-white rounded-lg shadow-lg border py-1 z-30">
      <div class="px-4 py-3 text-sm">
          <div class="flex items-start mb-3">
              <i class="fas fa-lock text-red-500 mr-2 mt-0.5"></i>
              <div>
                  <div class="font-medium text-gray-900">Không có quyền tạo</div>
                  <div class="text-gray-600 mt-1 text-xs">
                      {{ permissionMessage }}
                  </div>
              </div>
          </div>
          <div class="flex space-x-2">
              <button @click="goToRootFromPermission"
                      class="flex-1 px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-md transition-colors flex items-center justify-center">
                  <i class="fas fa-home mr-1"></i>
                  Về gốc
              </button>
              <button @click="showNewDropdown = false"
                      class="flex-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs rounded-md transition-colors">
                  Đóng
              </button>
          </div>
      </div>
    </div>
</div>

      <!-- Controls -->
      <div class="flex items-center gap-3">
        <span v-if="lastUpdate" class="text-sm text-gray-500 bg-gray-50 px-3 py-1 rounded-lg hidden sm:block">
          <i class="fas fa-clock mr-2"></i>{{ formatTime(lastUpdate) }}
        </span>
        
        <button @click="manualReload" :disabled="loading"
                class="px-3 py-2 bg-white hover:bg-gray-100 border rounded-lg text-sm flex items-center transition-colors disabled:opacity-50">
          <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': loading }"></i>
          Làm mới
        </button>
        
        <button @click="toggleAutoReload"
                :class="['px-3 py-2 border rounded-lg text-sm flex items-center transition-colors',
                         autoReloadEnabled ? 'bg-green-50 text-green-700 border-green-200' : 'bg-white text-gray-700 border-gray-300']">
          <i class="fas fa-clock mr-2"></i>
          {{ autoReloadEnabled ? 'Tự động: Bật' : 'Tự động: Tắt' }}
        </button>
      </div>
    </div>

    <!-- Breadcrumbs -->
    <FolderBreadcrumbs 
      :breadcrumbs="breadcrumbs"
      :current-folder="currentFolder"
      :is-search-mode="isSearchMode"
      @go-to-root="goToRoot"
      @go-to-folder="goToFolder"
      @go-to-parent="goToParent"
    />

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
    </div>

    <!-- Thông báo chế độ tìm kiếm -->
    <SearchModeNotification 
      v-if="isSearchMode && items.data.length > 0"
      :total-results="items.total"
    />

    <!-- Search Bar -->
    <SearchBar 
      :search-params="searchParams"
      :document-types="documentTypes"
      :loading="loading"
      :loading-document-types="loadingDocumentTypes"
      @search="handleSearch"
      @reset="resetFilters"
    />

    <!-- Folder Table -->
    <FolderTable 
      v-if="!loading"
      :items="items.data"
      :is-search-mode="isSearchMode"
      @open-item="openItem"
      @toggle-menu="toggleMenu"
      @show-context-menu="showContextMenu"
    >
      <template #empty-state-actions>
        <button v-if="!isSearchMode" @click="openCreateFolder" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
          <i class="fas fa-plus mr-2"></i>Tạo thư mục
        </button>
        <button v-if="isSearchMode" @click="resetFilters" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
          <i class="fas fa-times mr-2"></i>Xóa bộ lọc
        </button>
      </template>
    </FolderTable>

   <!-- Action Dropdown -->
<ActionDropdown 
  v-if="activeMenu"
  :active-menu="activeMenu"
  :action-dropdown-style="actionDropdownStyle"
  @share-folder="shareFolder"
  @edit-folder="editFolder"
  @view-document="viewDocument"
  @download-document="downloadDocument"
  @edit-document="editDocument"
  @delete-item="showDeleteConfirmation"
  @close="activeMenu = null"
/>

<!-- Context Menu -->
<ContextMenu 
  v-if="contextMenu.visible"
  :context-menu="contextMenu"
  :context-menu-style="contextMenuStyle"
  @open-item="openContextItem"
  @edit-folder="editFolder"
  @share-folder="shareFolder"
  @view-document="viewDocument"
  @download-document="downloadDocument"
  @edit-document="editDocument"
  @delete-item="showDeleteConfirmation"
  @close="hideContextMenu"
/>

    <!-- Modals -->
    <DeleteModal 
      v-if="showDeleteModal"
      :item="itemToDelete"
      @cancel="cancelDelete"
      @confirm="confirmDelete"
    />

    <SuccessErrorModal 
      v-if="showSuccessModal"
      type="success"
      :message="successMessage"
      @close="showSuccessModal = false"
      @continue="continueAfterSuccess"
    />

    <SuccessErrorModal 
      v-if="showErrorModal"
      type="error"
      :message="errorMessage"
      @close="showErrorModal = false"
    />

    <ShareModal 
      v-if="showShareModal"
      :folder-name="selectedFolder?.name"
      :share-data="shareModalData"
      :shared-users="shareModalData.sharedUsers"
      @close="closeShareModal"
      @share="shareFolderAction"
      @unshare="unshareUser"
    />

<!-- Phần template -->
<Pagination 
  v-if="!loading && items.data.length > 0"
  :pagination="items"
  :per-page="perPage"
  :pages="pages"
  @change-page="changePage"
  @change-per-page="changePerPage"
/>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

// Composables
import { useFolderAPI } from './composables/useFolderAPI';
import { useSearchFilters } from './composables/useSearchFilters';
import { useItemPermissions } from './composables/useItemPermissions';
import { useShareManagement } from './composables/useShareManagement';

// Utils
import { sanitizeOutput, sanitizeUrl, validateFolderId, validateDocumentId } from './utils/sanitizeUtils';
import { formatTime } from './utils/dateUtils';

// Components
import FolderBreadcrumbs from './components/FolderBreadcrumbs.vue';
import SearchModeNotification from './components/SearchModeNotification.vue';
import SearchBar from './components/SearchBar.vue';
import FolderTable from './components/FolderTable.vue';
import ActionDropdown from './components/ActionDropdown.vue';
import ContextMenu from './components/ContextMenu.vue';
import DeleteModal from './components/DeleteModal.vue';
import SuccessErrorModal from './components/SuccessErrorModal.vue';
import ShareModal from './components/ShareModal.vue';
import Pagination from './components/Pagination.vue';

// Refs
const items = ref({ 
  data: [], 
  current_page: 1, 
  last_page: 1, 
  from: 0, 
  to: 0, 
  total: 0 
});
const currentFolder = ref(null);
const breadcrumbs = ref([]);
const userInfo = ref(null);
const documentTypes = ref([]);
const loadingDocumentTypes = ref(false);
const perPage = ref(20);
const showNewDropdown = ref(false);
const autoReloadInterval = ref(null);
const autoReloadEnabled = ref(false);
const lastUpdate = ref(null);

// UI States
const activeMenu = ref(null);
const actionDropdownPosition = ref({ x: 0, y: 0 });
const contextMenu = ref({ visible: false, x: 0, y: 0, item: null });
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const showSuccessModal = ref(false);
const successMessage = ref('');
const showErrorModal = ref(false);
const errorMessage = ref('');
const showShareModal = ref(false);
const selectedFolder = ref(null);

// Composables
const { loading, error, loadData, loadDocumentTypes: apiLoadDocumentTypes } = useFolderAPI();
const { 
  searchParams, 
  isSearchMode, 
  hasActiveFilters, 
  handleSearch: handleSearchFilter, 
  resetFilters: resetFiltersFilter, 
  validateDate 
} = useSearchFilters();

const { 
  shouldShowEditButton, 
  shouldShowDeleteButton, 
  shouldShowShareButton, 
  shouldShowNewFolderButton: checkShouldShowNewFolderButton,
  getUploadFileUrl,
  getUserPermissionText,
  getPermissionDetails
} = useItemPermissions();

const { 
  shareModalData, 
  validateEmails, 
  loadSharedUsers, 
  shareFolder: apiShareFolder, 
  unshareUser: apiUnshareUser 
} = useShareManagement();

// Computed
const actionDropdownStyle = computed(() => {
  if (!activeMenu.value) return {};
  
  const menuWidth = 224;
  const menuHeight = 96;
  const padding = 10;
  
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  
  let x = actionDropdownPosition.value.x;
  let y = actionDropdownPosition.value.y;
  
  if (x + menuWidth > viewportWidth) {
    x = viewportWidth - menuWidth - padding;
  }
  
  if (y + menuHeight > viewportHeight) {
    y = actionDropdownPosition.value.y - menuHeight - 40;
  }
  
  x = Math.max(padding, Math.min(x, viewportWidth - menuWidth - padding));
  y = Math.max(padding, Math.min(y, viewportHeight - menuHeight - padding));
  
  return {
    left: x + 'px',
    top: y + 'px'
  };
});

const contextMenuStyle = computed(() => {
  if (!contextMenu.value.visible) return {};
  
  const menuWidth = 256;
  const menuHeight = 180;
  const padding = 10;
  
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  
  let x = contextMenu.value.x;
  let y = contextMenu.value.y;
  
  if (x + menuWidth > viewportWidth) {
    x = x - menuWidth;
  }
  
  if (y + menuHeight > viewportHeight) {
    y = y - menuHeight;
  }
  
  x = Math.max(padding, Math.min(x, viewportWidth - menuWidth - padding));
  y = Math.max(padding, Math.min(y, viewportHeight - menuHeight - padding));
  
  return {
    left: x + 'px',
    top: y + 'px'
  };
});

const handleNewButtonClick = () => {
    if (!shouldShowNewFolderButton.value) {
        // Hiển thị dropdown với thông báo lỗi
        showNewDropdown.value = true;
        // Cũng có thể hiển thị toast/thông báo
        showPermissionError();
    } else {
        showNewDropdown.value = !showNewDropdown.value;
    }
};
// Thêm computed để hiển thị thông báo chi tiết
const permissionMessage = computed(() => {
    if (!currentFolder.value) return '';
    
    const permission = currentFolder.value.user_permission;
    const isShared = currentFolder.value.is_shared_folder;
    const isDescendant = currentFolder.value.is_descendant_of_shared;
    const folderName = currentFolder.value.name || 'folder này';
    
    if (isShared) {
        if (permission === 'view') {
            return `"${folderName}" được chia sẻ với bạn với quyền "Chỉ xem". Bạn chỉ có thể xem và tải file.`;
        } else if (permission === 'edit') {
            return `"${folderName}" được chia sẻ với bạn với quyền "Chỉnh sửa". Bạn có thể tạo thư mục/file mới.`;
        }
    } else if (isDescendant) {
        return `"${folderName}" nằm trong folder được chia sẻ. Quyền của bạn phụ thuộc vào folder cha.`;
    } else if (currentFolder.value.is_owner === false) {
        return `Bạn không có quyền truy cập "${folderName}".`;
    }
    
    return 'Bạn không có quyền tạo thư mục/file mới tại đây.';
});

const showPermissionError = () => {
    if (!currentFolder.value) return;
    
    const message = permissionMessage.value;
    if (message) {
        // Có thể sử dụng toast notification hoặc alert
        showError(message);
    }
};

const shouldShowNewFolderButton = computed(() => {
  return checkShouldShowNewFolderButton(currentFolder.value);
});

const uploadFileUrl = computed(() => {
  return getUploadFileUrl(currentFolder.value);
});

const pages = computed(() => {
  const pages = [];
  const current = items.value.current_page;
  const last = items.value.last_page;
  const delta = 2;
  
  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    pages.push(i);
  }
  
  if (current - delta > 2) pages.unshift('...');
  if (current + delta < last - 1) pages.push('...');
  
  pages.unshift(1);
  if (last > 1) pages.push(last);
  
  return pages.filter((v, i, a) => a.indexOf(v) === i);
});

// Methods
const goToRootFromPermission = () => {
    showNewDropdown.value = false;
    goToRoot();
};

const hideContextMenu = () => {
  contextMenu.value = { visible: false, x: 0, y: 0, item: null };
};
const goToFolder = (folderId) => {
  try {
    const validFolderId = validateFolderId(folderId);
    
    if (isSearchMode.value) {
      isSearchMode.value = false;
      searchParams.value = { name: '', date: '', file_type: '' };
    }
    
    currentFolder.value = { folder_id: validFolderId };
    items.value.current_page = 1;
    loadFolderData();
  } catch (error) {
    showError('ID thư mục không hợp lệ');
  }
};

const goToParent = () => {
  if (currentFolder.value?.parent_folder_id) {
    goToFolder(currentFolder.value.parent_folder_id);
  } else {
    goToRoot();
  }
};

const goToRoot = () => {
  currentFolder.value = null;
  items.value.current_page = 1;
  loadFolderData();
};

const openCreateFolder = () => {
  const parentId = currentFolder.value?.folder_id || null;
  const safeParentId = parentId ? encodeURIComponent(parentId) : '';
  const url = `/folders/create?parent_id=${safeParentId}`;
  window.location.href = url;
  showNewDropdown.value = false;
};

const toggleNewDropdown = () => {
  showNewDropdown.value = !showNewDropdown.value;
};

const closeNewDropdownOutside = (event) => {
  const dropdown = event.target.closest('.relative');
  if (showNewDropdown.value && !dropdown) {
    showNewDropdown.value = false;
  }
};

const loadUserInfo = () => {
  try {
    const userMeta = document.querySelector('meta[name="user-info"]');
    if (userMeta) {
      userInfo.value = JSON.parse(userMeta.getAttribute('content'));
    }
  } catch (error) {
    console.error('Error loading user info:', error);
  }
};

const loadDocumentTypesList = async () => {
  loadingDocumentTypes.value = true;
  try {
    const types = await apiLoadDocumentTypes();
    documentTypes.value = types;
  } catch (error) {
    console.error('Error loading document types:', error);
    documentTypes.value = [];
  } finally {
    loadingDocumentTypes.value = false;
  }
};

const loadFolderData = async () => {
  const params = {
    name: searchParams.value.name,
    date: searchParams.value.date,
    file_type: searchParams.value.file_type,
    per_page: perPage.value,
    page: items.value.current_page,
  };

  if (currentFolder.value?.folder_id && !isSearchMode.value) {
    params.parent_id = currentFolder.value.folder_id;
  }

    try {
    const data = await loadData(params);
    
    console.log('API Response:', data); // Debug
    
    items.value = {
      data: data.items?.data || data.items || [],
      current_page: data.items?.current_page || data.current_page || 1,
      last_page: data.items?.last_page || data.last_page || 1,
      from: data.items?.from || data.from || 0,
      to: data.items?.to || data.to || 0,
      total: data.items?.total || data.total || 0
    };
        console.log('Pagination data:', items.value); 
    currentFolder.value = data.currentFolder || null;
    breadcrumbs.value = data.breadcrumbs || [];
    isSearchMode.value = data.isSearchMode || false;
    lastUpdate.value = new Date().toISOString();
  } catch (err) {
    showError(err.message || 'Lỗi khi tải dữ liệu');
    resetData();
  }
};

const resetData = () => {
  items.value = { data: [], current_page: 1, last_page: 1, from: 0, to: 0, total: 0 };
  currentFolder.value = null;
  breadcrumbs.value = [];
  isSearchMode.value = false;
};

const openItem = (item) => {
  if (!item) return;
  
  if (item.item_type === 'folder') {
    goToFolder(item.id);
  } else {
    viewDocument(item);
  }
};

const viewDocument = (item) => {
  if (item.item_type !== 'document') return;
  window.location.href = `/documents/${item.id}`;
};

const editDocument = (item) => {
  if (item.item_type !== 'document') return;
  
  if (!item.is_owner) {
    showError('Bạn không có quyền chỉnh sửa');
    return;
  }
  
  window.location.href = `/documents/${item.id}/edit`;
};

const downloadDocument = (item) => {
  if (item.file_path) {
    const safeUrl = sanitizeUrl(item.file_path);
    if (safeUrl) {
      const a = document.createElement('a');
      a.href = safeUrl;
      a.download = item.file_name || item.name;
      a.rel = 'noopener noreferrer';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    } else {
      showError('Đường dẫn file không hợp lệ');
    }
  } else {
    showError('File không tồn tại');
  }
  activeMenu.value = null;
  hideContextMenu();
};

const editFolder = (item) => {
  try {
    const validId = validateFolderId(item.id);
    window.location.href = `/folders/${validId}/edit`;
    activeMenu.value = null;
  } catch (error) {
    showError('ID không hợp lệ');
  }
};

const toggleMenu = (item, event) => {
  if (!item) return;
  
  if (activeMenu.value && activeMenu.value.id === item.id && activeMenu.value.item_type === item.item_type) {
    activeMenu.value = null;
    return;
  }

  const button = event.target.closest('button');
  if (button) {
    const rect = button.getBoundingClientRect();
    actionDropdownPosition.value = {
      x: rect.right - 224,
      y: rect.bottom
    };
  }

  activeMenu.value = item;
  hideContextMenu();
};

const showContextMenu = (event, item) => {
  if (!item) return;
  
  event.preventDefault();
  event.stopPropagation();
  
  contextMenu.value = {
    visible: true,
    x: event.clientX,
    y: event.clientY,
    item: item
  };
  activeMenu.value = null;
};

const openContextItem = () => {
  if (contextMenu.value.item.item_type === 'folder') {
    goToFolder(contextMenu.value.item.id);
  } else {
    openItem(contextMenu.value.item);
  }
  hideContextMenu();
};

const handleSearch = () => {
  items.value.current_page = 1;
  if (hasActiveFilters.value) {
    currentFolder.value = null;
    isSearchMode.value = true;
  } else {
    isSearchMode.value = false;
  }
  loadFolderData();
};

const resetFilters = () => {
  resetFiltersFilter();
  items.value.current_page = 1;
  isSearchMode.value = false;
  loadFolderData();
};

const shareFolder = (item) => {
  if (!item.is_owner) {
    showError('Chỉ chủ sở hữu mới có thể chia sẻ folder này');
    return;
  }
  
  selectedFolder.value = item;
  showShareModal.value = true;
  activeMenu.value = null;
  hideContextMenu();
  
  loadSharedUsers(item.id);
};

const shareFolderAction = async () => {
  if (!shareModalData.value.emailsInput.trim()) {
    showError('Vui lòng nhập ít nhất một email');
    return;
  }
  
  const emails = shareModalData.value.emailsInput.split(',')
    .map(email => email.trim())
    .filter(email => email);

  try {
    const result = await apiShareFolder(selectedFolder.value.id, emails, shareModalData.value.permission);
    if (result.success) {
      showSuccess(result.message);
      shareModalData.value.emailsInput = '';
    }
  } catch (error) {
    showError(error.message || 'Lỗi khi chia sẻ folder');
  }
};

const unshareUser = async (userId) => {
  if (!confirm('Bạn có chắc muốn hủy chia sẻ với người dùng này?')) {
    return;
  }

  try {
    const result = await apiUnshareUser(selectedFolder.value.id, userId);
    if (result.success) {
      showSuccess(result.message);
    }
  } catch (error) {
    showError('Lỗi khi hủy chia sẻ');
  }
};

const closeShareModal = () => {
  showShareModal.value = false;
  selectedFolder.value = null;
  shareModalData.value = {
    emailsInput: '',
    permission: 'view',
    loading: false,
    sharedUsers: [],
    emailError: ''
  };
};

const showDeleteConfirmation = (item) => {
  itemToDelete.value = item;
  showDeleteModal.value = true;
  activeMenu.value = null;
  hideContextMenu();
};

const cancelDelete = () => {
  showDeleteModal.value = false;
  itemToDelete.value = null;
};

const confirmDelete = async () => {
  if (!itemToDelete.value) return;
  
  try {
    const validId = validateFolderId(itemToDelete.value.id);
    const endpoint = itemToDelete.value.item_type === 'folder' 
      ? `/api/folders/${validId}`
      : `/api/documents/${validId}`;
    
    const response = await axios.delete(endpoint);
    
    if (response.data.success) {
      showSuccess(response.data.message);
      
      if (itemToDelete.value.item_type === 'folder' && 
          currentFolder.value && 
          currentFolder.value.folder_id === itemToDelete.value.id) {
        currentFolder.value = null;
      }
      
      loadFolderData();
    }
  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Lỗi khi xóa: ' + error.message;
    showError(errorMsg);
  } finally {
    showDeleteModal.value = false;
    itemToDelete.value = null;
  }
};

const continueAfterSuccess = () => {
  showSuccessModal.value = false;
  successMessage.value = '';
  loadFolderData();
};

const showSuccess = (message) => {
  successMessage.value = sanitizeOutput(message);
  showSuccessModal.value = true;
};

const showError = (message) => {
  errorMessage.value = sanitizeOutput(message);
  showErrorModal.value = true;
};

const changePage = (page) => {
  if (page === '...') return;
  items.value.current_page = page;
  loadFolderData();
};

const changePerPage = (newPerPage) => {
  perPage.value = newPerPage;
  items.value.current_page = 1;
  loadFolderData();
};

const manualReload = async () => {
  await Promise.all([
    loadDocumentTypesList(),
    loadFolderData()
  ]);
};

const toggleAutoReload = () => {
  autoReloadEnabled.value = !autoReloadEnabled.value;
  if (autoReloadEnabled.value) {
    startAutoReload();
  } else {
    stopAutoReload();
  }
};

const startAutoReload = () => {
  autoReloadInterval.value = setInterval(() => {
    if (autoReloadEnabled.value && !loading.value) {
      loadFolderData();
    }
  }, 30000);
};

const stopAutoReload = () => {
  if (autoReloadInterval.value) {
    clearInterval(autoReloadInterval.value);
    autoReloadInterval.value = null;
  }
};

const closeMenu = (event) => {
  const isDropdown = event.target.closest('.action-dropdown-container');
  const isFixed = event.target.closest('.action-dropdown-fixed');
  
  if (!isDropdown && !isFixed) {
    activeMenu.value = null;
  }
};

const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    activeMenu.value = null;
    hideContextMenu();
    if (showDeleteModal.value) {
      cancelDelete();
    }
    if (showSuccessModal.value) {
      continueAfterSuccess();
    }
    if (showErrorModal.value) {
      showErrorModal.value = false;
    }
  }
};

const handleScroll = () => {
  if (activeMenu.value) {
    activeMenu.value = null;
  }
};

// Lifecycle Hooks
onMounted(() => {
  loadUserInfo();
  loadDocumentTypesList();
  loadFolderData();
  
  document.addEventListener('click', closeMenu);
  document.addEventListener('keydown', handleKeydown);
  document.addEventListener('click', closeNewDropdownOutside);
  document.addEventListener('scroll', handleScroll);
  window.addEventListener('resize', hideContextMenu);
});

onBeforeUnmount(() => {
  stopAutoReload();
  document.removeEventListener('click', closeMenu);
  document.removeEventListener('keydown', handleKeydown);
  document.removeEventListener('click', closeNewDropdownOutside);
  document.removeEventListener('scroll', handleScroll);
  window.removeEventListener('resize', hideContextMenu);
});
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

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Smooth transitions */
.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}
</style>