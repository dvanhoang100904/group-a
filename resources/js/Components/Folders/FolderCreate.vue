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
          <div class="mb-6">
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

          <!-- Chia sẻ ngay sau khi tạo -->
          <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium text-gray-700">
                Chia sẻ thư mục (tùy chọn)
              </label>
              <button type="button" 
                      @click="toggleShareSection"
                      class="text-sm text-blue-500 hover:text-blue-700 flex items-center">
                <i class="fas" :class="showShareSection ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                <span class="ml-1">{{ showShareSection ? 'Ẩn' : 'Hiện' }}</span>
              </button>
            </div>
            
            <div v-if="showShareSection" class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <!-- Email Input -->
              <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Email người dùng (phân cách bằng dấu phẩy)
                </label>
                <input 
                  v-model="shareData.emailsInput"
                  type="text" 
                  placeholder="Nhập email1, email2, ..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  @input="validateEmails"
                >
                <p class="text-xs text-gray-500 mt-1">
                  Nhập email của những người bạn muốn chia sẻ
                </p>
                <p v-if="shareData.emailError" class="text-xs text-red-500 mt-1">
                  {{ shareData.emailError }}
                </p>
              </div>

              <!-- Permission Selection -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Quyền truy cập
                </label>
                <div class="space-y-2">
                  <label class="flex items-center">
                    <input 
                      v-model="shareData.permission" 
                      type="radio" 
                      value="view" 
                      class="mr-3 text-blue-500 focus:ring-blue-500"
                    >
                    <div>
                      <div class="font-medium text-gray-900">Chỉ xem</div>
                      <div class="text-sm text-gray-500">Có thể xem và tải xuống file</div>
                    </div>
                  </label>
                  <label class="flex items-center">
                    <input 
                      v-model="shareData.permission" 
                      type="radio" 
                      value="edit" 
                      class="mr-3 text-blue-500 focus:ring-blue-500"
                    >
                    <div>
                      <div class="font-medium text-gray-900">Chỉnh sửa</div>
                      <div class="text-sm text-gray-500">Có thể thêm, sửa, xóa file và folder con</div>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Danh sách email đã nhập -->
              <div v-if="shareData.validEmails.length > 0" class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Danh sách sẽ chia sẻ:
                </label>
                <div class="flex flex-wrap gap-2">
                  <span v-for="(email, index) in shareData.validEmails" 
                        :key="index"
                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ sanitizeOutput(email) }}
                    <button type="button" 
                            @click="removeEmail(index)"
                            class="ml-1 text-blue-600 hover:text-blue-800">
                      <i class="fas fa-times text-xs"></i>
                    </button>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Thông báo vị trí -->
          <div class="mb-6 p-3 bg-blue-50 rounded-lg">
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

    <!-- Share Success Modal -->
    <div v-if="showShareSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center mb-4">
            <i class="fas fa-share-alt text-green-500 text-2xl mr-3"></i>
            <h3 class="text-lg font-medium text-gray-900">Chia sẻ thành công</h3>
          </div>
          <p class="text-sm text-gray-600 mb-6">
            {{ sanitizeOutput(shareSuccessMessage) }}
          </p>
          <div class="flex justify-end space-x-3">
            <button @click="continueAfterShareSuccess" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg flex items-center transition-colors">
              <i class="fas fa-check mr-2"></i>Hoàn tất
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
        parent_folder_id: null
      },
      
      // Share data
      shareData: {
        emailsInput: '',
        permission: 'view',
        validEmails: [],
        emailError: '',
        loading: false
      },
      
      // UI State
      loading: false,
      submitting: false,
      showShareSection: false,
      errors: {},
      successMessage: '',
      errorMessage: '',
      shareSuccessMessage: '',
      
      // Location info
      parentFolderName: 'Danh sách hiện tại (Thư mục gốc)',
      
      // Modal states
      showSuccessModal: false,
      showErrorModal: false,
      showShareSuccessModal: false,

      // New folder ID after creation
      createdFolderId: null
    }
  },
  computed: {
    locationText() {
      return this.parentFolderName;
    },
    
    // Client-side form validation
    isFormValid() {
      return this.form.name.trim().length > 0 && 
             this.form.name.length <= 255;
    },

    // Check if there are emails to share
    hasEmailsToShare() {
      return this.shareData.validEmails.length > 0;
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
    async getLocationInfo() {
    try {
        if (!this.parentFolderId || this.parentFolderId === 'null') {
            this.parentFolderName = 'Thư mục gốc';
            this.breadcrumbs = [];
            return;
        }
        
        // Sửa URL API
        const response = await axios.get(`/api/folders/${this.parentFolderId}`);
        
        if (response.data.success) {
            const data = response.data.data;
            this.parentFolderName = this.sanitizeOutput(data.folder.name);
            this.breadcrumbs = data.breadcrumbs || [];
        } else {
            throw new Error(response.data.message || 'Không thể tải thông tin thư mục');
        }
    } catch (error) {
        console.error('Parent folder ID không hợp lệ:', error);
        this.parentFolderName = 'Thư mục không tồn tại';
        this.breadcrumbs = [];
        
        // Hiển thị thông báo lỗi
        if (error.response?.status === 404) {
            this.showError('Thư mục cha không tồn tại hoặc đã bị xóa');
        } else {
            this.showError('Không thể tải thông tin vị trí: ' + error.message);
        }
    }
},
    async getLocationInfo() {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const parentId = urlParams.get('parent_id');
        const parentName = urlParams.get('parent_name');
        
        console.log('📄 Create page - URL params:', {
            parentId: parentId,
            parentName: parentName,
            fullUrl: window.location.href
        });
        
        if (parentId) {
            try {
                const validParentId = this.validateFolderId(parentId);
                this.form.parent_folder_id = validParentId;
                
                console.log('📄 Fetching parent folder info for:', validParentId);
                
                // ✅ SỬA: Gọi API mới getFolderName
                try {
                    const response = await axios.get(`/api/folders/${validParentId}/name`);
                    
                    if (response.data.success) {
                        this.parentFolderName = this.sanitizeOutput(response.data.data.name);
                        console.log('📄 Parent folder name from API:', this.parentFolderName);
                    } else {
                        console.warn('Không thể lấy thông tin thư mục cha:', response.data.message);
                        // Fallback: sử dụng parent_name từ URL hoặc hiển thị ID
                        this.parentFolderName = parentName ? decodeURIComponent(parentName) : `Thư mục #${validParentId}`;
                    }
                } catch (apiError) {
                    console.warn('API Error, using fallback:', apiError.message);
                    // Fallback: sử dụng parent_name từ URL hoặc hiển thị ID
                    this.parentFolderName = parentName ? decodeURIComponent(parentName) : `Thư mục #${validParentId}`;
                }
                
            } catch (validationError) {
                console.warn('Parent folder ID không hợp lệ:', validationError.message);
                this.form.parent_folder_id = null;
                this.parentFolderName = 'Danh sách hiện tại (Thư mục gốc)';
            }
        } else {
            console.log('📄 No parent_id - Creating root folder');
            this.parentFolderName = 'Danh sách hiện tại (Thư mục gốc)';
        }
    } catch (error) {
        console.error('Error getting location info:', error);
        this.parentFolderName = 'Danh sách hiện tại (Thư mục gốc)';
    }
},
    // Sanitize methods
    sanitizeOutput(value) {
      if (value === null || value === undefined) return '';
      const div = document.createElement('div');
      div.textContent = value.toString();
      return div.innerHTML;
    },

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

    sanitizeFormField(fieldName) {
      if (this.form[fieldName]) {
        this.form[fieldName] = this.sanitizeInput(this.form[fieldName]);
      }
    },

    validateFolderId(folderId) {
      if (!folderId) return null;
      if (!Number.isInteger(Number(folderId)) || folderId <= 0) {
        throw new Error('ID thư mục không hợp lệ');
      }
      return Number(folderId);
    },

    sanitizeUrlParam(param) {
      if (!param) return '';
      return encodeURIComponent(param.toString());
    },

    // Share methods
    toggleShareSection() {
      this.showShareSection = !this.showShareSection;
    },

    validateEmails() {
      this.shareData.emailError = '';
      
      if (!this.shareData.emailsInput.trim()) {
        this.shareData.validEmails = [];
        return;
      }

      const emails = this.shareData.emailsInput.split(',')
        .map(email => email.trim())
        .filter(email => email);

      // Validate email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      const invalidEmails = emails.filter(email => !emailRegex.test(email));

      if (invalidEmails.length > 0) {
        this.shareData.emailError = `Email không hợp lệ: ${invalidEmails.join(', ')}`;
        this.shareData.validEmails = [];
      } else {
        this.shareData.validEmails = emails;
      }
    },

    removeEmail(index) {
      this.shareData.validEmails.splice(index, 1);
      // Update the input field
      this.shareData.emailsInput = this.shareData.validEmails.join(', ');
    },

    async shareFolder(folderId) {
      if (!this.hasEmailsToShare) return;

      this.shareData.loading = true;
      try {
        const response = await axios.post(`/api/folders/${folderId}/share`, {
          emails: this.shareData.validEmails,
          permission: this.shareData.permission
        });

        if (response.data.success) {
          this.shareSuccessMessage = `Đã tạo thư mục và chia sẻ thành công với ${this.shareData.validEmails.length} người dùng`;
          this.showShareSuccessModal = true;
        } else {
          console.warn('Chia sẻ thất bại:', response.data.message);
        }
      } catch (error) {
        console.error('Lỗi khi chia sẻ folder:', error);
        // Không hiển thị lỗi cho user vì folder đã được tạo thành công
      } finally {
        this.shareData.loading = false;
      }
    },

    // Modal methods
    continueAfterSuccess() {
      this.showSuccessModal = false;
      this.successMessage = '';
      
      // Nếu có emails để chia sẻ, thực hiện chia sẻ
      if (this.hasEmailsToShare && this.createdFolderId) {
        this.shareFolder(this.createdFolderId);
      } else {
        // Chuyển hướng bình thường
        this.redirectAfterCreation();
      }
    },

    continueAfterShareSuccess() {
      this.showShareSuccessModal = false;
      this.shareSuccessMessage = '';
      this.redirectAfterCreation();
    },

    redirectAfterCreation() {
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

    // Handle Escape key
    handleKeydown(event) {
      if (event.key === 'Escape') {
        if (this.showSuccessModal) {
          this.continueAfterSuccess();
        }
        if (this.showErrorModal) {
          this.hideErrorModal();
        }
        if (this.showShareSuccessModal) {
          this.continueAfterShareSuccess();
        }
      }
    },

    // Prevent form submission on Enter in inputs
    preventEnterSubmit(event) {
      if (event.key === 'Enter' && event.target.tagName !== 'FORM') {
        event.preventDefault();
      }
    },

    // API CALLS
    async getLocationInfo() {
      try {
        const urlParams = new URLSearchParams(window.location.search);
        const parentId = urlParams.get('parent_id');
        
        if (parentId) {
          try {
            const validParentId = this.validateFolderId(parentId);
            this.form.parent_folder_id = validParentId;
            
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
        this.parentFolderName = 'Danh sách hiện tại (Thư mục gốc)';
      }
    },

    async submitForm() {
      if (!this.isFormValid) {
        this.showError('Vui lòng nhập tên thư mục.');
        return;
      }

      this.submitting = true;
      this.errors = {};
      this.successMessage = '';
      this.errorMessage = '';

      try {
        const sanitizedForm = {
          name: this.sanitizeInput(this.form.name),
          parent_folder_id: this.form.parent_folder_id
        };

        const response = await axios.post('/api/folders', sanitizedForm);

        if (response.data.success) {
          // Lưu folder ID để sử dụng cho chia sẻ
          this.createdFolderId = response.data.data.folder_id;
          this.showSuccess(response.data.message);
        } else {
          this.showError(response.data.message || 'Lỗi khi tạo thư mục');
        }
      } catch (error) {
        console.error('Submit Error:', error);
        
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors || {};
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

    // UI METHODS
    goBack() {
      const parentId = this.form.parent_folder_id;
      const safeParentId = parentId ? this.sanitizeUrlParam(parentId) : '';
      const backUrl = parentId 
        ? `/folders?parent_id=${safeParentId}`
        : '/folders';
      window.location.href = backUrl;
    },

    resetForm() {
      this.form = {
        name: '',
        parent_folder_id: this.form.parent_folder_id
      };
      this.shareData = {
        emailsInput: '',
        permission: 'view',
        validEmails: [],
        emailError: '',
        loading: false
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

/* Custom styles for email tags */
.bg-blue-100 {
  background-color: #dbeafe;
}
</style>