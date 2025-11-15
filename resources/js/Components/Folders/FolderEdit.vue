<template>
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
      <!-- Header -->
      <div class="flex items-center mb-6">      
        <h1 class="text-2xl font-bold text-gray-800">Chỉnh sửa Thư Mục</h1>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        <span class="ml-3 text-gray-600">Đang tải thông tin thư mục...</span>
      </div>

      <!-- Error Message khi load data -->
      <div v-else-if="loadError" class="mt-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
        <div class="flex items-center">
          <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
          <div>
            <span class="font-medium">Lỗi khi tải thông tin thư mục!</span>
            <p class="text-sm mt-1">{{ loadError }}</p>
            <button @click="retryLoad" class="mt-2 px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
              Thử lại
            </button>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div v-else class="bg-white rounded-lg shadow p-6">
        <!-- Success Message -->
        <div v-if="successMessage" class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">
          <div class="flex items-center">
            <i class="fas fa-check-circle text-green-500 mr-3"></i>
            <span>{{ successMessage }}</span>
          </div>
        </div>

        <!-- Error Message khi submit -->
        <div v-if="errorMessage" class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
          <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
            <span>{{ errorMessage }}</span>
          </div>
        </div>

        <form @submit.prevent="submitForm">
          <!-- Tên thư mục -->
          <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
              Tên thư mục *
            </label>
            <input type="text" 
                   id="name" 
                   v-model="form.name"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                   :class="{ 'border-red-500': errors.name }"
                   placeholder="Nhập tên thư mục"
                   required>
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
          </div>

          <!-- Trạng thái -->
          <div class="mb-4">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
              Trạng thái *
            </label>
            <select id="status" 
                    v-model="form.status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-500': errors.status }"
                    required>
              <option value="">Chọn trạng thái</option>
              <option value="private">Riêng tư</option>
              <option value="public">Công khai</option>
            </select>
            <p v-if="errors.status" class="mt-1 text-sm text-red-600">{{ errors.status[0] }}</p>
          </div>

          <!-- Thư mục cha -->
          <div class="mb-6">
            <label for="parent_folder_id" class="block text-sm font-medium text-gray-700 mb-2">
              Thư mục cha (tùy chọn)
            </label>
            <select id="parent_folder_id" 
                    v-model="form.parent_folder_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-500': errors.parent_folder_id }">
              <option value="">-- Thư mục gốc --</option>
              <option v-for="parent in parentFolders" 
                      :key="parent.folder_id" 
                      :value="parent.folder_id"
                      :disabled="isCurrentOrDescendant(parent.folder_id)">
                {{ parent.name || parent.indented_name }}
              </option>
            </select>
            <p v-if="errors.parent_folder_id" class="mt-1 text-sm text-red-600">{{ errors.parent_folder_id[0] }}</p>
            <p v-if="form.parent_folder_id === folder.folder_id" class="mt-1 text-sm text-yellow-600">
              <i class="fas fa-exclamation-triangle mr-1"></i>
              Không thể chọn chính thư mục này làm thư mục cha
            </p>
          </div>

          <!-- Thông tin thêm -->
          <div class="mb-4 p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center">
              <i class="fas fa-info-circle text-blue-500 mr-2"></i>
              <span class="text-sm text-blue-700">
                Đang chỉnh sửa thư mục: <strong>{{ folder.name }}</strong>
                <span v-if="folder.parent_folder_id" class="ml-2">
                  (trong thư mục: {{ getParentFolderName(folder.parent_folder_id) }})
                </span>
              </span>
            </div>
          </div>

          <!-- Buttons -->
          <div class="flex justify-end space-x-3">
            <button type="button" 
                    @click="goBack"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
              Hủy
            </button>
            <button type="submit" 
                    :disabled="submitting"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
              <i class="fas fa-save mr-2"></i>
              {{ submitting ? 'Đang cập nhật...' : 'Cập nhật' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'FolderEdit',
  data() {
    return {
      // Data từ API
      folder: {},
      parentFolders: [],
      descendantIds: [],
      breadcrumbs: [],
      
      // Form data
      form: {
        name: '',
        status: '',
        parent_folder_id: ''
      },
      
      // UI State
      loading: true,
      submitting: false,
      errors: {},
      successMessage: '',
      errorMessage: '',
      loadError: ''
    }
  },
  mounted() {
    this.loadFolderData();
  },

  props: {
    folderId: {
      type: [String, Number],
      default: null
    }
  },
  methods: {
    // ==================== API CALLS ====================
    async loadFolderData() {
      this.loading = true;
      this.loadError = '';
      
      try {
        // FIX: Lấy folder ID từ URL chính xác
        // URL: /folders/1/edit -> cần lấy số 1
        const pathSegments = window.location.pathname.split('/').filter(segment => segment);        
        
        // Tìm folder ID (số) trong URL
        let folderId = null;
        for (let i = 0; i < pathSegments.length; i++) {
          if (pathSegments[i] === 'folders' && i + 1 < pathSegments.length) {
            // Phần tử sau 'folders' là folder ID
            const potentialId = pathSegments[i + 1];
            if (!isNaN(potentialId) && potentialId !== 'create' && potentialId !== 'edit') {
              folderId = potentialId;
              break;
            }
          }
        }
        
        // Fallback: lấy phần tử thứ 2 từ URL (sau /folders/)
        if (!folderId && pathSegments.length >= 2) {
          const potentialId = pathSegments[1];
          if (!isNaN(potentialId) && potentialId !== 'create') {
            folderId = potentialId;
          }
        }
        
        if (!folderId) {
          throw new Error('Không tìm thấy ID thư mục trong URL');
        }
        
        const apiUrl = `/api/folders/${folderId}`;
        
        const response = await axios.get(apiUrl);
        
        if (response.data.success) {
          const data = response.data.data;
          
          this.folder = data.folder || {};
          this.parentFolders = data.parentFolders || [];
          this.descendantIds = data.descendantIds || [];
          this.breadcrumbs = data.breadcrumbs || [];
          
          // Fill form với data hiện tại
          this.form = {
            name: this.folder.name || '',
            status: this.folder.status || '',
            parent_folder_id: this.folder.parent_folder_id || ''
          };
          
        } else {
          this.loadError = response.data.message || 'Lỗi khi tải dữ liệu thư mục';
        }
      } catch (error) {
        
        if (error.message.includes('Không tìm thấy ID')) {
          this.loadError = error.message;
        } else if (error.response?.status === 404) {
          this.loadError = 'Không tìm thấy thư mục. Có thể thư mục đã bị xóa.';
        } else if (error.response?.status === 500) {
          this.loadError = 'Lỗi server: ' + (error.response.data.message || 'Error loading folder');
        } else {
          this.loadError = error.response?.data?.message || 'Lỗi kết nối đến server: ' + error.message;
        }
      } finally {
        this.loading = false;
      }
    },

    async submitForm() {
      this.submitting = true;
      this.errors = {};
      this.successMessage = '';
      this.errorMessage = '';

      try {
        const folderId = this.folder.folder_id;
        const response = await axios.put(`/api/folders/${folderId}`, this.form);

        if (response.data.success) {
          this.successMessage = response.data.message;
          
          // Cập nhật data local sau khi thành công
          this.folder = { ...this.folder, ...response.data.data };
          
          // Redirect sau 1.5 giây
          setTimeout(() => {
            const parentId = response.data.data.parent_folder_id || this.folder.parent_folder_id;
            const redirectUrl = parentId 
              ? `/folders?parent_id=${parentId}`
              : '/folders';
            window.location.href = redirectUrl;
          }, 1500);
        } else {
          this.errorMessage = response.data.message || 'Lỗi khi cập nhật thư mục';
        }
      } catch (error) {
        console.error('Submit Error:', error);
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors || {};
          this.errorMessage = 'Vui lòng kiểm tra lại thông tin đã nhập.';
        } else if (error.response && error.response.data.message) {
          this.errorMessage = error.response.data.message;
        } else {
          this.errorMessage = 'Đã có lỗi xảy ra khi cập nhật thư mục. Vui lòng thử lại.';
        }
      } finally {
        this.submitting = false;
      }
    },

    // ==================== UI METHODS ====================
    isCurrentOrDescendant(folderId) {
      return folderId === this.folder.folder_id || this.descendantIds.includes(folderId);
    },

    getParentFolderName(parentId) {
      const parent = this.parentFolders.find(f => f.folder_id == parentId);
      return parent ? parent.name : 'Không xác định';
    },

    goBack() {
      const parentId = this.folder.parent_folder_id;
      const backUrl = parentId 
        ? `/folders?parent_id=${parentId}`
        : '/folders';
      window.location.href = backUrl;
    },

    retryLoad() {
      this.loadFolderData();
    }
  }
}
</script>