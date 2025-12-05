<template>
  <div class="folder-tree-inner">
    <div v-if="!folders || folders.length === 0" class="text-muted small fst-italic px-3">
      (Trống)
    </div>

    <div
      v-for="folder in folders"
      :key="folderKey(folder)"
      class="folder-item-wrapper"
    >
      <div
        class="folder-item d-flex align-items-center px-3 py-2 hover-bg-light cursor-pointer rounded position-relative"
        :class="{ 'bg-primary bg-opacity-10': selectedId === folder.id }"
        :style="{ paddingLeft: (depth * 20 + 10) + 'px' }"
        @click="handleSelect(folder)"
      >
        <div 
          v-if="folder.children && folder.children.length"
          class="me-2 d-flex align-items-center justify-content-center"
          style="width: 16px; height: 16px; cursor: pointer"
          @click.stop="toggleExpand(folder.id)"
        >
          <i
            class="bi"
            :class="isExpanded(folder.id) ? 'bi-chevron-down' : 'bi-chevron-right'"
            style="font-size: 10px;"
          ></i>
        </div>
        
        <span v-else style="width: 16px; display: inline-block; margin-right: 0.5rem"></span>

        <i class="bi me-2" 
           :class="[
             isExpanded(folder.id) ? 'bi-folder2-open' : 'bi-folder2', 
             selectedId === folder.id ? 'text-primary' : 'text-warning'
           ]">
        </i>
        
        <span class="flex-grow-1 text-truncate" :class="{'fw-bold text-primary': selectedId === folder.id}">
            {{ folder.name }}
        </span>

        <i v-if="selectedId === folder.id" class="bi bi-check2 text-primary ms-2"></i>
      </div>

      <div v-if="isExpanded(folder.id) && folder.children && folder.children.length">
          <FoldersTreee
            :folders="folder.children"
            :depth="depth + 1"
            :selected-id="selectedId"
            @select="emit('select', $event)"
          />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

// 🔥 QUAN TRỌNG: Import chính component này để có thể dùng đệ quy
// Nếu bạn đang dùng Vue 3.3+ thì có thể dùng defineOptions({ name: 'FoldersTreee' })
// Nhưng cách import này an toàn nhất cho mọi version.
import FoldersTreee from './FoldersTreee.vue'

const props = defineProps({
  folders: {
    type: Array,
    default: () => [] // Luôn trả về mảng rỗng nếu không có dữ liệu
  },
  depth: { type: Number, default: 0 },
  selectedId: [Number, String] // Chấp nhận cả chuỗi hoặc số
})

const emit = defineEmits(['select'])

const expandedIds = ref(new Set())

// Fix key logic an toàn hơn
const folderKey = (folder) => folder.id || `temp-${Math.random()}`

const isExpanded = (id) => expandedIds.value.has(id)

const toggleExpand = (id) => {
  if (expandedIds.value.has(id)) {
    expandedIds.value.delete(id)
  } else {
    expandedIds.value.add(id)
  }
}

const handleSelect = (folder) => {
  emit('select', folder)
}
</script>

<style scoped>
.hover-bg-light:hover {
  background-color: #f8f9fa;
}
.cursor-pointer {
  cursor: pointer;
}
</style>