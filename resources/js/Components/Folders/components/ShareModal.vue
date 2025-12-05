<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999] p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center">
            <i class="fas fa-share-alt text-blue-500 text-xl mr-3"></i>
            <h3 class="text-lg font-medium text-gray-900">Chia sẻ thư mục</h3>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Folder Info -->
        <div class="bg-gray-50 rounded-lg p-3 mb-4">
          <div class="flex items-center">
            <i class="fas fa-folder text-yellow-500 mr-2"></i>
            <span class="font-medium text-gray-800">{{ folderName }}</span>
          </div>
        </div>

        <!-- Share Form -->
        <form @submit.prevent="$emit('share', shareData)">
          <!-- Email Input -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Email người dùng (phân cách bằng dấu phẩy)
            </label>
            <input 
              v-model="shareData.emailsInput"
              type="text" 
              placeholder="Nhập email1, email2, ..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
              :disabled="shareData.loading"
            >
            <p class="text-xs text-gray-500 mt-1">
              Nhập email của những người bạn muốn chia sẻ
            </p>
          </div>

          <!-- Permission Selection -->
          <div class="mb-6">
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

          <!-- Current Shares -->
          <div v-if="sharedUsers.length > 0" class="mb-4">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Đang chia sẻ với:</h4>
            <div class="space-y-2">
              <div 
                v-for="user in sharedUsers" 
                :key="user.user_id"
                class="flex items-center justify-between bg-gray-50 rounded-lg p-2"
              >
                <div class="flex items-center">
                  <i class="fas fa-user text-gray-400 mr-2"></i>
                  <span class="text-sm font-medium">{{ user.name }}</span>
                  <span class="text-xs text-gray-500 ml-2">({{ user.email }})</span>
                  <span class="text-xs text-gray-500 ml-2">- {{ user.permission === 'edit' ? 'Chỉnh sửa' : 'Chỉ xem' }}</span>
                </div>
                <button 
                  @click="$emit('unshare', user.user_id)"
                  type="button"
                  class="text-red-500 hover:text-red-700 text-sm"
                  :disabled="shareData.loading"
                >
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-3">
            <button 
              type="button"
              @click="$emit('close')"
              :disabled="shareData.loading"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors disabled:opacity-50"
            >
              Hủy
            </button>
            <button 
              type="submit"
              :disabled="shareData.loading"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg flex items-center transition-colors disabled:opacity-50"
            >
              <i class="fas fa-share-alt mr-2"></i>
              {{ shareData.loading ? 'Đang chia sẻ...' : 'Chia sẻ' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  folderName: {
    type: String,
    default: ''
  },
  shareData: {
    type: Object,
    default: () => ({
      emailsInput: '',
      permission: 'view',
      loading: false
    })
  },
  sharedUsers: {
    type: Array,
    default: () => []
  }
});

defineEmits(['close', 'share', 'unshare']);
</script>