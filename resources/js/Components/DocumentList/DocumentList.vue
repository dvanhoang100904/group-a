<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <h2 class="text-3xl font-extrabold mb-6 text-indigo-700 flex items-center gap-2">
      📂 Danh sách tài liệu
      <span class="animate-pulse text-indigo-500 text-xl">✨</span>
    </h2>

    <div class="overflow-x-auto shadow-lg rounded-lg bg-white">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-indigo-100 text-left text-indigo-900 uppercase tracking-wider">
          <tr>
            <th class="px-4 py-3">Tên</th>
            <th class="px-4 py-3">Dung lượng</th>
            <th class="px-4 py-3">Loại</th>
            <th class="px-4 py-3">Người upload</th>
            <th class="px-4 py-3">Ngày tạo</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="doc in documents"
            :key="doc.document_id"
            class="hover:bg-indigo-50 transition-colors duration-200 cursor-pointer"
          >
            <td class="px-4 py-3 font-medium text-indigo-700">{{ doc.title }}</td>
            <td class="px-4 py-3">
              {{ doc.size ? (doc.size / 1024).toFixed(2) + ' KB' : 'N/A' }}
            </td>
            <td class="px-4 py-3">
              <span
                class="px-2 py-1 rounded-full text-xs font-semibold"
                :class="{
                  'bg-green-100 text-green-800': doc.type?.name === 'public',
                  'bg-yellow-100 text-yellow-800': doc.type?.name === 'restricted',
                  'bg-gray-100 text-gray-600': !doc.type?.name
                }"
              >
                {{ doc.type?.name || '—' }}
              </span>
            </td>
            <td class="px-4 py-3">{{ doc.user?.name || 'Ẩn danh' }}</td>
            <td class="px-4 py-3">{{ new Date(doc.created_at).toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="documents.length === 0" class="mt-6 text-center text-gray-400">
      Không có tài liệu nào. 😢
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const documents = ref([]);

const fetchDocuments = async () => {
  try {
    const res = await axios.get("/api/documents");
    if (res.data.success) documents.value = res.data.data;
  } catch (e) {
    console.error("Lỗi tải danh sách:", e);
  }
};

onMounted(fetchDocuments);
</script>

<style>
/* Optional: glow animation cho hover row */
tr:hover td {
  transform: translateY(-2px);
  transition: transform 0.2s ease-in-out;
}
</style>
