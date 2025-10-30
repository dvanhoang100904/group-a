<template>
  <div>
    <div class="flex justify-between mb-3">
      <input v-model="search" @input="fetchDocuments" placeholder="🔍 Tìm tài liệu..." class="border rounded p-2 w-1/3" />
    </div>

    <table class="min-w-full border text-sm">
      <thead class="bg-gray-100 text-left">
        <tr>
          <th class="p-2">🗂️ Tên</th>
          <th class="p-2">📦 Dung lượng</th>
          <th class="p-2">🧩 Loại</th>
          <th class="p-2">👤 Người upload</th>
          <th class="p-2">📅 Ngày</th>
          <th class="p-2">⚙️ Hành động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="doc in documents" :key="doc.document_id" class="border-b hover:bg-gray-50">
          <td class="p-2">{{ doc.title }}</td>
          <td class="p-2">{{ doc.size }}</td>
          <td class="p-2">{{ doc.type?.name || '—' }}</td>
          <td class="p-2">{{ doc.user?.name || 'Ẩn danh' }}</td>
          <td class="p-2">{{ new Date(doc.created_at).toLocaleDateString() }}</td>
          <td class="p-2">
            <button class="text-blue-600" @click="viewDetail(doc)">👁️</button>
            <button class="text-yellow-600 ml-2" @click="editDoc(doc)">✏️</button>
            <button class="text-red-600 ml-2" @click="deleteDoc(doc.document_id)">🗑️</button>
            <a v-if="doc.file_path" :href="doc.file_path" class="text-green-600 ml-2" download>⬇️</a>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal -->
    <div v-if="selectedDoc" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white p-4 rounded shadow w-1/2">
        <h3 class="font-bold text-lg mb-2">{{ selectedDoc.title }}</h3>
        <p><b>Người upload:</b> {{ selectedDoc.user?.name }}</p>
        <p><b>Dung lượng:</b> {{ selectedDoc.size }}</p>
        <p><b>Mô tả:</b> {{ selectedDoc.description || 'Không có mô tả' }}</p>
        <div class="mt-3 text-right">
          <button @click="selectedDoc=null" class="px-3 py-1 bg-gray-300 rounded">Đóng</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from "axios";
import { ref, onMounted } from "vue";

const documents = ref([]);
const search = ref("");
const selectedDoc = ref(null);

async function fetchDocuments() {
  try {
    const res = await axios.get(`/documents?search=${search.value}`);
    if (res.data.success) documents.value = res.data.data;
  } catch (err) {
    console.error("Lỗi khi tải tài liệu:", err);
  }
}

async function viewDetail(doc) {
  try {
    const res = await axios.get(`/api/documents/${doc.document_id}`);
    if (res.data.success) selectedDoc.value = res.data.data;
  } catch (e) {
    alert("Không thể xem chi tiết");
  }
}

async function deleteDoc(id) {
  if (!confirm("Bạn có chắc muốn xóa tài liệu này?")) return;
  try {
    const res = await axios.delete(`/api/documents/${id}`);
    if (res.data.success) fetchDocuments();
  } catch (e) {
    alert("Xóa thất bại");
  }
}

function editDoc(doc) {
  const newTitle = prompt("Nhập tên mới:", doc.title);
  if (!newTitle) return;
  axios.put(`/api/documents/${doc.document_id}`, { title: newTitle }).then(fetchDocuments);
}

onMounted(fetchDocuments);
</script>

<style scoped>
table {
  border-collapse: collapse;
  width: 100%;
}
th, td {
  border-bottom: 1px solid #ddd;
}
</style>
