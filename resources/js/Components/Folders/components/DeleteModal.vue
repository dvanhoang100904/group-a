<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="p-6">
        <div class="flex items-center mb-4">
          <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mr-3"></i>
          <h3 class="text-lg font-medium text-gray-900">Xác nhận xóa</h3>
        </div>
        <p class="text-sm text-gray-600 mb-6">
          Bạn có chắc muốn xóa <strong>{{ item?.item_type === 'folder' ? 'thư mục' : 'tài liệu' }}</strong> 
          "<strong>{{ item?.name }}</strong>"? Hành động này không thể hoàn tác.
        </p>
        <div class="flex justify-end space-x-3">
          <button @click="$emit('cancel')" 
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
            Hủy
          </button>
          <button @click="$emit('confirm')" 
                  class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg flex items-center transition-colors">
            <i class="fas fa-trash mr-2"></i>Xóa
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  item: {
    type: Object,
    default: null
  }
});

defineEmits(['cancel', 'confirm']);
</script>