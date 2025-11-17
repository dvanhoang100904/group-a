<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Tiêu đề -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Home</h1>
    </div>

    <!-- Header với Button và Search cùng dòng -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
      <!-- Button thêm folder -->
      <div class="flex-shrink-0 relative">
        <button 
          @click="toggleNewDropdown"
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center w-fit transition-colors duration-200"
        >
          <i class="fas fa-plus mr-2"></i>
          Mới
          <i class="fas fa-chevron-down ml-2 text-xs"></i>
        </button>
        
        <!-- Dropdown menu -->
        <div 
          v-if="showNewDropdown"
          class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-30"
        >
          <!-- Tạo thư mục -->
          <button 
            @click="openCreateFolder"
            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 no-underline w-full text-left"
          >
            <i class="fas fa-folder-plus text-blue-500 mr-3"></i>
            Tạo thư mục
          </button>
          
          <!-- Tải file lên -->
          <a 
            :href="uploadFileUrl" 
            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 no-underline"
          >
            <i class="fas fa-file-upload text-green-500 mr-3"></i>
            Tải file lên
          </a>
        </div>
      </div>

      <!-- Tìm kiếm & lọc -->
      <div class="flex-1 min-w-0">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-white rounded-lg shadow-sm border border-gray-200 p-3">
          <!-- Input hidden cho parent_id -->
          <input v-if="currentFolder" type="hidden" name="parent_id" :value="currentFolder.folder_id">
          
          <!-- Tìm theo tên -->
          <div class="relative flex-1 sm:flex-none">
            <input type="text" 
                   v-model="localSearchParams.name"
                   placeholder="Tìm theo tên..." 
                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full sm:w-48">
            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          </div>
          
          <!-- Tìm theo ngày -->
          <div class="relative flex-1 sm:flex-none">
            <input type="date" 
                   v-model="localSearchParams.date"
                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full">
            <i class="fas fa-calendar absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          </div>
          
          <!-- Lọc theo trạng thái -->
          <div class="relative flex-1 sm:flex-none">
            <select v-model="localSearchParams.status"
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full sm:w-40">
              <option value="">Tất cả trạng thái</option>
              <option value="public">Công khai</option>
              <option value="private">Riêng tư</option>
            </select>
            <i class="fas fa-filter absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          </div>
          
          <!-- Nút tìm kiếm -->
          <button @click="handleSearch" 
                  class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-colors duration-200 flex-1 sm:flex-none">
            <i class="fas fa-search mr-2"></i>
            Tìm kiếm
          </button>
          
          <!-- Nút reset -->
          <button v-if="hasActiveFilters" 
                  @click="resetFilters"
                  class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-colors duration-200 flex-1 sm:flex-none">
            <i class="fas fa-times mr-2"></i>
            Reset
          </button>
        </div>
      </div>
    </div>
    <!-- Thêm vào sau breadcrumbs trong FolderIndex.vue -->
<div class="flex items-center justify-between mb-4">
  <!-- Breadcrumbs (giữ nguyên) -->
  <nav v-if="breadcrumbs.length > 0" class="flex items-center">
    <!-- ... breadcrumbs code ... -->
  </nav>
  
  <!-- Auto-reload controls -->
  <div class="flex items-center space-x-2">
    <button @click="manualReload" 
            :disabled="loading"
            class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm text-gray-700 flex items-center disabled:opacity-50">
      <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': loading }"></i>
      Làm mới
    </button>
    
    <button @click="toggleAutoReload"
            :class="[
              'px-3 py-1 rounded-lg text-sm flex items-center',
              autoReloadEnabled 
                ? 'bg-green-100 text-green-700 hover:bg-green-200' 
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]">
      <i class="fas fa-clock mr-2"></i>
      {{ autoReloadEnabled ? 'Tự động load sau 30s: Bật' : 'Tự động load sau 30s: Tắt' }}
    </button>
    
    <!-- Last update time -->
    <span v-if="lastUpdate" class="text-xs text-gray-500">
      Cập nhật: {{ formatTime(lastUpdate) }}
    </span>
  </div>
</div>

    <!-- Breadcrumbs và nút back -->
    <div class="mb-6">
      <nav v-if="breadcrumbs.length > 0" class="flex items-center justify-between mb-4">
        <!-- Breadcrumbs -->
        <div class="flex items-center">
          <ol class="flex items-center space-x-2 text-sm">
            <li>
              <button @click="goToRoot" class="text-blue-500 hover:text-blue-700 flex items-center">
                <i class="fas fa-home mr-1"></i> Root
              </button>
            </li>
            <li v-for="(crumb, index) in breadcrumbs" :key="crumb.folder_id" class="flex items-center">
              <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
              <button v-if="index < breadcrumbs.length - 1" 
                     @click="goToFolder(crumb.folder_id)" 
                     class="text-blue-500 hover:text-blue-700">
                {{ crumb.name }}
              </button>
              <span v-else class="text-gray-600 font-medium">{{ crumb.name }}</span>
            </li>
          </ol>
        </div>

        <!-- Nút back -->
        <button v-if="currentFolder && currentFolder.parent_folder_id" 
                @click="goToParent"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center text-sm">
          <i class="fas fa-arrow-left mr-2"></i>
          Quay lại
        </button>
        <button v-else-if="currentFolder" 
                @click="goToRoot"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center text-sm">
          <i class="fas fa-arrow-left mr-2"></i>
          Về Root
        </button>
      </nav>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center items-center py-8">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
    </div>

    <!-- Thông báo kết quả tìm kiếm -->
    <div v-else-if="hasActiveFilters" class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <i class="fas fa-info-circle text-blue-500 mr-2"></i>
          <span class="text-sm text-blue-700">
            Kết quả tìm kiếm:
            <span v-if="localSearchParams.name"><strong>"{{ localSearchParams.name }}"</strong></span>
            <span v-if="localSearchParams.name && (localSearchParams.date || localSearchParams.status)"> và </span>
            <span v-if="localSearchParams.date"> ngày <strong>{{ formatDate(localSearchParams.date) }}</strong></span>
            <span v-if="localSearchParams.date && localSearchParams.status"> và </span>
            <span v-if="localSearchParams.status">
              <strong>{{ localSearchParams.status == 'public' ? 'Công khai' : 'Riêng tư' }}</strong>
            </span>
            - Tìm thấy <strong>{{ folders.total }}</strong> thư mục
            (Trang {{ folders.current_page }}/{{ folders.last_page }})
          </span>
        </div>
        <button @click="resetFilters" 
                class="text-blue-500 hover:text-blue-700 text-sm">
          <i class="fas fa-times mr-1"></i> Xóa bộ lọc
        </button>
      </div>
    </div>

    <!-- Thông báo -->
    <div v-if="successMessage" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
      <div class="flex items-center">
        <i class="fas fa-check-circle text-green-500 mr-3"></i>
        <span>{{ successMessage }}</span>
      </div>
    </div>

    <div v-if="errorMessage" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
      <div class="flex items-center">
        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
        <span>{{ errorMessage }}</span>
      </div>
    </div>

    <!-- Table -->
    <div v-if="!loading" class="bg-white rounded-lg shadow overflow-hidden mb-6">
      <table class="min-w-full">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Tên thư mục
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Chỉ số
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Ngày tạo
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Kích cỡ tập tin
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Thao tác
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="folder in folders.data" 
              :key="folder.folder_id" 
              class="hover:bg-gray-50 relative context-menu-row transition-colors duration-150"
              :class="{ 
                'context-menu-highlight': contextMenu.folder?.folder_id === folder.folder_id
              }"
              @contextmenu.prevent="showContextMenu($event, folder)"
              @click="goToFolder(folder.folder_id)">
            <td class="px-6 py-4 whitespace-nowrap cursor-pointer">
              <div class="flex items-center">
                <i class="fas fa-folder text-yellow-500 mr-3 text-lg"></i>
                <div>
                  <div class="text-sm font-medium text-gray-900" v-html="highlightText(folder.name)"></div>
                  <div v-if="folder.child_folders_count > 0" class="text-xs text-gray-500 flex items-center">
                    <i class="fas fa-folder-open mr-1"></i>
                    {{ folder.child_folders_count }} thư mục con
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap cursor-pointer">
              <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', 
                           folder.status == 'public' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                <i :class="['fas mr-1', folder.status == 'public' ? 'fa-globe' : 'fa-lock']"></i>
                {{ folder.status == 'public' ? 'Công khai' : 'Riêng tư' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer">
              {{ formatDateTime(folder.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer">
              {{ folder.documents_count }} files
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium align-middle">
             <div class="relative inline-block text-left">
                  <button type="button" 
                          class="inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                          @click.stop="toggleMenu(folder.folder_id)">
                    <i class="fas fa-ellipsis-v text-gray-500"></i>
                  </button>
                  
                  <!-- Dropdown panel -->
                  <div v-if="activeMenu === folder.folder_id"
                      class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-20"
                      style="z-index: 20;">
                    <div class="py-1" role="none">
                      <!-- Edit -->
                      <button @click="editFolder(folder.folder_id)"
                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 w-full text-left">
                        <i class="fas fa-edit mr-3 text-blue-500"></i>
                        Chỉnh sửa
                      </button>
                      
                      <!-- Delete -->
                      <button @click="deleteFolder(folder)"
                              class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 text-left">
                        <i class="fas fa-trash mr-3 text-red-500"></i>
                        Xóa
                      </button>
                    </div>
                  </div>
                </div>
            </td>
          </tr>

          <!-- Empty state -->
          <tr v-if="folders.data.length === 0">
            <td colspan="5" class="px-6 py-12 text-center">
              <div class="flex flex-col items-center justify-center">
                <i class="fas fa-folder-open text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Không có thư mục nào</h3>
                <p class="text-gray-500 mb-4">Hãy tạo thư mục đầu tiên của bạn</p>
                <button @click="openCreateFolder" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
                  <i class="fas fa-plus mr-2"></i>
                  Tạo thư mục
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Context Menu -->
    <div v-if="contextMenu.visible && contextMenu.folder" 
         class="context-menu"
         :style="contextMenuStyle">
      <!-- Header với tên folder -->
      <div class="context-menu-header">
        <div class="flex items-center">
          <i class="fas fa-folder text-yellow-500 me-2"></i>
          <span class="font-medium text-dark text-sm truncate" :title="contextMenu.folder.name">
            {{ contextMenu.folder.name }}
          </span>
        </div>
        <div class="text-gray-500 mt-1 text-xs">
          {{ contextMenu.folder.status === 'public' ? 'Công khai' : 'Riêng tư' }} • 
          {{ formatDateTime(contextMenu.folder.created_at) }}
        </div>
      </div>

      <!-- Menu items -->
      <div class="py-2">
        <!-- Open -->
        <button type="button"
                class="context-menu-item"
                @click="openContextFolder">
          <i class="fas fa-folder-open text-blue-500 me-3" style="width: 16px;"></i>
          Mở thư mục
        </button>
        
        <!-- Edit -->
        <button @click="editContextFolder"
                class="context-menu-item">
          <i class="fas fa-edit text-green-500 me-3" style="width: 16px;"></i>
          Chỉnh sửa
        </button>
        
        <!-- Divider -->
        <div class="context-menu-divider"></div>
        
        <!-- Delete -->
        <button @click="deleteContextFolder"
                class="context-menu-item context-menu-item-danger w-full text-start">
          <i class="fas fa-trash text-red-500 me-3" style="width: 16px;"></i>
          Xóa thư mục
        </button>
      </div>
    </div>

    <!-- Overlay để đóng context menu khi click ra ngoài -->
    <div v-if="contextMenu.visible" 
         class="context-menu-overlay"
         @click="hideContextMenu"></div>
         

    <!-- Phân trang và điều khiển hiển thị -->
    <div v-if="!loading && folders.data.length > 0" class="flex items-center justify-between bg-white px-4 py-3 rounded-lg shadow border-t border-gray-200">
      <!-- Thông tin số lượng -->
      <div class="flex items-center text-sm text-gray-700">
        <span>
          <span v-if="folders.total > 0">
            Hiển thị 
            <strong>{{ folders.from }}-{{ folders.to }}</strong>
            của <strong>{{ folders.total }}</strong> kết quả
          </span>
          <span v-else>Không có kết quả nào</span>
        </span>
      </div>

      <!-- Các nút phân trang -->
      <div v-if="folders.last_page > 1" class="flex items-center space-x-2">
        <!-- Nút trang trước -->
        <button v-if="folders.current_page > 1" 
                @click="changePage(folders.current_page - 1)"
                class="px-3 py-1 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700">
          <i class="fas fa-chevron-left mr-1"></i> Trước
        </button>
        <span v-else class="px-3 py-1 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
          <i class="fas fa-chevron-left mr-1"></i> Trước
        </span>

        <!-- Các số trang -->
        <button v-for="page in pages" 
                :key="page"
                @click="changePage(page)"
                :class="['px-3 py-1 rounded-lg font-medium', 
                        page === folders.current_page 
                          ? 'bg-blue-500 text-white' 
                          : 'bg-white border border-gray-300 hover:bg-gray-50 text-gray-700']">
          {{ page }}
        </button>

        <!-- Nút trang sau -->
        <button v-if="folders.current_page < folders.last_page" 
                @click="changePage(folders.current_page + 1)"
                class="px-3 py-1 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700">
          Sau <i class="fas fa-chevron-right ml-1"></i>
        </button>
        <span v-else class="px-3 py-1 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
          Sau <i class="fas fa-chevron-right ml-1"></i>
        </span>
      </div>
      <div v-else class="text-sm text-gray-500">
        Tất cả kết quả đang được hiển thị
      </div>

      <!-- Chọn số items mỗi trang -->
      <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-700">Hiển thị:</span>
        <select v-model="perPage" 
                @change="changePerPage"
                class="border border-gray-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="10">10</option>
          <option value="25">25</option>
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
      // Data từ API
      folders: {
        data: [],
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        total: 0
      },
      currentFolder: null,
      breadcrumbs: [],
      
      // UI State
      loading: true,
      activeMenu: null,
      perPage: 10,
      successMessage: '',
      errorMessage: '',
      localSearchParams: {
        name: '',
        date: '',
        status: ''
      },
      
      // Context Menu
      contextMenu: {
        visible: false,
        x: 0,
        y: 0,
        folder: null,
        rowElement: null
      },
      
      // New dropdown state
      showNewDropdown: false,
      autoReloadInterval: null,
      autoReloadEnabled: true,
      lastUpdate: null  
    }
  },
  computed: {
    hasActiveFilters() {
      return this.localSearchParams.name || this.localSearchParams.date || this.localSearchParams.status;
    },
    uploadFileUrl() {
      const parentId = this.currentFolder ? this.currentFolder.folder_id : null;
      return `/upload?folder_id=${parentId}`;
    },
    pages() {
      const pages = [];
      const current = this.folders.current_page;
      const last = this.folders.last_page;
      const delta = 2;

      for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        pages.push(i);
      }

      if (current - delta > 2) {
        pages.unshift('...');
      }
      if (current + delta < last - 1) {
        pages.push('...');
      }

      pages.unshift(1);
      if (last > 1) {
        pages.push(last);
      }

      return pages;
    },
    contextMenuStyle() {
      if (!this.contextMenu.visible) {
        return {};
      }

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
  },
  mounted() {
    this.loadFolders();
    this.startAutoReload();
    document.addEventListener('click', this.closeMenu);
    document.addEventListener('keydown', this.handleKeydown);
    document.addEventListener('click', this.handleDocumentClick);
    document.addEventListener('scroll', this.hideContextMenu);
    window.addEventListener('resize', this.hideContextMenu);
    document.addEventListener('click', this.closeNewDropdownOutside);
  },
  beforeUnmount() {
    this.stopAutoReload();
    document.removeEventListener('click', this.closeMenu);
    document.removeEventListener('keydown', this.handleKeydown);
    document.removeEventListener('click', this.handleDocumentClick);
    document.removeEventListener('scroll', this.hideContextMenu);
    window.removeEventListener('resize', this.hideContextMenu);
    document.removeEventListener('click', this.closeNewDropdownOutside);
  },
  methods: {
    // ==================== API CALLS ====================
    async loadFolders() {
  this.loading = true;
  this.errorMessage = '';
  
  try {
    // Tạo params an toàn, loại bỏ các giá trị undefined/null
    const params = {
      name: this.localSearchParams.name || '',
      date: this.localSearchParams.date || '',
      status: this.localSearchParams.status || '',
      per_page: this.perPage || 10,
      page: this.folders.current_page || 1
    };

    // Thêm parent_id nếu có currentFolder và hợp lệ
    if (this.currentFolder && this.currentFolder.folder_id) {
      params.parent_id = this.currentFolder.folder_id;
    }

    // Loại bỏ các tham số rỗng
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null || params[key] === undefined) {
        delete params[key];
      }
    });

    console.log('📡 Loading folders with params:', params);

    const response = await axios.get('/api/folders', { params });
    
    if (response.data.success) {
      const data = response.data.data;
      this.folders = data.folders;
      this.currentFolder = data.currentFolder;
      this.breadcrumbs = data.breadcrumbs;
      
      console.log('✅ Load folders success:', data.folders?.data?.length || 0, 'folders loaded');
    } else {
      this.errorMessage = response.data.message || 'Lỗi khi tải dữ liệu';
    }
  } catch (error) {
    console.error('❌ API Error details:');
    console.error('Status:', error.response?.status);
    console.error('Data:', error.response?.data);
    console.error('URL:', error.config?.url);
    
    this.errorMessage = error.response?.data?.message || 'Lỗi kết nối đến server';
    
    // Hiển thị chi tiết lỗi validation nếu có
    if (error.response?.data?.errors) {
      const validationErrors = Object.values(error.response.data.errors).flat().join(', ');
      this.errorMessage += ` (${validationErrors})`;
    }
  } finally {
    this.loading = false;
  }
},

    async createFolder(folderData) {
      try {
        const response = await axios.post('/api/folders', folderData);
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async updateFolder(id, folderData) {
      try {
        const response = await axios.put(`/api/folders/${id}`, folderData);
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    showSuccessMessage(message) {
        this.successMessage = message;
        this.errorMessage = '';
        
        // Tự động ẩn sau 5 giây
        setTimeout(() => {
            this.successMessage = '';
        }, 5000);
    },

    showErrorMessage(message) {
        this.errorMessage = message;
        this.successMessage = '';
        
        // Tự động ẩn sau 8 giây
        setTimeout(() => {
            this.errorMessage = '';
        }, 8000);
    },

// Trong methods của FolderIndex.vue
      async deleteFolder(folder) {
    if (!confirm(`Bạn có chắc muốn xóa thư mục "${folder.name}"?`)) {
        return;
    }

    try {
        console.log('🗑️ Deleting folder:', folder);
        
        const result = await this.deleteFolderAPI(folder.folder_id);
        
        this.showSuccessMessage(result.message || 'Thư mục đã được xóa thành công!');
        
        // QUAN TRỌNG: Reset currentFolder nếu đang xóa folder hiện tại
        if (this.currentFolder && this.currentFolder.folder_id === folder.folder_id) {
            this.currentFolder = null;
        }
        
        await this.loadFolders();
        
    } catch (error) {
        console.error('Delete folder error:', error);
        
        if (error.response?.data?.message) {
            this.showErrorMessage(error.response.data.message);
        } else if (error.response?.data?.errors) {
            const errorMessages = Object.values(error.response.data.errors).flat().join(', ');
            this.showErrorMessage('Lỗi validation: ' + errorMessages);
        } else {
            this.showErrorMessage('Lỗi khi xóa thư mục. Vui lòng thử lại.');
        }
    }
},
      async deleteFolderAPI(id) {
        try {
            console.log('🗑️ Deleting folder with ID:', id);
            
            const response = await axios.delete(`/api/folders/${id}`);
            
            console.log('✅ Delete response:', response.data);
            return response.data;
            
        } catch (error) {
            console.error('❌ Delete API error details:');
            console.error('Status:', error.response?.status);
            console.error('Data:', error.response?.data);
            console.error('Headers:', error.response?.headers);
            
            throw error;
        }
    },

 
    // ==================== UI METHODS ====================
    toggleNewDropdown() {
      this.showNewDropdown = !this.showNewDropdown;
    },
    
    closeNewDropdown() {
      this.showNewDropdown = false;
    },
    
    closeNewDropdownOutside(event) {
      const dropdown = this.$el.querySelector('.relative');
      if (dropdown && !dropdown.contains(event.target)) {
        this.showNewDropdown = false;
      }
    },

    openCreateFolder() {
      const parentId = this.currentFolder ? this.currentFolder.folder_id : null;
      window.location.href = `/folders/create?parent_id=${parentId}`;
    },

    showContextMenu(event, folder) {
      event.preventDefault();
      event.stopPropagation();
      
      const rowElement = event.currentTarget;
      this.removeContextRowHighlight();
      rowElement.classList.add('context-menu-highlight');
      
      this.contextMenu = {
        visible: true,
        x: event.clientX,
        y: event.clientY,
        folder: folder,
        rowElement: rowElement
      };
      
      this.activeMenu = null;
    },
    
    hideContextMenu() {
      this.removeContextRowHighlight();
      this.contextMenu = {
        visible: false,
        x: 0,
        y: 0,
        folder: null,
        rowElement: null
      };
    },

    removeContextRowHighlight() {
      const highlightedRows = document.querySelectorAll('.context-menu-highlight');
      highlightedRows.forEach(row => {
        row.classList.remove('context-menu-highlight');
      });
    },
    
    openContextFolder() {
      if (this.contextMenu.folder) {
        this.goToFolder(this.contextMenu.folder.folder_id);
      }
      this.hideContextMenu();
    },

    editContextFolder() {
      if (this.contextMenu.folder) {
        this.editFolder(this.contextMenu.folder.folder_id);
      }
      this.hideContextMenu();
    },

    async deleteContextFolder() {
      if (this.contextMenu.folder) {
        await this.deleteFolder(this.contextMenu.folder);
      }
      this.hideContextMenu();
    },

    toggleMenu(folderId) {
      this.activeMenu = this.activeMenu === folderId ? null : folderId;
      this.hideContextMenu();
    },
    
    closeMenu(event) {
      if (!event.target.closest('.relative')) {
        this.activeMenu = null;
      }
    },
    
    handleKeydown(event) {
      if (event.key === 'Escape') {
        this.activeMenu = null;
        this.hideContextMenu();
      }
    },
    
    goToFolder(folderId) {
      this.currentFolder = { folder_id: folderId };
      this.folders.current_page = 1;
      this.loadFolders();
    },
    
    goToParent() {
      if (this.currentFolder && this.currentFolder.parent_folder_id) {
        this.goToFolder(this.currentFolder.parent_folder_id);
      }
    },
    
    goToRoot() {
      this.currentFolder = null;
      this.folders.current_page = 1;
      this.loadFolders();
    },

    editFolder(folderId) {
      window.location.href = `/folders/${folderId}/edit`;
    },
    
    handleSearch() {
      this.folders.current_page = 1;
      this.loadFolders();
    },
    
    resetFilters() {
      this.localSearchParams = { name: '', date: '', status: '' };
      this.perPage = 10;
      this.folders.current_page = 1;
      this.loadFolders();
    },
    
    changePerPage() {
      this.folders.current_page = 1;
      this.loadFolders();
    },
    
    changePage(page) {
      if (page === '...') return;
      this.folders.current_page = page;
      this.loadFolders();
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
    
    highlightText(text) {
      if (!this.localSearchParams.name) return text;
      const regex = new RegExp(`(${this.escapeRegExp(this.localSearchParams.name)})`, 'gi');
      return text.replace(regex, '<span class="highlight">$1</span>');
    },
    
    escapeRegExp(string) {
      return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    },

    handleDocumentClick(event) {
      setTimeout(() => {
        if (event.button === 0) {
          const contextMenuElement = document.querySelector('.context-menu');
          const isClickInsideMenu = contextMenuElement && contextMenuElement.contains(event.target);
          const isClickInsideTable = event.target.closest('tbody');
          
          if (!isClickInsideMenu && !isClickInsideTable) {
            this.hideContextMenu();
          }
        }
      }, 50);
    },
     startAutoReload() {
    // Auto reload mỗi 30 giây
    this.autoReloadInterval = setInterval(() => {
      if (this.autoReloadEnabled && !this.loading) {
        console.log('🔄 Auto-reloading folders...');
        this.loadFolders();
      }
    }, 30000); // 30 giây
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
    } else {
      this.stopAutoReload();
    }
  },
  
  manualReload() {
    this.loadFolders();
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
  }
}
</script>

<style scoped>
/* Giữ nguyên toàn bộ style từ component cũ */
.highlight {
  background-color: #ffeb3b;
  padding: 0 2px;
  border-radius: 2px;
  font-weight: bold;
}

.overlay {
  z-index: 11111 !important;
}

tbody tr {
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

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
  text-decoration: none;
  cursor: pointer;
  border-radius: 4px;
  margin: 2px 4px;
}

.context-menu-item:hover {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  transform: translateX(2px);
  text-decoration: none;
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

.context-menu-highlight {
  background: linear-gradient(90deg, #eff6ff 0%, #dbeafe 50%, #eff6ff 100%) !important;
  position: relative;
  border-left: 4px solid #3b82f6 !important;
}

tbody tr.context-menu-highlight {
  background: linear-gradient(90deg, #eff6ff 0%, #dbeafe 50%, #eff6ff 100%) !important;
  border-left: 4px solid #3b82f6 !important;
}

.context-menu-row {
  user-select: none;
  -webkit-user-select: none;
  border-left: 0px solid transparent !important;
}

.context-menu-row:hover {
  background-color: #f9fafb !important;
  border-left: 0px solid transparent !important;
}

.context-menu-row.context-menu-highlight:hover {
  background: linear-gradient(90deg, #eff6ff 0%, #dbeafe 50%, #eff6ff 100%) !important;
  border-left: 4px solid #3b82f6 !important;
}

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

@keyframes dropdownFadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Loading spinner */
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
</style>