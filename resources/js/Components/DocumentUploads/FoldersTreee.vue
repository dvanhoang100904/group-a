<template>
  <div>
    <div
      v-for="folder in folders"
      :key="folder.id"
      class="folder-item-wrapper"
    >
      <!-- Folder hiện tại -->
      <div
        class="d-flex align-items-center px-3 py-2 hover-bg-light cursor-pointer rounded position-relative"
        :class="{ 'bg-primary bg-opacity-10': selectedId === folder.id }"
        :style="{ paddingLeft: (depth * 24 + 12) + 'px' }"
        @click="handleSelect(folder)"
      >
        <!-- Toggle icon nếu có children -->
        <i
          v-if="folder.children && folder.children.length"
          class="bi me-2 cursor-pointer"
          :class="isExpanded(folder.id) ? 'bi-chevron-down' : 'bi-chevron-right'"
          @click.stop="toggleExpand(folder.id)"
          style="font-size: 12px; width: 16px"
        ></i>
        <span v-else style="width: 16px; display: inline-block"></span>

        <!-- Folder icon -->
        <i class="bi bi-folder-fill text-warning me-2"></i>

        <!-- Folder name -->
        <span class="flex-grow-1">{{ folder.name }}</span>

        <!-- Checkmark nếu được chọn -->
        <i
          v-if="selectedId === folder.id"
          class="bi bi-check2 text-success"
        ></i>
      </div>

      <!-- Children (đệ quy) -->
      <div v-if="folder.children && folder.children.length && isExpanded(folder.id)">
        <FoldersTreee
          :folders="folder.children"
          :depth="depth + 1"
          :selected-id="selectedId"
          @select="$emit('select', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  folders: {
    type: Array,
    required: true
  },
  depth: {
    type: Number,
    default: 0
  },
  selectedId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['select'])

// Quản lý trạng thái expand/collapse
const expandedIds = ref(new Set())

const isExpanded = (id) => {
  return expandedIds.value.has(id)
}

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
  background-color: #f8f9fa !important;
}

.cursor-pointer {
  cursor: pointer;
}

.folder-item-wrapper {
  user-select: none;
}
</style>