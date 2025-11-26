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
            <p class="text-sm mt-1">{{ sanitizeOutput(loadError) }}</p>
            <button @click="retryLoad" class="mt-2 px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
              Thử lại
            </button>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div v-else class="bg-white rounded-lg shadow p-6">
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
                   required
                   maxlength="255"
                   @input="sanitizeFormField('name')">
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ sanitizeOutput(errors.name[0]) }}</p>
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
            <p v-if="errors.status" class="mt-1 text-sm text-red-600">{{ sanitizeOutput(errors.status[0]) }}</p>
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
                {{ sanitizeOutput(parent.name || parent.indented_name) }}
              </option>
            </select>
            <p v-if="errors.parent_folder_id" class="mt-1 text-sm text-red-600">{{ sanitizeOutput(errors.parent_folder_id[0]) }}</p>
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
                Đang chỉnh sửa thư mục: <strong>{{ sanitizeOutput(folder.name) }}</strong>
                <span v-if="folder.parent_folder_id" class="ml-2">
                  (trong thư mục: {{ sanitizeOutput(getParentFolderName(folder.parent_folder_id)) }})
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
    
    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center mb-4">
            <i class="fas fa-check-circle text-green-500 text-2xl mr-3"></i>
            <h3 class="text-lg font-medium text-gray-900">Thành công</h3>
          </div>
          <p class="text-sm text-gray-600 mb-6">
            {{ sanitizeOutput(successMessage) }}
          </p>
          <div class="flex justify-end space-x-3">
            <button @click="continueAfterSuccess" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg flex items-center transition-colors">
              <i class="fas fa-check mr-2"></i>Tiếp tục
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Error Modal -->
    <div v-if="showErrorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center mb-4">
            <i class="fas fa-exclamation-circle text-red-500 text-2xl mr-3"></i>
            <h3 class="text-lg font-medium text-gray-900">Lỗi</h3>
          </div>
          <p class="text-sm text-gray-600 mb-6">
            {{ sanitizeOutput(errorMessage) }}
          </p>
          <div class="flex justify-end space-x-3">
            <button @click="hideErrorModal" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
              Đóng
            </button>
          </div>
        </div>
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
      loadError: '',
      
      // ✅ THÊM: Modal states
      showSuccessModal: false,
      showErrorModal: false
    }
  },

  props: {
    folderId: {
      type: [String, Number],
      default: null
    }
  },

  methods: {
    // ✅ BẢO MẬT: Sanitize output để tránh XSS
    sanitizeOutput(value) {
      if (value === null || value === undefined) return '';
      const div = document.createElement('div');
      div.textContent = value.toString();
      return div.innerHTML;
    },

    // ✅ BẢO MẬT: Sanitize input để tránh XSS
    sanitizeInput(value) {
      if (value === null || value === undefined) return '';
      const div = document.createElement('div');
      div.textContent = value.toString();
      return div.innerHTML.replace(/[<>]/g, '').substring(0, 255);
    },

    // ✅ BẢO MẬT: Sanitize form field
    sanitizeFormField(fieldName) {
      if (this.form[fieldName]) {
        this.form[fieldName] = this.sanitizeInput(this.form[fieldName]);
      }
    },

    // ✅ BẢO MẬT: Validate folder ID
    validateFolderId(folderId) {
      if (!folderId || !Number.isInteger(Number(folderId)) || folderId <= 0) {
        throw new Error('ID thư mục không hợp lệ');
      }
      return Number(folderId);
    },

    // ✅ BẢO MẬT: Sanitize URL parameters
    sanitizeUrlParam(param) {
      if (!param) return '';
      return encodeURIComponent(param.toString());
    },

    // ✅ THÊM: Modal methods
    continueAfterSuccess() {
      this.showSuccessModal = false;
      this.successMessage = '';
      
      // ✅ BẢO MẬT: Sanitize URL parameters
      const parentId = this.folder.parent_folder_id || this.form.parent_folder_id;
      const safeParentId = parentId ? this.sanitizeUrlParam(parentId) : '';
      const redirectUrl = parentId 
        ? `/folders?parent_id=${safeParentId}`
        : '/folders';
      window.location.href = redirectUrl;
    },

    hideErrorModal() {
      this.showErrorModal = false;
      this.errorMessage = '';
    },

    showSuccess(message) {
      this.successMessage = this.sanitizeOutput(message);
      this.showSuccessModal = true;
    },

    showError(message) {
      this.errorMessage = this.sanitizeOutput(message);
      this.showErrorModal = true;
    },

    // ==================== API CALLS ====================
    async loadFolderData() {
      this.loading = true;
      this.loadError = '';
      
      try {
        // ✅ BẢO MẬT: Lấy folder ID từ URL một cách an toàn
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

        // ✅ BẢO MẬT: Validate folder ID
        const validFolderId = this.validateFolderId(folderId);
        
        const apiUrl = `/api/folders/${validFolderId}`;
        
        const response = await axios.get(apiUrl);
        
        if (response.data.success) {
          const data = response.data.data;
          
          // ✅ BẢO MẬT: Sanitize data từ API
          this.folder = data.folder || {};
          this.parentFolders = (data.parentFolders || []).map(folder => ({
            ...folder,
            name: this.sanitizeOutput(folder.name),
            indented_name: folder.indented_name ? this.sanitizeOutput(folder.indented_name) : ''
          }));
          this.descendantIds = data.descendantIds || [];
          this.breadcrumbs = (data.breadcrumbs || []).map(crumb => ({
            ...crumb,
            name: this.sanitizeOutput(crumb.name)
          }));
          
          // Fill form với data hiện tại (đã được sanitize)
          this.form = {
            name: this.sanitizeOutput(this.folder.name || ''),
            status: this.folder.status || '',
            parent_folder_id: this.folder.parent_folder_id || ''
          };
          
        } else {
          this.loadError = this.sanitizeOutput(response.data.message || 'Lỗi khi tải dữ liệu thư mục');
        }
      } catch (error) {
        console.error('Load folder error:', error);
        
        // ✅ BẢO MẬT: Sanitize error messages
        if (error.message.includes('Không tìm thấy ID')) {
          this.loadError = this.sanitizeOutput(error.message);
        } else if (error.response?.status === 404) {
          this.loadError = 'Không tìm thấy thư mục. Có thể thư mục đã bị xóa.';
        } else if (error.response?.status === 500) {
          this.loadError = 'Lỗi server: ' + this.sanitizeOutput(error.response.data?.message || 'Error loading folder');
        } else if (error.response?.data?.message) {
          this.loadError = this.sanitizeOutput(error.response.data.message);
        } else {
          this.loadError = 'Lỗi kết nối đến server: ' + this.sanitizeOutput(error.message);
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
        // ✅ BẢO MẬT: Validate folder ID
        const folderId = this.validateFolderId(this.folder.folder_id);

        // ✅ BẢO MẬT: Sanitize form data trước khi gửi
        const sanitizedForm = {
          name: this.sanitizeInput(this.form.name),
          status: this.form.status,
          parent_folder_id: this.form.parent_folder_id || null
        };

        const response = await axios.put(`/api/folders/${folderId}`, sanitizedForm);

        if (response.data.success) {
          // ✅ THAY THẾ: Hiển thị modal thông báo thành công
          this.showSuccess(response.data.message);
          
          // Cập nhật data local sau khi thành công
          this.folder = { 
            ...this.folder, 
            ...response.data.data,
            name: this.sanitizeOutput(response.data.data?.name || this.folder.name)
          };
        } else {
          // ✅ THAY THẾ: Hiển thị modal thông báo lỗi
          this.showError(response.data.message || 'Lỗi khi cập nhật thư mục');
        }
      } catch (error) {
        console.error('Submit Error:', error);
        
        // ✅ BẢO MẬT: Sanitize error handling
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors || {};
          // Sanitize error messages
          Object.keys(this.errors).forEach(key => {
            if (Array.isArray(this.errors[key])) {
              this.errors[key] = this.errors[key].map(msg => this.sanitizeOutput(msg));
            }
          });
          this.showError('Vui lòng kiểm tra lại thông tin đã nhập.');
        } else if (error.response && error.response.data.message) {
          this.showError(error.response.data.message);
        } else if (error.message) {
          this.showError('Đã có lỗi xảy ra khi cập nhật thư mục. Vui lòng thử lại.');
        } else {
          this.showError('Lỗi không xác định. Vui lòng thử lại.');
        }
      } finally {
        this.submitting = false;
      }
    },

    // ==================== UI METHODS ====================
    isCurrentOrDescendant(folderId) {
      try {
        const validFolderId = this.validateFolderId(folderId);
        return validFolderId === this.folder.folder_id || this.descendantIds.includes(validFolderId);
      } catch {
        return false;
      }
    },

    getParentFolderName(parentId) {
      try {
        const validParentId = this.validateFolderId(parentId);
        const parent = this.parentFolders.find(f => f.folder_id == validParentId);
        return parent ? this.sanitizeOutput(parent.name) : 'Không xác định';
      } catch {
        return 'Không xác định';
      }
    },

    goBack() {
      // ✅ BẢO MẬT: Sanitize URL parameters
      const parentId = this.folder.parent_folder_id;
      const safeParentId = parentId ? this.sanitizeUrlParam(parentId) : '';
      const backUrl = parentId 
        ? `/folders?parent_id=${safeParentId}`
        : '/folders';
      window.location.href = backUrl;
    },

    retryLoad() {
      this.loadFolderData();
    },

    // ✅ THÊM: Handle Escape key
    handleKeydown(event) {
      if (event.key === 'Escape') {
        if (this.showSuccessModal) {
          this.continueAfterSuccess();
        }
        if (this.showErrorModal) {
          this.hideErrorModal();
        }
      }
    },

    // ✅ BẢO MẬT: Prevent form submission on Enter in inputs
    preventEnterSubmit(event) {
      if (event.key === 'Enter' && event.target.tagName !== 'FORM') {
        event.preventDefault();
      }
    }
  },
  
  // ✅ THÊM: Event listeners cho phím
  mounted() {
    this.loadFolderData();
    document.addEventListener('keydown', this.handleKeydown);
    document.addEventListener('keydown', this.preventEnterSubmit);
  },
  
  beforeUnmount() {
    document.removeEventListener('keydown', this.handleKeydown);
    document.removeEventListener('keydown', this.preventEnterSubmit);
  }
}
</script>

<style scoped>
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

/* Smooth transitions for modals */
.fixed {
  transition: opacity 0.3s ease;
}
</style>