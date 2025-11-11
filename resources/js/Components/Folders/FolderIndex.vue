<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Tiêu đề -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Quản Lý Thư Mục</h1>
    </div>

    <!-- Header với Button và Search cùng dòng -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
      <!-- Button thêm folder -->
      <div class="flex-shrink-0">
        <a :href="createFolderUrl" 
           class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center w-fit no-underline transition-colors duration-200">
          <i class="fas fa-plus mr-2"></i>
          Tạo Thư Mục Mới
        </a>
      </div>

      <!-- Tìm kiếm & lọc -->
      <div class="flex-1 min-w-0">
        <form @submit.prevent="handleSearch" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-white rounded-lg shadow-sm border border-gray-200 p-3">
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
          <button type="submit" 
                  class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-colors duration-200 flex-1 sm:flex-none">
            <i class="fas fa-search mr-2"></i>
            Tìm kiếm
          </button>
          
          <!-- Nút reset -->
          <button v-if="hasActiveFilters" 
                  @click="resetFilters"
                  type="button"
                  class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-colors duration-200 flex-1 sm:flex-none">
            <i class="fas fa-times mr-2"></i>
            Reset
          </button>
        </form>
      </div>
    </div>

    <!-- Breadcrumbs và nút back -->
    <div class="mb-6">
      <nav v-if="breadcrumbs.length > 0" class="flex items-center justify-between mb-4">
        <!-- Breadcrumbs -->
        <div class="flex items-center">
          <ol class="flex items-center space-x-2 text-sm">
            <li>
              <a :href="route('folders.index')" class="text-blue-500 hover:text-blue-700 flex items-center">
                <i class="fas fa-home mr-1"></i> Root
              </a>
            </li>
            <li v-for="(crumb, index) in breadcrumbs" :key="crumb.folder_id" class="flex items-center">
              <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
              <a v-if="index < breadcrumbs.length - 1" 
                 :href="route('folders.show', crumb.folder_id)" 
                 class="text-blue-500 hover:text-blue-700">
                {{ crumb.name }}
              </a>
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

    <!-- Thông báo kết quả tìm kiếm -->
    <div v-if="hasActiveFilters" class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
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
<div class="bg-white rounded-lg shadow overflow-hidden mb-6">
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
                    <a :href="route('folders.edit', folder.folder_id)" 
                      class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                      <i class="fas fa-edit mr-3 text-blue-500"></i>
                      Chỉnh sửa
                    </a>
                    
                    <!-- Delete -->
                    <form :action="route('folders.destroy', folder.folder_id)" method="POST" class="inline">
                      <input type="hidden" name="_token" :value="csrfToken">
                      <input type="hidden" name="_method" value="DELETE">
                      <button type="submit" 
                              class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                              @click="confirmDelete(folder)">
                        <i class="fas fa-trash mr-3 text-red-500"></i>
                        Xóa
                      </button>
                    </form>
                  </div>
                </div>
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
      <a :href="route('folders.edit', contextMenu.folder.folder_id)" 
         class="context-menu-item"
         @click="hideContextMenu">
        <i class="fas fa-edit text-green-500 me-3" style="width: 16px;"></i>
        Chỉnh sửa
      </a>
      
      <!-- Divider -->
      <div class="context-menu-divider"></div>
      
      <!-- Delete -->
      <form :action="route('folders.destroy', contextMenu.folder.folder_id)" method="POST" class="w-100">
        <input type="hidden" name="_token" :value="csrfToken">
        <input type="hidden" name="_method" value="DELETE">
        <button type="submit" 
                class="context-menu-item context-menu-item-danger w-full text-start"
                @click="confirmDeleteContext">
          <i class="fas fa-trash text-red-500 me-3" style="width: 16px;"></i>
          Xóa thư mục
        </button>
      </form>
    </div>
  </div>

<!-- Overlay để đóng context menu khi click ra ngoài -->
<div v-if="contextMenu.visible" 
     class="context-menu-overlay"
     @click="hideContextMenu"></div>
     

    <!-- Phân trang và điều khiển hiển thị -->
    <div class="flex items-center justify-between bg-white px-4 py-3 rounded-lg shadow border-t border-gray-200">
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
export default {
  name: 'FolderIndex',
  props: {
    initialFolders: {
      type: Object,
      required: true
    },
    currentFolder: {
      type: Object,
      default: null
    },
    breadcrumbs: {
      type: Array,
      default: () => []
    },
    success: {
      type: String,
      default: null
    },
    error: {
      type: String,
      default: null
    },
    searchParams: {
      type: Object,
      default: () => ({
        name: '',
        date: '',
        status: '',
        per_page: 10
      })
    }
  },
  data() {
    return {
      folders: this.initialFolders,
      activeMenu: null,
      perPage: this.searchParams.per_page || 10,
      successMessage: this.success,
      errorMessage: this.error,
      localSearchParams: {
        name: this.searchParams.name || '',
        date: this.searchParams.date || '',
        status: this.searchParams.status || ''
      },
      // Context Menu Data
      contextMenu: {
        visible: false,
        x: 0,
        y: 0,
        folder: null,
        rowElement: null
      }
    }
  },
  computed: {
    hasActiveFilters() {
      return this.localSearchParams.name || this.localSearchParams.date || this.localSearchParams.status;
    },
    createFolderUrl() {
      const parentId = this.currentFolder ? this.currentFolder.folder_id : null;
      return `/folders/create?parent_id=${parentId}`;
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
    csrfToken() {
      return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
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
      
      // Nếu không đủ chỗ bên phải, hiển thị bên trái con trỏ
      if (x + menuWidth > viewportWidth) {
        x = x - menuWidth;
      }
      
      // Nếu không đủ chỗ bên dưới, hiển thị phía trên con trỏ
      if (y + menuHeight > viewportHeight) {
        y = y - menuHeight;
      }
      
      // Đảm bảo không vượt ra ngoài viewport
      x = Math.max(padding, Math.min(x, viewportWidth - menuWidth - padding));
      y = Math.max(padding, Math.min(y, viewportHeight - menuHeight - padding));
      
      return {
        left: x + 'px',
        top: y + 'px'
      };
    },
  },
  mounted() {
    // Đóng menu khi click ra ngoài
    document.addEventListener('click', this.closeMenu);
    // Đóng menu khi nhấn Escape
    document.addEventListener('keydown', this.handleKeydown);
    // Đóng context menu khi click ra ngoài
    document.addEventListener('click', this.handleDocumentClick);
    // Đóng context menu khi scroll
    document.addEventListener('scroll', this.hideContextMenu);
    // Đóng context menu khi resize
    window.addEventListener('resize', this.hideContextMenu);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.closeMenu);
    document.removeEventListener('keydown', this.handleKeydown);
    document.removeEventListener('click', this.handleDocumentClick);
    document.removeEventListener('scroll', this.hideContextMenu);
    window.removeEventListener('resize', this.hideContextMenu);
  },
  methods: {
    /**
     * Hiển thị context menu khi click chuột phải
     */
    showContextMenu(event, folder) {
      event.preventDefault();
      event.stopPropagation();
      
      // Lấy element của dòng được click
      const rowElement = event.currentTarget;
      
      // Xóa highlight cũ và thêm highlight mới
      this.removeContextRowHighlight();
      rowElement.classList.add('context-menu-highlight');
      
      this.contextMenu = {
        visible: true,
        x: event.clientX,
        y: event.clientY,
        folder: folder,
        rowElement: rowElement
      };
      
      // Đóng dropdown menu nếu đang mở
      this.activeMenu = null;
    },
    
    /**
     * Ẩn context menu và bỏ highlight
     */
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

    /**
     * Bỏ highlight tất cả các dòng
     */
      removeContextRowHighlight() {
      const highlightedRows = document.querySelectorAll('.context-menu-highlight');
      highlightedRows.forEach(row => {
        row.classList.remove('context-menu-highlight');
      });
    },
    
    /**
     * Mở thư mục từ context menu
     */
    openContextFolder() {
      if (this.contextMenu.folder) {
        this.goToFolder(this.contextMenu.folder.folder_id);
      }
      this.hideContextMenu();
    },
    
    /**
     * Xác nhận xóa từ context menu
     */
    confirmDeleteContext(event) {
      if (this.contextMenu.folder && !confirm(`Bạn có chắc chắn muốn xóa thư mục "${this.contextMenu.folder.name}"?`)) {
        event.preventDefault();
        event.stopPropagation();
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
    
    confirmDelete(folder) {
      if (!confirm(`Bạn có chắc chắn muốn xóa thư mục ${folder.name}?`)) {
        event.preventDefault();
      }
    },
    
    goToFolder(folderId) {
      window.location.href = this.route('folders.show', folderId);
    },
    
    route(name, params = null) {
      const baseUrl = window.location.origin;
      const routes = {
        'folders.index': '/folders',
        'folders.create': '/folders/create',
        'folders.show': (id) => `/folders/${id}`,
        'folders.edit': (id) => `/folders/${id}/edit`,
        'folders.destroy': (id) => `/folders/${id}`
      };

      if (typeof routes[name] === 'function') {
        return baseUrl + routes[name](params);
      }
      return baseUrl + routes[name];
    },
    
    goToParent() {
      if (this.currentFolder && this.currentFolder.parent_folder_id) {
        window.location.href = this.route('folders.show', this.currentFolder.parent_folder_id);
      }
    },
    
    goToRoot() {
      window.location.href = this.route('folders.index');
    },
    
    handleSearch() {
      this.updateUrl();
    },
    
    resetFilters() {
      this.localSearchParams = { name: '', date: '', status: '' };
      this.perPage = 10;
      this.updateUrl();
    },
    
    changePerPage() {
      this.updateUrl();
    },
    
    changePage(page) {
      if (page === '...') return;
      const url = new URL(window.location.href);
      url.searchParams.set('page', page);
      window.location.href = url.toString();
    },
    
    updateUrl() {
      const url = new URL(window.location.href);
      
      if (this.localSearchParams.name) {
        url.searchParams.set('name', this.localSearchParams.name);
      } else {
        url.searchParams.delete('name');
      }
      
      if (this.localSearchParams.date) {
        url.searchParams.set('date', this.localSearchParams.date);
      } else {
        url.searchParams.delete('date');
      }
      
      if (this.localSearchParams.status) {
        url.searchParams.set('status', this.localSearchParams.status);
      } else {
        url.searchParams.delete('status');
      }
      
      url.searchParams.set('per_page', this.perPage);
      url.searchParams.delete('page');

      window.location.href = url.toString();
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

    /**
     * Xử lý click document để đóng context menu
     */
    handleDocumentClick(event) {
      // Thêm timeout để tránh đóng menu ngay khi vừa mở
      setTimeout(() => {
        // Chỉ xử lý click chuột trái
        if (event.button === 0) {
          const contextMenuElement = document.querySelector('.context-menu');
          const isClickInsideMenu = contextMenuElement && contextMenuElement.contains(event.target);
          const isClickInsideTable = event.target.closest('tbody');
          
          if (!isClickInsideMenu && !isClickInsideTable) {
            this.hideContextMenu();
          }
        }
      }, 50);
    }
  }
}
</script>
<style scoped>
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

/* FIXED: Highlight cho dòng được click chuột phải */
.context-menu-highlight {
  background: linear-gradient(90deg, #eff6ff 0%, #dbeafe 50%, #eff6ff 100%) !important;
  position: relative;
  border-left: 4px solid #3b82f6 !important;
}

/* Đảm bảo không bị ghi đè bởi các class khác */
tbody tr.context-menu-highlight {
  background: linear-gradient(90deg, #eff6ff 0%, #dbeafe 50%, #eff6ff 100%) !important;
  border-left: 4px solid #3b82f6 !important;
}

/* Loại bỏ các style conflict */
.context-menu-row {
  user-select: none;
  -webkit-user-select: none;
  border-left: 0px solid transparent !important;
}

/* Đảm bảo hover không gây conflict */
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

/* Responsive cho header */
@media (max-width: 1024px) {
  .flex-col.lg\:flex-row {
    flex-direction: column;
  }
  
  .flex-1.min-w-0 {
    min-width: 100%;
  }
}

@media (max-width: 768px) {
  .flex-col.sm\:flex-row {
    flex-direction: column;
  }
  
  .sm\:flex-none {
    flex: 1;
  }
  
  .sm\:w-48,
  .sm\:w-40 {
    width: 100% !important;
  }
}
</style>