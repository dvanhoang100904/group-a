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

          <!-- Chia sẻ thư mục -->
          <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
              <label class="block text-sm font-medium text-gray-700">
                Quản lý chia sẻ
              </label>
              <button type="button" 
                      @click="toggleShareSection"
                      class="text-sm text-blue-500 hover:text-blue-700 flex items-center">
                <i class="fas" :class="showShareSection ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                <span class="ml-1">{{ showShareSection ? 'Ẩn' : 'Hiện' }}</span>
              </button>
            </div>
            
            <div v-if="showShareSection" class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <!-- Thông tin chia sẻ hiện tại -->
              <div v-if="sharedUsers.length > 0" class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Đang chia sẻ với:</h4>
                <div class="space-y-2">
                  <div v-for="user in sharedUsers" 
                       :key="user.user_id"
                       class="flex items-center justify-between bg-white rounded-lg p-3 border">
                    <div class="flex items-center">
                      <i class="fas fa-user text-gray-400 mr-2"></i>
                      <div>
                        <span class="text-sm font-medium">{{ sanitizeOutput(user.name) }}</span>
                        <span class="text-xs text-gray-500 ml-2">({{ sanitizeOutput(user.email) }})</span>
                        <span class="text-xs text-gray-500 ml-2">- {{ user.permission === 'edit' ? 'Chỉnh sửa' : 'Chỉ xem' }}</span>
                      </div>
                    </div>
                    <button 
                      @click="unshareUser(user.user_id)"
                      type="button"
                      class="text-red-500 hover:text-red-700 text-sm"
                      :disabled="shareData.loading"
                    >
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Thêm người dùng mới -->
              <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Thêm người dùng (phân cách bằng dấu phẩy)
                </label>
                <input 
                  v-model="shareData.emailsInput"
                  type="text" 
                  placeholder="Nhập email1, email2, ..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                  @input="validateEmails"
                  :disabled="shareData.loading"
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
                      :disabled="shareData.loading"
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
                      :disabled="shareData.loading"
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

              <!-- Nút chia sẻ -->
              <div class="flex justify-end">
                <button 
                  type="button"
                  @click="shareFolder"
                  :disabled="shareData.loading || shareData.validEmails.length === 0"
                  class="px-4 py-2 text-sm font-medium text-white bg-green-500 hover:bg-green-600 rounded-lg flex items-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <i class="fas fa-share-alt mr-2"></i>
                  {{ shareData.loading ? 'Đang chia sẻ...' : 'Chia sẻ' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Thông tin thêm -->
          <div class="mb-6 p-3 bg-blue-50 rounded-lg">
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
  name: 'FolderEdit',
  data() {
    return {
      // Data từ API
      folder: {},
      parentFolders: [],
      descendantIds: [],
      breadcrumbs: [],
      sharedUsers: [],
      
      // Form data
      form: {
        name: '',
        parent_folder_id: ''
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
      loading: true,
      submitting: false,
      showShareSection: false,
      errors: {},
      successMessage: '',
      errorMessage: '',
      shareSuccessMessage: '',
      loadError: '',
      
      // Modal states
      showSuccessModal: false,
      showErrorModal: false,
      showShareSuccessModal: false
    }
  },

  props: {
    folderId: {
      type: [String, Number],
      default: null
    }
  },

  computed: {
    // Client-side form validation
    isFormValid() {
      return this.form.name.trim().length > 0 && 
             this.form.name.length <= 255;
    }
  },

  methods: {
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
      return div.innerHTML.replace(/[<>]/g, '').substring(0, 255);
    },

    sanitizeFormField(fieldName) {
      if (this.form[fieldName]) {
        this.form[fieldName] = this.sanitizeInput(this.form[fieldName]);
      }
    },

    validateFolderId(folderId) {
      if (!folderId || !Number.isInteger(Number(folderId)) || folderId <= 0) {
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
      if (this.showShareSection) {
        this.loadSharedUsers();
      }
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
      this.shareData.emailsInput = this.shareData.validEmails.join(', ');
    },

async loadSharedUsers() {
  try {
    const folderId = this.validateFolderId(this.folder.folder_id);
    // ✅ Sửa: Bỏ /api/
    const response = await axios.get(`/folders/${folderId}/shared-users`);
    
    if (response.data.success) {
      this.sharedUsers = response.data.data || [];
    }
  } catch (error) {
    console.error('Lỗi khi tải danh sách chia sẻ:', error);
  }
},


   async shareFolder() {
  if (this.shareData.validEmails.length === 0) return;

  this.shareData.loading = true;
  try {
    const folderId = this.validateFolderId(this.folder.folder_id);
    // ✅ Sửa: Bỏ /api/
    const response = await axios.post(`/folders/${folderId}/share`, {
      emails: this.shareData.validEmails,
      permission: this.shareData.permission
    });

    if (response.data.success) {
      this.shareSuccessMessage = `Đã chia sẻ thành công với ${this.shareData.validEmails.length} người dùng`;
      this.showShareSuccessModal = true;
      
      // Reset form share
      this.shareData.emailsInput = '';
      this.shareData.validEmails = [];
      this.shareData.permission = 'view';
      
      // Reload danh sách chia sẻ
      this.loadSharedUsers();
    } else {
      this.showError(response.data.message || 'Lỗi khi chia sẻ folder');
    }
  } catch (error) {
    const message = error.response?.data?.message || 'Lỗi khi chia sẻ folder';
    this.showError(message);
  } finally {
    this.shareData.loading = false;
  }
},

   async unshareUser(userId) {
  if (!confirm('Bạn có chắc muốn hủy chia sẻ với người dùng này?')) {
    return;
  }

  try {
    const folderId = this.validateFolderId(this.folder.folder_id);
    // ✅ Sửa: Bỏ /api/
    const response = await axios.post(`/folders/${folderId}/unshare`, {
      user_ids: [userId]
    });

    if (response.data.success) {
      this.loadSharedUsers();
      this.showSuccess('Hủy chia sẻ thành công');
    }
  } catch (error) {
    this.showError('Lỗi khi hủy chia sẻ');
  }
},

    // Modal methods
    continueAfterSuccess() {
      this.showSuccessModal = false;
      this.successMessage = '';
      this.redirectAfterUpdate();
    },

    continueAfterShareSuccess() {
      this.showShareSuccessModal = false;
      this.shareSuccessMessage = '';
    },

    redirectAfterUpdate() {
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

    // API CALLS
    async loadFolderData() {
      this.loading = true;
      this.loadError = '';
      
      try {
        const pathSegments = window.location.pathname.split('/').filter(segment => segment);        
        let folderId = null;
        
        for (let i = 0; i < pathSegments.length; i++) {
          if (pathSegments[i] === 'folders' && i + 1 < pathSegments.length) {
            const potentialId = pathSegments[i + 1];
            if (!isNaN(potentialId) && potentialId !== 'create' && potentialId !== 'edit') {
              folderId = potentialId;
              break;
            }
          }
        }
        
        if (!folderId && pathSegments.length >= 2) {
          const potentialId = pathSegments[1];
          if (!isNaN(potentialId) && potentialId !== 'create') {
            folderId = potentialId;
          }
        }
        
        if (!folderId) {
          throw new Error('Không tìm thấy ID thư mục trong URL');
        }

        const validFolderId = this.validateFolderId(folderId);
        const apiUrl = `/api/folders/${validFolderId}`;
        
        const response = await axios.get(apiUrl);
        
        if (response.data.success) {
          const data = response.data.data;
          
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
          
          // Fill form với data hiện tại
          this.form = {
            name: this.sanitizeOutput(this.folder.name || ''),
            parent_folder_id: this.folder.parent_folder_id || ''
          };
          
          // Load danh sách chia sẻ
          this.loadSharedUsers();
          
        } else {
          this.loadError = this.sanitizeOutput(response.data.message || 'Lỗi khi tải dữ liệu thư mục');
        }
      } catch (error) {
        console.error('Load folder error:', error);
        
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
        const folderId = this.validateFolderId(this.folder.folder_id);

        const sanitizedForm = {
          name: this.sanitizeInput(this.form.name),
          parent_folder_id: this.form.parent_folder_id || null
        };

        const response = await axios.put(`/api/folders/${folderId}`, sanitizedForm);

        if (response.data.success) {
          this.showSuccess(response.data.message);
          
          // Cập nhật data local sau khi thành công
          this.folder = { 
            ...this.folder, 
            ...response.data.data,
            name: this.sanitizeOutput(response.data.data?.name || this.folder.name)
          };
        } else {
          this.showError(response.data.message || 'Lỗi khi cập nhật thư mục');
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
        } else if (error.message) {
          this.showError('Đã có lỗi xảy ra khi cập nhật thư mục. Vui lòng thử lại.');
        } else {
          this.showError('Lỗi không xác định. Vui lòng thử lại.');
        }
      } finally {
        this.submitting = false;
      }
    },

    // UI METHODS
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
    }
  },
  
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