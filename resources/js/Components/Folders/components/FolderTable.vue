<template>
  <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
    <table class="min-w-full">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="item in items" 
            :key="getItemKey(item)" 
            class="hover:bg-gray-50 transition-colors cursor-pointer"
            @contextmenu.prevent="$emit('show-context-menu', $event, item)">
          
          <!-- Tên -->
          <td class="px-6 py-4">
            <div class="flex items-center" 
                 @click="$emit('open-item', item)">
              <i :class="getItemIcon(item)" class="text-lg mr-3 flex-shrink-0"></i>
              <div class="min-w-0">
                <div class="text-sm font-medium text-gray-900 truncate">
                  {{ item?.name || 'Unknown' }}
                </div>
                
                <div class="text-xs text-gray-500 mt-1">
                  <div v-if="isSearchMode && item?.folder_path" 
                       class="flex items-center truncate">
                    <i class="fas fa-folder mr-1 text-yellow-500"></i>
                    <span class="truncate">
                      {{ formatFolderPath(item.folder_path) }}
                    </span>
                  </div>
                  
                  <div v-else-if="!isSearchMode && item && item.item_type === 'folder'" 
                       class="flex items-center space-x-3">
                    <span v-if="item.child_folders_count > 0 || item.documents_count > 0" 
                          class="flex items-center">
                      <i class="fas fa-folder-open mr-1"></i>
                      {{ item.child_folders_count }} thư mục, {{ item.documents_count }} file
                    </span>
                  </div>
                  
                  <div v-else-if="!isSearchMode && item && item.item_type === 'document'">
                    <span class="flex items-center">
                      <i class="fas fa-file-alt mr-1"></i>
                      {{ item?.type_name || 'File' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </td>

          <!-- Loại -->
          <td class="px-6 py-4 whitespace-nowrap" @click="$emit('open-item', item)">
            <span class="text-sm text-gray-600">{{ item?.type_name || 'Unknown' }}</span>
          </td>

          <!-- Ngày tạo -->
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" 
              @click="$emit('open-item', item)">
            {{ item ? formatDateTime(item.created_at) : '' }}
          </td>
          
          <!-- Thao tác -->
          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
            <div class="relative inline-block text-left action-dropdown-container">
              <button @click.stop="$emit('toggle-menu', item, $event)"
                      :disabled="!item"
                      class="inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-ellipsis-v text-gray-500"></i>
              </button>
            </div>
          </td>
        </tr>

        <!-- Empty state -->
        <tr v-if="items.length === 0">
          <td :colspan="isSearchMode ? 6 : 5" class="px-6 py-12 text-center">
            <div class="flex flex-col items-center">
              <i class="fas fa-search text-gray-400 text-4xl mb-4" v-if="isSearchMode"></i>
              <i class="fas fa-folder-open text-gray-400 text-4xl mb-4" v-else></i>
              <h3 class="text-lg font-medium text-gray-900 mb-2">
                {{ isSearchMode ? 'Không tìm thấy kết quả' : 'Không có dữ liệu' }}
              </h3>
              <p class="text-gray-500 mb-4">
                {{ isSearchMode ? 'Hãy thử với từ khóa khác hoặc điều chỉnh bộ lọc' : 'Hãy tạo thư mục hoặc tải file lên' }}
              </p>
              <slot name="empty-state-actions"></slot>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
import { getItemIcon } from '../utils/folderUtils';
import { formatDateTime } from '../utils/dateUtils';

const props = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  isSearchMode: {
    type: Boolean,
    default: false
  }
});

defineEmits(['open-item', 'toggle-menu', 'show-context-menu']);

const getItemKey = (item) => {
  if (!item) return 'null-item';
  return `${item.item_type}-${item.id}`;
};

const formatFolderPath = (fullPath) => {
  if (!fullPath) return 'Thư mục gốc';
  
  const pathParts = fullPath.split('/').filter(part => part.trim());
  
  if (pathParts.length <= 2) {
    return pathParts.join('/');
  }
  
  return `${pathParts[0]}/.../${pathParts[pathParts.length - 1]}`;
};
</script>