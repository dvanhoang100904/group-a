<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="p-6">
        <div class="flex items-center mb-4">
          <i :class="iconClass" class="text-2xl mr-3"></i>
          <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
        </div>
        <p class="text-sm text-gray-600 mb-6">
          {{ message }}
        </p>
        <div class="flex justify-end space-x-3">
          <button v-if="type === 'success'" 
                  @click="$emit('continue')" 
                  class="px-4 py-2 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg flex items-center transition-colors">
            <i class="fas fa-sync-alt mr-2"></i>Tiếp tục
          </button>
          <button v-else 
                  @click="$emit('close')" 
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
            Đóng
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: {
    type: String,
    default: 'success',
    validator: (value) => ['success', 'error'].includes(value)
  },
  message: {
    type: String,
    default: ''
  }
});

defineEmits(['continue', 'close']);

const title = computed(() => {
  return props.type === 'success' ? 'Thành công' : 'Xin thông báo';
});

const iconClass = computed(() => {
  return props.type === 'success' 
    ? 'fas fa-check-circle text-green-500' 
    : 'fas fa-exclamation-circle text-red-500';
});
</script>