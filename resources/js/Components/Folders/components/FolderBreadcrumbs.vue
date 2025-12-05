<template>
  <nav v-if="breadcrumbs.length > 0 || !isSearchMode" class="flex items-center justify-between mb-6">
    <ol class="flex items-center space-x-2 text-sm">
      <!-- Root chỉ hiển thị khi KHÔNG ở chế độ tìm kiếm -->
      <li v-if="!isSearchMode">
        <button @click="$emit('go-to-root')" class="text-blue-500 hover:text-blue-700 flex items-center">
          <i class="fas fa-home mr-1"></i>Root
        </button>
      </li>
      
      <!-- Breadcrumbs bình thường -->
      <template v-if="!isSearchMode">
        <li v-for="(crumb, idx) in breadcrumbs" :key="crumb.folder_id" class="flex items-center">
          <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
          <button v-if="idx < breadcrumbs.length - 1" 
                  @click="$emit('go-to-folder', crumb.folder_id)" 
                  class="text-blue-500 hover:text-blue-700">
            {{ crumb.name }}
          </button>
          <span v-else class="text-gray-600 font-medium">{{ crumb.name }}</span>
        </li>
      </template>
    </ol>

    <!-- Nút quay lại chỉ hiển thị khi có currentFolder và không ở chế độ tìm kiếm -->
    <button v-if="currentFolder && !isSearchMode" @click="$emit('go-to-parent')" 
            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm flex items-center">
      <i class="fas fa-arrow-left mr-2"></i>Quay lại
    </button>
  </nav>
</template>

<script setup>
defineProps({
  breadcrumbs: {
    type: Array,
    default: () => []
  },
  currentFolder: {
    type: Object,
    default: null
  },
  isSearchMode: {
    type: Boolean,
    default: false
  }
});

defineEmits(['go-to-root', 'go-to-folder', 'go-to-parent']);
</script>