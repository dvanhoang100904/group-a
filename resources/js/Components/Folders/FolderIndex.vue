<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Home</h1>
        <p class="text-gray-600 mt-1" v-if="userInfo">
          👋 Xin chào, <strong>{{ userInfo.name }}</strong>
          <span class="text-sm text-gray-500">({{ userInfo.role }})</span>
        </p>
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

    <!-- Actions & Search -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
      <div class="flex-shrink-0 relative">
        <button @click="toggleNewDropdown"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
          <i class="fas fa-plus mr-2"></i>Mới
          <i class="fas fa-chevron-down ml-2 text-xs"></i>
        </button>
        
        <div v-if="showNewDropdown" class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-1 z-30">
          <button @click="openCreateFolder" class="flex items-center px-4 py-2 text-sm hover:bg-gray-100 w-full text-left text-gray-700">
            <i class="fas fa-folder-plus text-blue-500 mr-3"></i>Tạo thư mục
          </button>
          <a :href="uploadFileUrl" class="flex items-center px-4 py-2 text-sm hover:bg-gray-100 no-underline text-gray-700">
            <i class="fas fa-file-upload text-green-500 mr-3"></i>Tải file lên
          </a>
        </div>
      </div>

      <!-- Search -->
      <div class="flex-1 min-w-0">
        <div class="flex flex-col sm:flex-row gap-2 bg-white rounded-lg shadow-sm border p-3">
          <div class="relative flex-1 sm:flex-none">
            <input v-model="searchParams.name" type="text" placeholder="Tìm tên..." 
                   class="pl-10 pr-4 py-2 border rounded-lg w-full sm:w-48 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          </div>
          
          <div class="relative flex-1 sm:flex-none">
            <input v-model="searchParams.date" type="date" 
                   class="pl-10 pr-4 py-2 border rounded-lg w-full focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <i class="fas fa-calendar absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          </div>
          
          <select v-model="searchParams.status" 
                  class="pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none w-full sm:w-40">
            <option value="">Tất cả trạng thái</option>
            <option value="public">Công khai</option>
            <option value="private">Riêng tư</option>
          </select>
          
          <button @click="handleSearch" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-colors">
            <i class="fas fa-search mr-2"></i>Tìm
          </button>
          
          <button v-if="hasActiveFilters" @click="resetFilters" 
                  class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-colors">
            <i class="fas fa-times mr-2"></i>Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Breadcrumbs -->
    <nav v-if="breadcrumbs.length > 0" class="flex items-center justify-between mb-6">
      <ol class="flex items-center space-x-2 text-sm">
        <li>
          <button @click="goToRoot" class="text-blue-500 hover:text-blue-700 flex items-center">
            <i class="fas fa-home mr-1"></i>Root
          </button>
        </li>
        <li v-for="(crumb, idx) in breadcrumbs" :key="crumb.folder_id" class="flex items-center">
          <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
          <button v-if="idx < breadcrumbs.length - 1" 
                  @click="goToFolder(crumb.folder_id)" 
                  class="text-blue-500 hover:text-blue-700">
            {{ crumb.name }}
          </button>
          <span v-else class="text-gray-600 font-medium">{{ crumb.name }}</span>
        </li>
      </ol>

      <button v-if="currentFolder" @click="goToParent" 
              class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm flex items-center">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại
      </button>
    </nav>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
    </div>

    <!-- Messages -->
    <div v-if="successMessage" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded flex items-center">
      <i class="fas fa-check-circle mr-2"></i>{{ successMessage }}
    </div>
    <div v-if="errorMessage" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded flex items-center">
      <i class="fas fa-exclamation-circle mr-2"></i>{{ errorMessage }}
    </div>

    <!-- Delete Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center mb-4">
            <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mr-3"></i>
            <h3 class="text-lg font-medium text-gray-900">Xác nhận xóa</h3>
          </div>
          <p class="text-sm text-gray-600 mb-6">
            Bạn có chắc muốn xóa <strong>{{ itemToDelete?.item_type === 'folder' ? 'thư mục' : 'tài liệu' }}</strong> 
            "<strong>{{ itemToDelete?.name }}</strong>"? Hành động này không thể hoàn tác.
          </p>
          <div class="flex justify-end space-x-3">
            <button @click="cancelDelete" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
              Hủy
            </button>
            <button @click="confirmDelete" 
                    class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg flex items-center transition-colors">
              <i class="fas fa-trash mr-2"></i>Xóa
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div v-if="!loading" class="bg-white rounded-lg shadow overflow-hidden mb-6">
      <table class="min-w-full">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kích cỡ</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
          </tr>
        </thead>
           <tbody class="bg-white divide-y divide-gray-200">
  <tr v-for="item in (items.data || [])" 
      :key="getItemKey(item)" 
      class="hover:bg-gray-50 transition-colors cursor-pointer"
      @contextmenu.prevent="showContextMenu($event, item)">
    
    <!-- Tên -->
    <td class="px-6 py-4 whitespace-nowrap">
      <div class="flex items-center" 
           @click="item && item.item_type === 'folder' ? goToFolder(item.id) : openDocument(item)">
        <i :class="getItemIcon(item)" class="text-lg mr-3"></i>
        <div>
          <div class="text-sm font-medium text-gray-900">{{ item?.name || 'Unknown' }}</div>
          <div v-if="item && item.item_type === 'folder' && (item.child_folders_count > 0 || item.documents_count > 0)" 
               class="text-xs text-gray-500 flex items-center">
            <i class="fas fa-folder-open mr-1"></i>
            {{ item.child_folders_count }} thư mục, {{ item.documents_count }} file
          </div>
        </div>
      </div>
    </td>

    <!-- Loại -->
    <td class="px-6 py-4 whitespace-nowrap" @click="item && item.item_type === 'folder' ? goToFolder(item.id) : openDocument(item)">
      <span class="text-sm text-gray-600">{{ item?.type_name || 'Unknown' }}</span>
    </td>

    <!-- Ngày tạo -->
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" 
        @click="item && item.item_type === 'folder' ? goToFolder(item.id) : openDocument(item)">
      {{ item ? formatDateTime(item.created_at) : '' }}
    </td>

    <!-- Kích cỡ -->
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" 
        @click="item && item.item_type === 'folder' ? goToFolder(item.id) : openDocument(item)">
      {{ item && item.item_type === 'folder' ? '-' : formatFileSize(item?.size) }}
    </td>

    <!-- Thao tác -->
    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
      <div class="relative inline-block text-left action-dropdown-container">
        <button @click.stop="item && toggleMenu(item, $event)"
                :disabled="!item"
                class="inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
          <i class="fas fa-ellipsis-v text-gray-500"></i>
        </button>
      </div>
    </td>
  </tr>

  <!-- Empty state -->
  <tr v-if="(items.data || []).length === 0">
    <td colspan="5" class="px-6 py-12 text-center">
      <div class="flex flex-col items-center">
        <i class="fas fa-folder-open text-gray-400 text-4xl mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Không có dữ liệu</h3>
        <p class="text-gray-500 mb-4">Hãy tạo thư mục hoặc tải file lên</p>
        <button @click="openCreateFolder" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
          <i class="fas fa-plus mr-2"></i>Tạo thư mục
        </button>
      </div>
    </td>
  </tr>
</tbody>
      </table>
    </div>

    <!-- Action Dropdown (fixed positioning) -->
    <div v-if="activeMenu" 
         class="fixed bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 w-56 z-[10001] action-dropdown-fixed"
         :style="actionDropdownStyle">
      <div class="py-1">
        <button v-if="activeMenu.item_type === 'folder'" 
                @click.stop="editFolder(activeMenu)"
                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left transition-colors">
          <i class="fas fa-edit mr-3 text-blue-500"></i>Chỉnh sửa
        </button>
        <button v-if="activeMenu.item_type === 'document'" 
                @click.stop="downloadDocument(activeMenu)"
                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left transition-colors">
          <i class="fas fa-download mr-3 text-green-500"></i>Tải xuống
        </button>
        <button @click.stop="showDeleteConfirmation(activeMenu)"
                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left transition-colors">
          <i class="fas fa-trash mr-3 text-red-500"></i>Xóa
        </button>
      </div>
    </div>

    <!-- Context Menu -->
    <div v-if="contextMenu.visible" class="context-menu" :style="contextMenuStyle">
      <div class="context-menu-header">
        <i :class="getItemIcon(contextMenu.item)" class="mr-2"></i>
        <span class="font-medium text-sm truncate">{{ contextMenu.item.name }}</span>
      </div>
      <div class="py-2">
        <button @click="openContextItem" class="context-menu-item">
          <i :class="contextMenu.item.item_type === 'folder' ? 'fas fa-folder-open' : 'fas fa-eye'" 
             class="text-blue-500 mr-3" style="width: 16px;"></i>
          {{ contextMenu.item.item_type === 'folder' ? 'Mở thư mục' : 'Xem file' }}
        </button>
        <button v-if="contextMenu.item.item_type === 'document'" 
                @click="downloadDocument(contextMenu.item)" 
                class="context-menu-item">
          <i class="fas fa-download text-green-500 mr-3" style="width: 16px;"></i>Tải xuống
        </button>
        <div class="context-menu-divider"></div>
        <button @click="showDeleteConfirmation(contextMenu.item)" class="context-menu-item context-menu-item-danger w-full text-left">
          <i class="fas fa-trash text-red-500 mr-3" style="width: 16px;"></i>Xóa
        </button>
      </div>
    </div>

    <div v-if="contextMenu.visible" class="context-menu-overlay" @click="hideContextMenu"></div>

    <!-- Pagination -->
    <div v-if="!loading && items.data.length > 0" 
         class="flex items-center justify-between bg-white px-4 py-3 rounded-lg shadow border-t">
      <div class="flex items-center text-sm text-gray-700">
        <span v-if="items.total > 0">
          Hiển thị <strong>{{ items.from }}-{{ items.to }}</strong> của <strong>{{ items.total }}</strong> kết quả
        </span>
        <span v-else>Không có kết quả</span>
      </div>

      <div v-if="items.last_page > 1" class="flex items-center space-x-2">
        <button v-if="items.current_page > 1" 
                @click="changePage(items.current_page - 1)"
                class="px-3 py-1 bg-white border rounded-lg hover:bg-gray-50 text-gray-700 transition-colors">
          <i class="fas fa-chevron-left mr-1"></i>Trước
        </button>
        <span v-else class="px-3 py-1 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
          <i class="fas fa-chevron-left mr-1"></i>Trước
        </span>

        <button v-for="page in pages" 
                :key="page"
                @click="changePage(page)"
                :disabled="page === '...'"
                :class="['px-3 py-1 rounded-lg font-medium transition-colors', 
                        page === items.current_page 
                          ? 'bg-blue-500 text-white' 
                          : page === '...' 
                            ? 'cursor-default text-gray-500'
                            : 'bg-white border hover:bg-gray-50 text-gray-700']">
          {{ page }}
        </button>

        <button v-if="items.current_page < items.last_page" 
                @click="changePage(items.current_page + 1)"
                class="px-3 py-1 bg-white border rounded-lg hover:bg-gray-50 text-gray-700 transition-colors">
          Sau<i class="fas fa-chevron-right ml-1"></i>
        </button>
        <span v-else class="px-3 py-1 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
          Sau<i class="fas fa-chevron-right ml-1"></i>
        </span>
      </div>
      <div v-else class="text-sm text-gray-500">
        Tất cả kết quả đang được hiển thị
      </div>

      <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-700">Hiển thị:</span>
        <select v-model="perPage" @change="changePerPage" 
                class="border rounded-lg px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'FolderIndex',
  data() {
    return {
      items: { 
        data: [], 
        current_page: 1, 
        last_page: 1, 
        from: 0, 
        to: 0, 
        total: 0 
      },
      currentFolder: null,
      breadcrumbs: [],
      userInfo: null,
      loading: true,
      activeMenu: null,
      perPage: 20,
      successMessage: '',
      errorMessage: '',
      searchParams: { 
        name: '', 
        date: '', 
        status: '' 
      },
      contextMenu: { 
        visible: false, 
        x: 0, 
        y: 0, 
        item: null 
      },
      actionDropdownPosition: { 
        x: 0, 
        y: 0 
      },
      showNewDropdown: false,
      autoReloadInterval: null,
      autoReloadEnabled: false,
      lastUpdate: null,
      showDeleteModal: false,
      itemToDelete: null,
    }
  },
  computed: {
     safeItems() {
    return (this.items.data || []).filter(item => item !== null && item !== undefined);
  },
    hasActiveFilters() {
      return this.searchParams.name || this.searchParams.date || this.searchParams.status;
    },
    uploadFileUrl() {
      const folderId = this.currentFolder?.folder_id || null;
      return `/upload?folder_id=${folderId}`;
    },
    pages() {
      const pages = [];
      const current = this.items.current_page;
      const last = this.items.last_page;
      const delta = 2;
      
      for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        pages.push(i);
      }
      
      if (current - delta > 2) pages.unshift('...');
      if (current + delta < last - 1) pages.push('...');
      
      pages.unshift(1);
      if (last > 1) pages.push(last);
      
      return pages.filter((v, i, a) => a.indexOf(v) === i);
    },
    actionDropdownStyle() {
      if (!this.activeMenu) return {};
      
      const menuWidth = 224;
      const menuHeight = 96;
      const padding = 10;
      
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;
      
      let x = this.actionDropdownPosition.x;
      let y = this.actionDropdownPosition.y;
      
      if (x + menuWidth > viewportWidth) {
        x = viewportWidth - menuWidth - padding;
      }
      
      if (y + menuHeight > viewportHeight) {
        y = this.actionDropdownPosition.y - menuHeight - 40;
      }
      
      x = Math.max(padding, Math.min(x, viewportWidth - menuWidth - padding));
      y = Math.max(padding, Math.min(y, viewportHeight - menuHeight - padding));
      
      return {
        left: x + 'px',
        top: y + 'px'
      };
    },
    contextMenuStyle() {
      if (!this.contextMenu.visible) return {};
      
      const menuWidth = 256;
      const menuHeight = 180;
      const padding = 10;
      
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;
      
      let x = this.contextMenu.x;
      let y = this.contextMenu.y;
      
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
    },
     safeItems() {
    return this.items.data || [];
  },
  
  safePagination() {
    return {
      current_page: this.items.current_page || 1,
      last_page: this.items.last_page || 1,
      from: this.items.from || 0,
      to: this.items.to || 0,
      total: this.items.total || 0
    };
  }
  },
  mounted() {
    this.loadUserInfo();
    this.loadData();
    document.addEventListener('click', this.closeMenu);
    document.addEventListener('keydown', this.handleKeydown);
    document.addEventListener('click', this.closeNewDropdownOutside);
    document.addEventListener('scroll', this.handleScroll);
    window.addEventListener('resize', this.hideContextMenu);
  },
  beforeUnmount() {
    this.stopAutoReload();
    document.removeEventListener('click', this.closeMenu);
    document.removeEventListener('keydown', this.handleKeydown);
    document.removeEventListener('click', this.closeNewDropdownOutside);
    document.removeEventListener('scroll', this.handleScroll);
    window.removeEventListener('resize', this.hideContextMenu);
  },
  methods: {
    getItemKey(item) {
    if (!item) return 'null-item';
    return `${item.item_type}-${item.id}`;
  },
    async loadData() {
  this.loading = true;
  this.errorMessage = '';
  
  try {
    const params = {
      name: this.searchParams.name || '',
      date: this.searchParams.date || '',
      status: this.searchParams.status || '',
      per_page: this.perPage,
      page: this.items.current_page,
    };

    if (this.currentFolder?.folder_id) {
      params.parent_id = this.currentFolder.folder_id;
    }

    console.log('📡 Loading data with params:', params);

    const response = await axios.get('/api/folders', { params });
    
    console.log('📄 API Response:', response.data);

    if (response.data.success) {
      const data = response.data.data;
      
      // ✅ Đảm bảo items luôn có cấu trúc đúng
      this.items = {
        data: data.items?.data || data.items || [],
        current_page: data.items?.current_page || data.current_page || 1,
        last_page: data.items?.last_page || data.last_page || 1,
        from: data.items?.from || data.from || 0,
        to: data.items?.to || data.to || 0,
        total: data.items?.total || data.total || 0
      };
      
      this.currentFolder = data.currentFolder || null;
      this.breadcrumbs = data.breadcrumbs || [];
      this.lastUpdate = new Date().toISOString();
      
      console.log('✅ Load success:', this.items.data.length, 'items');
    } else {
      throw new Error(response.data.message || 'API response not successful');
    }
  } catch (error) {
    console.error('❌ Error loading data:', error);
    this.errorMessage = error.response?.data?.message || 'Lỗi khi tải dữ liệu';
    
    // Reset data khi có lỗi
    this.items = { data: [], current_page: 1, last_page: 1, from: 0, to: 0, total: 0 };
    this.currentFolder = null;
    this.breadcrumbs = [];
  } finally {
    this.loading = false;
  }
},

    async loadUserInfo() {
      try {
        const userMeta = document.querySelector('meta[name="user-info"]');
        if (userMeta) {
          this.userInfo = JSON.parse(userMeta.getAttribute('content'));
        }
      } catch (error) {
        console.error('Error loading user info:', error);
      }
    },

   getItemIcon(item) {
  if (!item) return 'fas fa-question-circle text-gray-400';
  
  if (item.item_type === 'folder') {
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
},

    goToFolder(folderId) {
      console.log('🔍 Navigate to folder:', folderId);
      this.currentFolder = { folder_id: folderId };
      this.items.current_page = 1;
      this.loadData();
    },

    goToParent() {
      if (this.currentFolder?.parent_folder_id) {
        this.goToFolder(this.currentFolder.parent_folder_id);
      } else {
        this.goToRoot();
      }
    },

    goToRoot() {
      console.log('🏠 Navigate to root');
      this.currentFolder = null;
      this.items.current_page = 1;
      this.loadData();
    },

    openCreateFolder() {
      const parentId = this.currentFolder?.folder_id || null;
      window.location.href = `/folders/create?parent_id=${parentId}`;
      this.showNewDropdown = false;
    },

    editFolder(item) {
      window.location.href = `/folders/${item.id}/edit`;
      this.activeMenu = null;
    },

    openDocument(item) {
      if (item.file_path) {
        window.open(item.file_path, '_blank');
      } else {
        this.errorMessage = 'File không tồn tại';
      }
      this.hideContextMenu();
    },

    downloadDocument(item) {
      if (item.file_path) {
        const a = document.createElement('a');
        a.href = item.file_path;
        a.download = item.file_name || item.name;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      } else {
        this.errorMessage = 'File không tồn tại';
      }
      this.activeMenu = null;
      this.hideContextMenu();
    },

    showDeleteConfirmation(item) {
      this.itemToDelete = item;
      this.showDeleteModal = true;
      this.activeMenu = null;
      this.hideContextMenu();
    },

    cancelDelete() {
      this.showDeleteModal = false;
      this.itemToDelete = null;
    },

    async confirmDelete() {
      if (!this.itemToDelete) return;
      
      try {
        console.log('🗑️ Deleting:', this.itemToDelete);
        
        const endpoint = this.itemToDelete.item_type === 'folder' 
          ? `/api/folders/${this.itemToDelete.id}`
          : `/api/documents/${this.itemToDelete.id}`;
        
        const response = await axios.delete(endpoint);
        
        if (response.data.success) {
          this.successMessage = response.data.message;
          
          // Reset currentFolder if deleting current folder
          if (this.itemToDelete.item_type === 'folder' && 
              this.currentFolder && 
              this.currentFolder.folder_id === this.itemToDelete.id) {
            this.currentFolder = null;
          }
          
          await this.loadData();
          
          // Auto hide success message
          setTimeout(() => {
            this.successMessage = '';
          }, 5000);
        }
      } catch (error) {
        console.error('❌ Delete error:', error);
        this.errorMessage = error.response?.data?.message || 'Lỗi khi xóa';
        
        // Auto hide error message
        setTimeout(() => {
          this.errorMessage = '';
        }, 8000);
      } finally {
        this.showDeleteModal = false;
        this.itemToDelete = null;
      }
    },
showContextMenu(event, item) {
  if (!item) return;
  
  event.preventDefault();
  event.stopPropagation();
  
  this.contextMenu = {
    visible: true,
    x: event.clientX,
    y: event.clientY,
    item: item
  };
  this.activeMenu = null;
},

toggleMenu(item, event) {
  if (!item) return;
  
  if (this.activeMenu && this.activeMenu.id === item.id && this.activeMenu.item_type === item.item_type) {
    this.activeMenu = null;
    return;
  }

  const button = event.target.closest('button');
  if (button) {
    const rect = button.getBoundingClientRect();
    this.actionDropdownPosition = {
      x: rect.right - 224,
      y: rect.bottom
    };
  }

  this.activeMenu = item;
  this.hideContextMenu();
},

    hideContextMenu() {
      this.contextMenu = { 
        visible: false, 
        x: 0, 
        y: 0, 
        item: null 
      };
    },

    toggleMenu(item, event) {
      if (this.activeMenu && this.activeMenu.id === item.id && this.activeMenu.item_type === item.item_type) {
        this.activeMenu = null;
        return;
      }

      const button = event.target.closest('button');
      if (button) {
        const rect = button.getBoundingClientRect();
        this.actionDropdownPosition = {
          x: rect.right - 224,
          y: rect.bottom
        };
      }

      this.activeMenu = item;
      this.hideContextMenu();
    },

    closeMenu(event) {
      const isDropdown = event.target.closest('.action-dropdown-container');
      const isFixed = event.target.closest('.action-dropdown-fixed');
      
      if (!isDropdown && !isFixed) {
        this.activeMenu = null;
      }
    },

    handleKeydown(event) {
      if (event.key === 'Escape') {
        this.activeMenu = null;
        this.hideContextMenu();
        if (this.showDeleteModal) {
          this.cancelDelete();
        }
      }
    },

    handleScroll() {
      if (this.activeMenu) {
        this.activeMenu = null;
      }
    },

    closeNewDropdownOutside(event) {
      const dropdown = event.target.closest('.relative');
      if (this.showNewDropdown && !dropdown) {
        this.showNewDropdown = false;
      }
    },

    toggleNewDropdown() {
      this.showNewDropdown = !this.showNewDropdown;
    },

    openContextItem() {
      if (this.contextMenu.item.item_type === 'folder') {
        this.goToFolder(this.contextMenu.item.id);
      } else {
        this.openDocument(this.contextMenu.item);
      }
      this.hideContextMenu();
    },

    handleSearch() {
      this.items.current_page = 1;
      this.loadData();
    },

    resetFilters() {
      this.searchParams = { name: '', date: '', status: '' };
      this.perPage = 20;
      this.items.current_page = 1;
      this.loadData();
    },

    changePerPage() {
      this.items.current_page = 1;
      this.loadData();
    },

    changePage(page) {
      if (page === '...') return;
      this.items.current_page = page;
      this.loadData();
    },

    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('vi-VN');
    },

    formatDateTime(dateTimeString) {
      if (!dateTimeString) return '';
      const date = new Date(dateTimeString);
      return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', { 
        hour: '2-digit', 
        minute: '2-digit' 
      });
    },

    formatTime(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleTimeString('vi-VN', { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
      });
    },

    formatFileSize(bytes) {
      if (!bytes || bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    },

    startAutoReload() {
      this.autoReloadInterval = setInterval(() => {
        if (this.autoReloadEnabled && !this.loading) {
          console.log('🔄 Auto-reloading...');
          this.loadData();
        }
      }, 30000); // 30 seconds
    },

    stopAutoReload() {
      if (this.autoReloadInterval) {
        clearInterval(this.autoReloadInterval);
        this.autoReloadInterval = null;
      }
    },

    toggleAutoReload() {
      this.autoReloadEnabled = !this.autoReloadEnabled;
      if (this.autoReloadEnabled) {
        this.startAutoReload();
        console.log('🔔 Auto-reload: ON');
      } else {
        this.stopAutoReload();
        console.log('🔕 Auto-reload: OFF');
      }
    },

    manualReload() {
      this.loadData();
    },
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

/* Action Dropdown Fixed */
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