<template>
  <div class="flex flex-col lg:flex-row gap-4 mb-6">
    <div class="flex flex-col sm:flex-row gap-2 bg-gray-100 rounded-lg shadow-sm border p-3">
      <div class="relative flex-1">
        <input v-model="searchParams.name" 
               type="text" 
               placeholder="Tìm theo tên" 
               class="pl-10 pr-4 py-2 border rounded-lg w-full focus:ring-2 focus:ring-blue-500 focus:outline-none"
               @keyup.enter="$emit('search')"
               maxlength="255">
        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
      </div>
      
      <div class="relative flex-1">
        <input v-model="searchParams.date" 
               type="date" 
               class="pl-10 pr-4 py-2 border rounded-lg w-full focus:ring-2 focus:ring-blue-500 focus:outline-none"
               @change="validateDate">
        <i class="fas fa-calendar absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
      </div>
      
      <div class="relative flex-1 min-w-0">
        <select v-model="searchParams.file_type" 
                class="pl-10 pr-8 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none w-full appearance-none bg-white truncate"
                :disabled="loadingDocumentTypes">
          <option value="">Tất cả loại file</option>
          <option value="folder">Thư mục</option>
          <option v-for="docType in documentTypes" 
                  :key="docType.type_id" 
                  :value="docType.name">
            {{ docType.name }}
          </option>
        </select>
        <i class="fas fa-file-alt absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
        <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
      </div>
      
      <div class="flex gap-2">
        <button @click="$emit('search')" 
                :disabled="loading"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-colors disabled:opacity-50 flex-shrink-0">
          <i class="fas fa-search mr-2"></i>Tìm
        </button>
        
        <button v-if="hasActiveFilters" @click="$emit('reset')" 
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center justify-center transition-colors flex-shrink-0">
          <i class="fas fa-times mr-2"></i>Reset
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue';

const props = defineProps({
  searchParams: {
    type: Object,
    required: true,
    default: () => ({ name: '', date: '', file_type: '' })
  },
  documentTypes: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  loadingDocumentTypes: {
    type: Boolean,
    default: false
  }
});

defineEmits(['search', 'reset']);

const hasActiveFilters = computed(() => {
  return props.searchParams.name.trim() !== '' || 
         props.searchParams.date !== '' || 
         props.searchParams.file_type !== '';
});

const validateDate = () => {
  if (props.searchParams.date) {
    const date = new Date(props.searchParams.date);
    if (isNaN(date.getTime())) {
      // Xử lý lỗi nếu cần
    }
  }
};
</script>