<template>
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
      <!-- Header with Breadcrumbs (FIX: Add display breadcrumbs) -->
      <div class="flex items-center mb-6">      
        <h1 class="text-2xl font-bold text-gray-800">Chỉnh sửa Thư Mục</h1>
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
      
      <br>

      <!-- Form -->
      <div class="bg-white rounded-lg shadow p-6">
        <form @submit.prevent="submitForm">
          <input type="hidden" name="_token" :value="csrfToken">
          <!-- FIX: Remove _method if using AJAX; keep for fallback sync -->
          <input type="hidden" name="_method" value="PUT">

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
          <div class="mb-4">
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

          <!-- Thư mục cha -->
          <div class="mb-6">
            <label for="parent_folder_id" class="block text-sm font-medium text-gray-700 mb-2">
              Thư mục cha (tùy chọn)
            </label>
            <select id="parent_folder_id" 
                    v-model="form.parent_folder_id"
                    name="parent_folder_id" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-500': errors.parent_folder_id }">
              <option value="">-- Thư mục gốc --</option>
              <option v-for="parent in parentFolders" 
                      :key="parent.folder_id" 
                      :value="parent.folder_id"
                      :disabled="isCurrentOrDescendant(parent.folder_id)">
                {{ parent.indented_name }}  <!-- FIX: Use indented_name for hierarchy -->
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
              <i class="fas fa-save mr-2"></i>
              {{ loading ? 'Đang cập nhật...' : 'Cập nhật' }}
            </button>
          </div>
        </form>
      </div>
     
    </div>
  </div>
</template>

<script>
export default {
  name: 'FolderEdit',
  props: {
    folder: {
      type: Object,
      required: true
    },
    parentFolders: {  // FIX: Assume now includes indented_name and all possible
      type: Array,
      default: () => []
    },
    descendantIds: {  // FIX: New prop from service to check descendants
      type: Array,
      default: () => []
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
        name: this.initialOld.name || this.folder.name,
        status: this.initialOld.status || this.folder.status,
        parent_folder_id: this.initialOld.parent_folder_id || this.folder.parent_folder_id || ''
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
    }
  },
  methods: {
    route(name, params = null) {
      const baseUrl = window.location.origin;
      const routes = {
        'folders.index': '/folders',
        'folders.update': (id) => `/folders/${id}`
      };

      if (typeof routes[name] === 'function') {
        return baseUrl + routes[name](params);
      }
      return baseUrl + routes[name];
    },
    isCurrentOrDescendant(folderId) {
      // FIX: Check if folderId is current or in descendantIds to prevent cycle
      return folderId === this.folder.folder_id || this.descendantIds.includes(folderId);
    },
    async submitForm() {
      this.loading = true;
      this.errors = {};
      this.successMessage = '';
      this.errorMessage = '';

      try {
        const response = await axios.put(this.route('folders.update', this.folder.folder_id), this.form, {
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken
          }
        });

        this.successMessage = response.data.message || 'Thư mục đã được cập nhật thành công!';
        // Redirect sau 1 giây
        setTimeout(() => {
          window.location.href = this.route('folders.index') + (response.data.folder.parent_folder_id ? '?parent_id=' + response.data.folder.parent_folder_id : '');
        }, 1000);
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors || {};
          this.errorMessage = 'Vui lòng kiểm tra lại thông tin đã nhập.';
        } else if (error.response && error.response.data.message) {
          this.errorMessage = error.response.data.message;
        } else {
          this.errorMessage = 'Đã có lỗi xảy ra khi cập nhật thư mục. Vui lòng thử lại.';
        }
      } finally {
        this.loading = false;
      }
    },
    // Fallback method nếu không dùng axios
    submitFormFallback() {
      this.loading = true;
      
      // Tìm form và submit theo cách truyền thống
      const form = this.$el.querySelector('form');
      if (form) {
        form.action = this.route('folders.update', this.folder.folder_id);
        form.method = 'POST';  // Laravel fake PUT with _method
        form.submit();
      } else {
        this.loading = false;
      }
    }
  },
  mounted() {
    console.log('FolderEdit component mounted');
    console.log('Folder data:', this.folder);
    console.log('Parent folders:', this.parentFolders);
    console.log('Descendant IDs:', this.descendantIds);
  }
}
</script>