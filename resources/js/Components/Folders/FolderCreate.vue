<template>
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
      <!-- Header -->
      <div class="flex items-center mb-6">
        <button @click="goBack" class="text-blue-500 hover:text-blue-700 mr-4">
          <i class="fas fa-arrow-left"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800">Tạo Thư Mục Mới</h1>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>

      <!-- Form -->
      <div v-else class="bg-white rounded-lg shadow p-6">
        <form @submit.prevent="submitForm" @keydown="preventEnterSubmit">
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
          <div class="mb-6">
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

          <!-- Thông báo vị trí -->
          <div class="mb-4 p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center">
              <i class="fas fa-info-circle text-blue-500 mr-2"></i>
              <span class="text-sm text-blue-700">
                Thư mục sẽ được tạo trong <strong>{{ sanitizeOutput(locationText) }}</strong>
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
                    :disabled="submitting || !isFormValid"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
              <i class="fas fa-folder-plus mr-2"></i>
              {{ submitting ? 'Đang tạo...' : 'Tạo Thư Mục' }}
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
  name: 'FolderCreate',
  data() {
    return {
      // Form data
      form: {
        name: '',
        status: '',
        parent_folder_id: null
      },
      
      // UI State
      loading: false,
      submitting: false,
      errors: {},
      successMessage: '',
      errorMessage: '',
      
      // Location info
      parentFolderName: 'Danh sách hiện tại (Thư mục gốc)',
      
      // ✅ THÊM: Modal states
      showSuccessModal: false,
      showErrorModal: false
    }
  },
  computed: {
    locationText() {
      return this.parentFolderName;
    },
    
    // ✅ BẢO MẬT: Client-side form validation
    isFormValid() {
      return this.form.name.trim().length > 0 && 
             this.form.name.length <= 255 &&
             ['private', 'public'].includes(this.form.status);
    }
  },
  mounted() {
    this.getLocationInfo();
    document.addEventListener('keydown', this.handleKeydown);
  },
  
  beforeUnmount() {
    document.removeEventListener('keydown', this.handleKeydown);
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
      return div.innerHTML
        .replace(/[<>"'`]/g, '')
        .replace(/javascript:/gi, '')
        .replace(/on\w+=/gi, '')
        .substring(0, 255)
        .trim();
    },

    // ✅ BẢO MẬT: Sanitize form field
    sanitizeFormField(fieldName) {
      if (this.form[fieldName]) {
        this.form[fieldName] = this.sanitizeInput(this.form[fieldName]);
      }
    },

    // ✅ BẢO MẬT: Validate folder ID
    validateFolderId(folderId) {
      if (!folderId) return null;
      if (!Number.isInteger(Number(folderId)) || folderId <= 0) {
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
      const parentId = this.form.parent_folder_id;
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
    },

    // ==================== API CALLS ====================
    async getLocationInfo() {
      try {
        // ✅ BẢO MẬT: Lấy và validate URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const parentId = urlParams.get('parent_id');
        
        if (parentId) {
          try {
            // ✅ BẢO MẬT: Validate parent folder ID
            const validParentId = this.validateFolderId(parentId);
            this.form.parent_folder_id = validParentId;
            
            // ✅ BẢO MẬT: Lấy thông tin thư mục cha với error handling
            const response = await axios.get(`/api/folders/${validParentId}`);
            
            if (response.data.success) {
              this.parentFolderName = this.sanitizeOutput(response.data.data.folder.name);
            } else {
              console.warn('Không thể lấy thông tin thư mục cha:', response.data.message);
              this.parentFolderName = 'Thư mục không xác định';
            }
          } catch (validationError) {
            console.warn('Parent folder ID không hợp lệ:', validationError.message);
            this.form.parent_folder_id = null;
            this.parentFolderName = 'Danh sách hiện tại (Thư mục gốc)';
          }
        }
      } catch (error) {
        console.error('Error getting location info:', error);
        // ✅ BẢO MẬT: Fallback an toàn
        this.parentFolderName = 'Danh sách hiện tại (Thư mục gốc)';
      }
    },

    async submitForm() {
      // ✅ BẢO MẬT: Client-side validation trước khi gửi
      if (!this.isFormValid) {
        this.showError('Vui lòng kiểm tra lại thông tin đã nhập.');
        return;
      }

      this.submitting = true;
      this.errors = {};
      this.successMessage = '';
      this.errorMessage = '';

      try {
        // ✅ BẢO MẬT: Sanitize form data trước khi gửi
        const sanitizedForm = {
          name: this.sanitizeInput(this.form.name),
          status: this.form.status,
          parent_folder_id: this.form.parent_folder_id
        };

        const response = await axios.post('/api/folders', sanitizedForm);

        if (response.data.success) {
          // ✅ THAY THẾ: Hiển thị modal thông báo thành công
          this.showSuccess(response.data.message);
        } else {
          // ✅ THAY THẾ: Hiển thị modal thông báo lỗi
          this.showError(response.data.message || 'Lỗi khi tạo thư mục');
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
        } else if (error.message && error.message.includes('Network Error')) {
          this.showError('Lỗi kết nối mạng. Vui lòng kiểm tra kết nối và thử lại.');
        } else if (error.message) {
          this.showError('Đã có lỗi xảy ra khi tạo thư mục. Vui lòng thử lại.');
        } else {
          this.showError('Lỗi không xác định. Vui lòng thử lại.');
        }
      } finally {
        this.submitting = false;
      }
    },

    // ==================== UI METHODS ====================
    goBack() {
      // ✅ BẢO MẬT: Sanitize URL parameters
      const parentId = this.form.parent_folder_id;
      const safeParentId = parentId ? this.sanitizeUrlParam(parentId) : '';
      const backUrl = parentId 
        ? `/folders?parent_id=${safeParentId}`
        : '/folders';
      window.location.href = backUrl;
    },

    // ✅ BẢO MẬT: Reset form an toàn
    resetForm() {
      this.form = {
        name: '',
        status: '',
        parent_folder_id: this.form.parent_folder_id // Giữ parent_id
      };
      this.errors = {};
    }
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

/* Focus styles for accessibility */
button:focus-visible,
input:focus-visible,
select:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* Disabled state styles */
button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
</style>