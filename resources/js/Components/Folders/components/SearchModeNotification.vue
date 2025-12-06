<template>
  <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
    <div class="flex items-center">
      <i class="fas fa-search text-blue-500 mr-3"></i>
      <div>
        <p class="text-blue-800 font-medium">Đang hiển thị kết quả tìm kiếm</p>
        <p class="text-blue-600 text-sm">Tìm thấy {{ totalResults }} kết quả phù hợp</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  totalResults: {
    type: Number,
    default: 0
  }
});
</script>