<template>
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
      <!-- Header -->
      <div class="flex items-center mb-6">
        <a :href="route('folders.index')" class="text-blue-500 hover:text-blue-700 mr-4">
          <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Tạo Thư Mục Mới</h1>
      </div>

      <!-- Form -->
      <div class="bg-white rounded-lg shadow p-6">
        <form method="post" :action="route('folders.store')" @submit.prevent="submitForm">
          <input type="hidden" name="_token" :value="csrfToken">
          <input type="hidden" name="parent_folder_id" :value="parentFolderId">

          <!-- Tên thư mục -->
          <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
              Tên thư mục *
            </label>
            <input type="text" 
                   id="name" 
                   v-model="form.name"
                   name="name"
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
                    name="status" 
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
            <a :href="route('folders.index')" 
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
              Hủy
            </a>
            <button type="submit" 
                    :disabled="loading"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
              <i class="fas fa-folder-plus mr-2"></i>
              {{ loading ? 'Đang tạo...' : 'Tạo Thư Mục' }}
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
export default {
  name: 'FolderCreate',
  props: {
    parentFolderId: {
      type: [String, Number],
      default: null
    },
    parentFolderName: {
      type: String,
      default: 'Danh sách hiện tại'
    },
    breadcrumbs: {
      type: Array,
      default: () => []
    },
    initialErrors: {
      type: Object,
      default: () => ({})
    },
    initialOld: {
      type: Object,
      default: () => ({})
    },
    success: {
      type: String,
      default: null
    },
    error: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      form: {
        name: this.initialOld.name || '',
        status: this.initialOld.status || '',
        parent_folder_id: this.parentFolderId
      },
      errors: this.initialErrors,
      loading: false,
      successMessage: this.success,
      errorMessage: this.error
    }
  },
  computed: {
    csrfToken() {
      return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    },
    locationText() {
      return this.parentFolderName || 'Danh sách hiện tại (Thư mục gốc)';
    }
  },
  methods: {
    route(name, params = null) {
      const baseUrl = window.location.origin;
      const routes = {
        'folders.index': '/folders',
        'folders.store': '/folders'
      };

      if (typeof routes[name] === 'function') {
        return baseUrl + routes[name](params);
      }
      return baseUrl + routes[name];
    },
    async submitForm() {
      this.loading = true;
      this.errors = {};
      this.successMessage = '';
      this.errorMessage = '';

      try {
        // Submit form truyền thống
        const form = this.$el.querySelector('form');
        if (form) {
          form.submit();
        }
      } catch (error) {
        this.errorMessage = 'Đã có lỗi xảy ra khi tạo thư mục. Vui lòng thử lại.';
        this.loading = false;
      }
    }
  },
  mounted() {
    console.log('FolderCreate component mounted');
  }
}
</script>