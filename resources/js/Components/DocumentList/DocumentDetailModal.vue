<template>
  <div
    class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center"
    @click.self="$emit('close')"
  >
    <div class="bg-white p-6 rounded-lg shadow-lg w-1/2">
      <h3 class="text-xl font-bold mb-2">📄 Chi tiết tài liệu</h3>
      <p><strong>Tiêu đề:</strong> {{ document?.title }}</p>
      <p><strong>Môn học:</strong> {{ document?.subject?.name || 'Không có' }}</p>
      <p><strong>Loại:</strong> {{ document?.type?.name || 'Không có' }}</p>
      <p><strong>Người tạo:</strong> {{ document?.user?.name || 'Không rõ' }}</p>
      <p><strong>Ngày tạo:</strong> {{ formatDate(document?.created_at) }}</p>

      <div class="text-right mt-4">
        <button
          class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"
          @click="$emit('close')"
        >
          Đóng
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from "vue";

const props = defineProps({
  document: Object,
  show: Boolean,
});

const formatDate = (date) => {
  return new Date(date).toLocaleDateString("vi-VN");
};
</script>
