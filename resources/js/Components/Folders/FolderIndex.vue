<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Tiêu đề -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Quản Lý Thư Mục</h1>
    </div>

    <!-- Button thêm folder -->
    <div class="mb-6">
      <a :href="createFolderUrl" 
         class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center w-fit">
        <i class="fas fa-plus mr-2"></i>
        Tạo Thư Mục Mới
      </a>
    </div>

    <!-- Tìm kiếm & lọc -->
    <div class="mb-6">
      <form @submit.prevent="handleSearch" class="flex items-center space-x-2 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <input v-if="currentFolder" type="hidden" name="parent_id" :value="currentFolder.folder_id">
        
        <!-- Tìm theo tên -->
        <div class="relative">
          <input type="text" 
                 v-model="searchParams.name"
                 placeholder="Tìm theo tên..." 
                 class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-48">
          <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>
        
        <!-- Tìm theo ngày -->
        <div class="relative">
          <input type="date" 
                 v-model="searchParams.date"
                 class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <i class="fas fa-calendar absolute left-3 top-3 text-gray-400"></i>
        </div>
        
        <!-- Lọc theo trạng thái -->
        <div class="relative">
          <select v-model="searchParams.status" 
                  class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-40">
            <option value="">Tất cả trạng thái</option>
            <option value="public">Công khai</option>
            <option value="private">Riêng tư</option>
          </select>
          <i class="fas fa-filter absolute left-3 top-3 text-gray-400"></i>
        </div>
        
        <!-- Nút tìm kiếm -->
        <button type="submit" 
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
          <i class="fas fa-search mr-2"></i>
          Tìm kiếm
        </button>
        
        <!-- Nút reset -->
        <button v-if="hasActiveFilters" 
                @click="resetFilters"
                type="button"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center">
          <i class="fas fa-times mr-2"></i>
          Reset
        </button>
      </form>
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
            <span v-if="searchParams.name"><strong>"{{ searchParams.name }}"</strong></span>
            <span v-if="searchParams.name && (searchParams.date || searchParams.status)"> và </span>
            <span v-if="searchParams.date"> ngày <strong>{{ formatDate(searchParams.date) }}</strong></span>
            <span v-if="searchParams.date && searchParams.status"> và </span>
            <span v-if="searchParams.status">
              <strong>{{ searchParams.status == 'public' ? 'Công khai' : 'Riêng tư' }}</strong>
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
          <tr v-for="folder in folders.data" :key="folder.folder_id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap cursor-pointer" 
                @click="goToFolder(folder.folder_id)">
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
            <td class="px-6 py-4 whitespace-nowrap cursor-pointer" 
                @click="goToFolder(folder.folder_id)">
              <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', 
                           folder.status == 'public' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                <i :class="['fas mr-1', folder.status == 'public' ? 'fa-globe' : 'fa-lock']"></i>
                {{ folder.status == 'public' ? 'Công khai' : 'Riêng tư' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer" 
                @click="goToFolder(folder.folder_id)">
              {{ formatDateTime(folder.created_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 cursor-pointer" 
                @click="goToFolder(folder.folder_id)">
              {{ folder.documents_count }} files
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium align-middle">
              <!-- Dropdown Menu -->
              <div class="relative inline-block text-left">
                <button type="button" 
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        @click.stop="toggleMenu(folder.folder_id)">
                  <i class="fas fa-ellipsis-v text-gray-500"></i>
                </button>
                
                <!-- Dropdown panel -->
                <div v-if="activeMenu === folder.folder_id"
                    class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10"
                    style="z-index: 11111;">
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
          <tr v-if="folders.data.length === 0">
            <td colspan="5" class="px-6 py-8 text-center">
              <div class="flex flex-col items-center justify-center text-gray-400">
                <i class="fas fa-folder-open text-4xl mb-2"></i>
                <p class="text-lg">
                  {{ hasActiveFilters ? 'Không tìm thấy thư mục nào phù hợp' : 'Không có thư mục nào' }}
                </p>
                <p class="text-sm mt-1">
                  {{ hasActiveFilters ? 'Hãy thử điều chỉnh từ khóa tìm kiếm hoặc bộ lọc' : 'Hãy tạo thư mục đầu tiên' }}
                </p>
                <button v-if="hasActiveFilters" 
                        @click="resetFilters"
                        class="mt-3 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                  <i class="fas fa-times mr-2"></i>
                  Xóa bộ lọc
                </button>
                <a v-else :href="createFolderUrl" 
                   class="mt-3 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                  <i class="fas fa-plus mr-2"></i>
                  Tạo Thư Mục Mới
                </a>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

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
      search: {
        name: this.searchParams.name || '',
        date: this.searchParams.date || '',
        status: this.searchParams.status || ''
      }
    }
  },
  computed: {
    hasActiveFilters() {
      return this.search.name || this.search.date || this.search.status;
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
    }
  },
  mounted() {
    // Đóng menu khi click ra ngoài
    document.addEventListener('click', this.closeMenu);
    // Đóng menu khi nhấn Escape
    document.addEventListener('keydown', this.handleKeydown);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.closeMenu);
    document.removeEventListener('keydown', this.handleKeydown);
  },
  methods: {
    route(name, params = null) {
      // Helper function để tạo URL giống Laravel route()
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
    toggleMenu(folderId) {
      this.activeMenu = this.activeMenu === folderId ? null : folderId;
    },
    closeMenu(event) {
      if (!event.target.closest('.relative')) {
        this.activeMenu = null;
      }
    },
    handleKeydown(event) {
      if (event.key === 'Escape') {
        this.activeMenu = null;
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
      this.search = { name: '', date: '', status: '' };
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
      
      // Cập nhật search params
      if (this.search.name) {
        url.searchParams.set('name', this.search.name);
      } else {
        url.searchParams.delete('name');
      }
      
      if (this.search.date) {
        url.searchParams.set('date', this.search.date);
      } else {
        url.searchParams.delete('date');
      }
      
      if (this.search.status) {
        url.searchParams.set('status', this.search.status);
      } else {
        url.searchParams.delete('status');
      }
      
      // Cập nhật per_page
      url.searchParams.set('per_page', this.perPage);
      
      // Xóa page khi thay đổi filter hoặc per_page
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
      if (!this.search.name) return text;
      const regex = new RegExp(`(${this.escapeRegExp(this.search.name)})`, 'gi');
      return text.replace(regex, '<span class="highlight">$1</span>');
    },
    escapeRegExp(string) {
      return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
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
</style>