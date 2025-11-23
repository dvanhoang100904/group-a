<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <h2 class="text-3xl font-extrabold mb-6 text-indigo-700 flex items-center gap-2">
      📂 Danh sách tài liệu
      <span class="animate-pulse text-indigo-500 text-xl">✨</span>
    </h2>

    <!-- Bộ lọc -->
    <div class="flex items-center gap-4 mb-4">
      <select v-model="filterType" class="border rounded-lg px-3 py-2">
        <option value="">Tất cả loại</option>
        <option value="public">Công khai</option>
        <option value="restricted">Giới hạn</option>
      </select>

      <input
        v-model="search"
        type="text"
        placeholder="Tìm theo tên tài liệu..."
        class="border rounded-lg px-3 py-2 w-64"
      />

      <button
        @click="reloadFilter"
        class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition"
      >
        Lọc
      </button>
    </div>

    <!-- Skeleton Loading -->
    <div v-if="loading && page === 1">
      <div
        v-for="n in 5"
        :key="n"
        class="animate-pulse bg-white shadow rounded-lg p-4 mb-3"
      >
        <div class="h-4 bg-gray-300 rounded w-1/3 mb-2"></div>
        <div class="h-3 bg-gray-200 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Table -->
    <div v-if="documents.length > 0" class="overflow-x-auto shadow-lg rounded-lg bg-white">
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
            @click="goDetail(doc.document_id)"
          >
            <td class="px-4 py-3 font-medium text-indigo-700">{{ doc.title }}</td>
            <td class="px-4 py-3">{{ (doc.size / 1024).toFixed(2) }} KB</td>
            <td class="px-4 py-3">
              <span
                class="px-2 py-1 rounded-full text-xs font-semibold"
                :class="{
                  'bg-green-100 text-green-800': doc.type?.name === 'public',
                  'bg-yellow-100 text-yellow-800': doc.type?.name === 'restricted',
                }"
              >
                {{ doc.type?.name || '—' }}
              </span>
            </td>
            <td class="px-4 py-3">{{ doc.user?.name }}</td>
            <td class="px-4 py-3">{{ new Date(doc.created_at).toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Không có dữ liệu -->
    <div v-if="!loading && documents.length === 0" class="mt-6 text-center text-gray-400">
      Không có tài liệu nào. 😢
    </div>

    <!-- Infinite scroll loading -->
    <div v-if="loading && page > 1" class="flex justify-center py-6">
      <div class="animate-spin rounded-full h-8 w-8 border-4 border-indigo-500 border-t-transparent"></div>
    </div>

    <div ref="bottomTrigger"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";

const documents = ref([]);
const loading = ref(true);
const page = ref(1);
const totalPages = ref(1);
const bottomTrigger = ref(null);

// Filters
const filterType = ref("");
const search = ref("");

// Fetch API
const fetchDocuments = async (pageNum = 1) => {
  loading.value = true;

  const res = await axios.get("/api/documents", {
    params: {
      page: pageNum,
      per_page: 10,
      type: filterType.value,
      search: search.value,
    },
  });

  totalPages.value = res.data.last_page;

  if (pageNum === 1) {
    documents.value = res.data.data;
    scrollToTop();
  } else {
    documents.value.push(...res.data.data);
  }

  loading.value = false;
};

// Reset filter
const reloadFilter = () => {
  page.value = 1;
  fetchDocuments(1);
};

// Auto-scroll to top on page change
const scrollToTop = () => {
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });
};

// Infinite Scroll
const initInfiniteScroll = () => {
  const observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting && page.value < totalPages.value && !loading.value) {
        page.value++;
        fetchDocuments(page.value);
      }
    },
    { threshold: 1 }
  );

  if (bottomTrigger.value) {
    observer.observe(bottomTrigger.value);
  }
};

// Điều hướng bằng phím ← →
window.addEventListener("keydown", (e) => {
  if (e.key === "ArrowLeft" && page.value > 1) {
    page.value--;
    fetchDocuments(page.value);
  }
  if (e.key === "ArrowRight" && page.value < totalPages.value) {
    page.value++;
    fetchDocuments(page.value);
  }
});

// chuyển trang chi tiết
const goDetail = (id) => {
  window.location.href = `/documents/${id}`;
};

onMounted(() => {
  fetchDocuments(1);
  initInfiniteScroll();
});
</script>

<style>
tr:hover td {
  transform: translateY(-2px);
  transition: transform 0.2s ease-in-out;
}
</style>
