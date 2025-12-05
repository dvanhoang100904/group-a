<template>
  <div class="flex items-center justify-between bg-white px-4 py-3 rounded-lg shadow border-t">
    <div class="flex items-center text-sm text-gray-700">
      <span v-if="pagination.total > 0">
        Hiển thị <strong>{{ pagination.from }}-{{ pagination.to }}</strong> của <strong>{{ pagination.total }}</strong> kết quả
      </span>
      <span v-else>Không có kết quả</span>
    </div>

    <div v-if="pagination.last_page > 1" class="flex items-center space-x-2">
      <button 
        v-if="pagination.current_page > 1" 
        @click="$emit('changePage', pagination.current_page - 1)"
        class="px-3 py-1 bg-white border rounded-lg hover:bg-gray-50 text-gray-700 transition-colors"
      >
        <i class="fas fa-chevron-left mr-1"></i>Trước
      </button>
      <span 
        v-else 
        class="px-3 py-1 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed"
      >
        <i class="fas fa-chevron-left mr-1"></i>Trước
      </span>

      <button 
        v-for="page in computedPages" 
        :key="page"
        @click="handlePageClick(page)"
        :disabled="page === '...'"
        :class="[
          'px-3 py-1 rounded-lg font-medium transition-colors min-w-[40px]',
          page === pagination.current_page 
            ? 'bg-blue-500 text-white' 
            : page === '...' 
              ? 'cursor-default text-gray-500 bg-transparent'
              : 'bg-white border hover:bg-gray-50 text-gray-700'
        ]"
      >
        {{ page }}
      </button>

      <button 
        v-if="pagination.current_page < pagination.last_page" 
        @click="$emit('changePage', pagination.current_page + 1)"
        class="px-3 py-1 bg-white border rounded-lg hover:bg-gray-50 text-gray-700 transition-colors"
      >
        Sau<i class="fas fa-chevron-right ml-1"></i>
      </button>
      <span 
        v-else 
        class="px-3 py-1 text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed"
      >
        Sau<i class="fas fa-chevron-right ml-1"></i>
      </span>
    </div>
    <div v-else class="text-sm text-gray-500">
      Tất cả kết quả đang được hiển thị
    </div>

    <div class="flex items-center space-x-2">
      <span class="text-sm text-gray-700">Hiển thị:</span>
      <select 
        v-model="localPerPage" 
        @change="handlePerPageChange"
        class="border rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none"
      >
        <option value="10">10</option>
        <option value="20">20</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  pagination: {
    type: Object,
    required: true,
    default: () => ({ 
      current_page: 1, 
      last_page: 1, 
      from: 0, 
      to: 0, 
      total: 0 
    })
  },
  perPage: {
    type: Number,
    default: 20
  },
  pages: {
    type: Array,
    default: () => []
  }
});

// Sửa tên emit theo camelCase
const emit = defineEmits(['changePage', 'changePerPage']);

const localPerPage = ref(props.perPage);

const computedPages = computed(() => {
  if (props.pages && props.pages.length > 0) {
    return props.pages;
  }
  
  const pages = [];
  const current = props.pagination.current_page;
  const last = props.pagination.last_page;
  const delta = 2;
  
  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    pages.push(i);
  }
  
  if (current - delta > 2) pages.unshift('...');
  if (current + delta < last - 1) pages.push('...');
  
  pages.unshift(1);
  if (last > 1) pages.push(last);
  
  return pages.filter((v, i, a) => a.indexOf(v) === i);
});

const handlePageClick = (page) => {
  if (page === '...' || page === props.pagination.current_page) return;
  emit('changePage', page);
};

const handlePerPageChange = (event) => {
  // Sửa: truyền event.target.value thay vì localPerPage.value
  emit('changePerPage', parseInt(event.target.value));
};

watch(() => props.perPage, (newValue) => {
  localPerPage.value = newValue;
});
</script>