<template>
  <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded shadow-lg w-3/4 max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold">📄 Chi tiết: {{ document.title }}</h3>
        <button @click="$emit('close')" class="text-red-500 font-bold">✖</button>
      </div>

      <div class="mb-4">
        <p><strong>Mô tả:</strong> {{ document.description }}</p>
        <p><strong>Loại:</strong> {{ document.type?.name || '—' }}</p>
        <p><strong>Chủ sở hữu:</strong> {{ document.user?.name || 'Ẩn danh' }}</p>
        <p><strong>Ngày tạo:</strong> {{ new Date(document.created_at).toLocaleString() }}</p>
      </div>

      <div class="mb-4">
        <h4 class="font-bold mb-2">Phiên bản:</h4>
        <table class="w-full border text-sm">
          <thead class="bg-gray-100">
            <tr>
              <th class="p-2">#</th>
              <th class="p-2">File</th>
              <th class="p-2">Size</th>
              <th class="p-2">Uploader</th>
              <th class="p-2">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ver in document.versions || []" :key="ver.version_id" class="border-b">
              <td class="p-2">{{ ver.version_number }}</td>
              <td class="p-2">{{ ver.file_path.split('/').pop() }}</td>
              <td class="p-2">{{ (ver.file_size/1024).toFixed(2) + ' KB' }}</td>
              <td class="p-2">{{ ver.user?.name || 'Ẩn danh' }}</td>
              <td class="p-2 flex gap-2">
                <a
                  v-if="ver.file_path"
                  :href="ver.file_path"
                  target="_blank"
                  class="bg-green-500 px-2 py-1 rounded text-white hover:bg-green-600"
                  >Download</a
                >
                <button @click="deleteVersion(ver)" class="bg-red-500 px-2 py-1 rounded text-white hover:bg-red-600">
                  Xóa
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex gap-2">
        <input type="file" ref="fileInput" class="hidden" @change="uploadVersion" />
        <button @click="$refs.fileInput.click()" class="bg-blue-500 px-3 py-1 rounded text-white hover:bg-blue-600">
          Upload version mới
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import axios from "axios";

const props = defineProps({
  document: Object
});

const emit = defineEmits(["close", "updated"]);

const uploadVersion = async (e) => {
  const file = e.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append("file", file);

  try {
    await axios.post(`/api/documents/${props.document.document_id}/versions`, formData);
    emit("updated"); // refresh list
  } catch (err) {
    console.error("Upload thất bại:", err);
  }
};

const deleteVersion = async (ver) => {
  if (!confirm(`Bạn có chắc muốn xóa version #${ver.version_number}?`)) return;
  try {
    await axios.delete(`/api/documents/${props.document.document_id}/versions/${ver.version_id}`);
    emit("updated");
  } catch (err) {
    console.error("Xóa thất bại:", err);
  }
};
</script>
