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
            <p v-if="errors.status" class="mt-1 text-sm text-red-600">{{ errors.status[0] }}</p>
          </div>

          <!-- Thông báo vị trí -->
          <div class="mb-4 p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center">
              <i class="fas fa-info-circle text-blue-500 mr-2"></i>
              <span class="text-sm text-blue-700">
                Thư mục sẽ được tạo trong <strong>{{ locationText }}</strong>
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
              <i class="fas fa-folder-plus mr-2"></i>
              {{ submitting ? 'Đang tạo...' : 'Tạo Thư Mục' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Success Message -->
      <div v-if="successMessage" class="mt-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">
        <div class="flex items-center">
          <i class="fas fa-check-circle text-green-500 mr-3"></i>
          <span>{{ successMessage }}</span>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="mt-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
        <div class="flex items-center">
          <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
          <span>{{ errorMessage }}</span>
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
      parentFolderName: 'Danh sách hiện tại (Thư mục gốc)'
    }
  },
  computed: {
    locationText() {
      return this.parentFolderName;
    }
  },
  mounted() {
    this.getLocationInfo();
  },
  methods: {
    // ==================== API CALLS ====================
    async getLocationInfo() {
      try {
        // Lấy parent_id từ URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const parentId = urlParams.get('parent_id');
        
        if (parentId) {
          this.form.parent_folder_id = parentId;
          
          // Lấy thông tin thư mục cha để hiển thị
          const response = await axios.get(`/api/folders/${parentId}`);
          if (response.data.success) {
            this.parentFolderName = response.data.data.folder.name;
          }
        }
      } catch (error) {
        console.error('Error getting location info:', error);
        // Không hiển thị lỗi vì đây chỉ là thông tin phụ
      }
    },

    async submitForm() {
      this.submitting = true;
      this.errors = {};
      this.successMessage = '';
      this.errorMessage = '';

      try {
        const response = await axios.post('/api/folders', this.form);

        if (response.data.success) {
          this.successMessage = response.data.message;
          
          // Redirect sau 1.5 giây
          setTimeout(() => {
            const parentId = response.data.data.parent_folder_id;
            const redirectUrl = parentId 
              ? `/folders?parent_id=${parentId}`
              : '/folders';
            window.location.href = redirectUrl;
          }, 1500);
        } else {
          this.errorMessage = response.data.message || 'Lỗi khi tạo thư mục';
        }
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors || {};
          this.errorMessage = 'Vui lòng kiểm tra lại thông tin đã nhập.';
        } else if (error.response && error.response.data.message) {
          this.errorMessage = error.response.data.message;
        } else {
          this.errorMessage = 'Đã có lỗi xảy ra khi tạo thư mục. Vui lòng thử lại.';
        }
      } finally {
        this.submitting = false;
      }
    },

    // ==================== UI METHODS ====================
    goBack() {
      const parentId = this.form.parent_folder_id;
      const backUrl = parentId 
        ? `/folders?parent_id=${parentId}`
        : '/folders';
      window.location.href = backUrl;
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
</style>