<template>
  <div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="h3 mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-folder2-open text-primary"></i>
        <span class="fw-bold">Danh sách tài liệu</span>
        <span v-if="total > 0" class="badge bg-primary">{{ total }}</span>
      </h2>
      <button @click="resetFilters" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-clockwise"></i>
        Làm mới
      </button>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3">
          <!-- Search -->
          <div class="col-md-3">
            <label class="form-label small fw-semibold">
              <i class="bi bi-search"></i> Tìm kiếm
            </label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control"
              placeholder="Tên tài liệu..."
              @input="debounceSearch"
            />
          </div>

          <!-- Type Filter -->
          <div class="col-md-2">
            <label class="form-label small fw-semibold">
              <i class="bi bi-tag"></i> Loại tài liệu
            </label>
            <select v-model="filters.type_id" class="form-select" @change="applyFilters">
              <option value="">Tất cả</option>
              <option v-for="type in types" :key="type.type_id" :value="type.type_id">
                {{ type.name }}
              </option>
            </select>
          </div>

          <!-- Subject Filter -->
          <div class="col-md-2">
            <label class="form-label small fw-semibold">
              <i class="bi bi-book"></i> Môn học
            </label>
            <select v-model="filters.subject_id" class="form-select" @change="applyFilters">
              <option value="">Tất cả</option>
              <option v-for="subject in subjects" :key="subject.subject_id" :value="subject.subject_id">
                {{ subject.name }}
              </option>
            </select>
          </div>

          <!-- Sort By -->
          <div class="col-md-2">
            <label class="form-label small fw-semibold">
              <i class="bi bi-sort-down"></i> Sắp xếp theo
            </label>
            <select v-model="filters.sort_by" class="form-select" @change="applyFilters">
              <option value="created_at">Ngày tạo</option>
              <option value="title">Tên (A-Z)</option>
              <option value="size">Dung lượng</option>
              <option value="user">Người upload</option>
            </select>
          </div>

          <!-- Sort Order -->
          <div class="col-md-2">
            <label class="form-label small fw-semibold">
              <i class="bi bi-arrow-down-up"></i> Thứ tự
            </label>
            <select v-model="filters.sort_order" class="form-select" @change="applyFilters">
              <option value="desc">Giảm dần</option>
              <option value="asc">Tăng dần</option>
            </select>
          </div>

          <!-- Apply Button -->
          <div class="col-md-1 d-flex align-items-end">
            <button @click="applyFilters" class="btn btn-primary w-100">
              <i class="bi bi-funnel"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading && currentPage === 1" class="card shadow-sm">
      <div class="card-body">
        <div v-for="n in 5" :key="n" class="placeholder-glow mb-3">
          <span class="placeholder col-6 mb-2"></span>
          <span class="placeholder col-4"></span>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div v-else-if="documents.length > 0" class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">
                <i class="bi bi-file-earmark-text me-1"></i>
                Tên tài liệu
              </th>
              <th>
                <i class="bi bi-hdd me-1"></i>
                Dung lượng
              </th>
              <th>
                <i class="bi bi-tag me-1"></i>
                Loại
              </th>
              <th>
                <i class="bi bi-book me-1"></i>
                Môn học
              </th>
              <th>
                <i class="bi bi-person me-1"></i>
                Người upload
              </th>
              <th>
                <i class="bi bi-calendar me-1"></i>
                Ngày tạo
              </th>
              <th class="text-center">
                <i class="bi bi-gear me-1"></i>
                Thao tác
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="doc in documents"
              :key="doc.document_id"
              class="cursor-pointer"
              @click="goDetail(doc.document_id)"
            >
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  <i 
                    :class="getFileIcon(doc.title)" 
                    :style="`color: ${getFileColor(doc.title)}`"
                    class="fs-4"
                  ></i>
                  <span class="fw-medium text-primary">{{ doc.title }}</span>
                </div>
              </td>
              <td>
                <span class="badge bg-light text-dark">
                  {{ formatSize(doc.size) }}
                </span>
              </td>
              <td>
                <span class="badge bg-info">
                  {{ doc.type?.name || '—' }}
                </span>
              </td>
              <td>
                <span class="badge bg-success">
                  {{ doc.subject?.name || '—' }}
                </span>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-person-circle text-secondary"></i>
                  <span>{{ doc.user?.name || '—' }}</span>
                </div>
              </td>
              <td>
                <small class="text-muted">
                  {{ formatDate(doc.created_at) }}
                </small>
              </td>
              <td class="text-center">
                <button 
                  @click.stop="goDetail(doc.document_id)"
                  class="btn btn-sm btn-outline-primary"
                >
                  <i class="bi bi-eye"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="card shadow-sm">
      <div class="card-body text-center py-5">
        <i class="bi bi-inbox text-muted" style="font-size: 5rem;"></i>
        <p class="text-muted mt-3 mb-0">Không tìm thấy tài liệu nào</p>
        <button @click="resetFilters" class="btn btn-sm btn-outline-primary mt-3">
          <i class="bi bi-arrow-clockwise"></i>
          Đặt lại bộ lọc
        </button>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="lastPage > 1" class="mt-4">
      <nav aria-label="Document pagination">
        <ul class="pagination justify-content-center mb-0">
          <!-- First Page -->
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <a class="page-link" href="#" @click.prevent="goToPage(1)">
              <i class="bi bi-chevron-double-left"></i>
            </a>
          </li>

          <!-- Previous Page -->
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>

          <!-- Page Numbers -->
          <li
            v-for="page in visiblePages"
            :key="page"
            class="page-item"
            :class="{ active: page === currentPage }"
          >
            <a class="page-link" href="#" @click.prevent="goToPage(page)">
              {{ page }}
            </a>
          </li>

          <!-- Next Page -->
          <li class="page-item" :class="{ disabled: currentPage === lastPage }">
            <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>

          <!-- Last Page -->
          <li class="page-item" :class="{ disabled: currentPage === lastPage }">
            <a class="page-link" href="#" @click.prevent="goToPage(lastPage)">
              <i class="bi bi-chevron-double-right"></i>
            </a>
          </li>
        </ul>
      </nav>

      <!-- Page Info -->
      <div class="text-center mt-3">
        <small class="text-muted">
          Hiển thị {{ (currentPage - 1) * perPage + 1 }} - 
          {{ Math.min(currentPage * perPage, total) }} 
          trong tổng số {{ total }} tài liệu
        </small>
      </div>
    </div>

    <!-- Loading More -->
    <div v-if="loading && currentPage > 1" class="text-center py-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Đang tải...</span>
      </div>
    </div>

    <!-- Scroll to Top Button -->
    <button
      v-if="showScrollTop"
      @click="scrollToTop"
      class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-4 shadow"
      style="width: 50px; height: 50px; z-index: 1000;"
    >
      <i class="bi bi-arrow-up"></i>
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'

// State
const documents = ref([])
const types = ref([])
const subjects = ref([])
const loading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const perPage = ref(20)
const showScrollTop = ref(false)

// Filters
const filters = ref({
  search: '',
  type_id: '',
  subject_id: '',
  sort_by: 'created_at',
  sort_order: 'desc'
})

let searchTimeout = null

// Computed - Visible Pages
const visiblePages = computed(() => {
  const pages = []
  const current = currentPage.value
  const last = lastPage.value
  
  // Show max 10 pages
  let start = Math.max(1, current - 5)
  let end = Math.min(last, current + 4)
  
  // Adjust if at start or end
  if (end - start < 9) {
    if (start === 1) {
      end = Math.min(last, start + 9)
    } else {
      start = Math.max(1, end - 9)
    }
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Fetch Documents
const fetchDocuments = async (page = 1) => {
  loading.value = true
  
  try {
    const params = {
      page,
      per_page: perPage.value,
      search: filters.value.search,
      type_id: filters.value.type_id,
      subject_id: filters.value.subject_id,
      sort_by: filters.value.sort_by,
      sort_order: filters.value.sort_order
    }
    
    const res = await axios.get('/api/documents', { params })
    
    documents.value = res.data.data
    currentPage.value = res.data.current_page
    lastPage.value = res.data.last_page
    total.value = res.data.total
    perPage.value = res.data.per_page
    
  } catch (error) {
    console.error('Fetch documents error:', error)
  } finally {
    loading.value = false
  }
}

// Fetch Types
const fetchTypes = async () => {
  try {
    const res = await axios.get('/api/types')
    types.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
  } catch (error) {
    console.error('Fetch types error:', error)
  }
}

// Fetch Subjects
const fetchSubjects = async () => {
  try {
    const res = await axios.get('/api/subjects')
    subjects.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
  } catch (error) {
    console.error('Fetch subjects error:', error)
  }
}

// Debounce Search
const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

// Apply Filters
const applyFilters = () => {
  currentPage.value = 1
  fetchDocuments(1)
  scrollToTop()
}

// Reset Filters
const resetFilters = () => {
  filters.value = {
    search: '',
    type_id: '',
    subject_id: '',
    sort_by: 'created_at',
    sort_order: 'desc'
  }
  applyFilters()
}

// Go to Page
const goToPage = (page) => {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return
  fetchDocuments(page)
  scrollToTop()
}

// Go to Detail
const goDetail = (id) => {
  window.location.href = `/documents/${id}`
}

// Scroll to Top
const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Handle Scroll
const handleScroll = () => {
  showScrollTop.value = window.scrollY > 300
}

// Format Size
const formatSize = (bytes) => {
  if (!bytes) return 'N/A'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}

// Format Date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Get File Icon
const getFileIcon = (filename) => {
  const ext = filename.split('.').pop()?.toLowerCase()
  const icons = {
    pdf: 'bi bi-file-earmark-pdf-fill',
    doc: 'bi bi-file-earmark-word-fill',
    docx: 'bi bi-file-earmark-word-fill',
    xls: 'bi bi-file-earmark-excel-fill',
    xlsx: 'bi bi-file-earmark-excel-fill',
    ppt: 'bi bi-file-earmark-ppt-fill',
    pptx: 'bi bi-file-earmark-ppt-fill',
    txt: 'bi bi-file-earmark-text-fill',
    zip: 'bi bi-file-earmark-zip-fill'
  }
  return icons[ext] || 'bi bi-file-earmark-fill'
}

// Get File Color
const getFileColor = (filename) => {
  const ext = filename.split('.').pop()?.toLowerCase()
  const colors = {
    pdf: '#dc3545',
    doc: '#2b579a',
    docx: '#2b579a',
    xls: '#217346',
    xlsx: '#217346',
    ppt: '#d24726',
    pptx: '#d24726',
    txt: '#6c757d',
    zip: '#ffc107'
  }
  return colors[ext] || '#6c757d'
}

// Keyboard Navigation
const handleKeyboard = (e) => {
  if (e.key === 'ArrowLeft' && currentPage.value > 1) {
    goToPage(currentPage.value - 1)
  } else if (e.key === 'ArrowRight' && currentPage.value < lastPage.value) {
    goToPage(currentPage.value + 1)
  }
}

// Lifecycle
onMounted(() => {
  fetchDocuments(1)
  fetchTypes()
  fetchSubjects()
  window.addEventListener('scroll', handleScroll)
  window.addEventListener('keydown', handleKeyboard)
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('keydown', handleKeyboard)
})
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.cursor-pointer:hover {
  background-color: rgba(13, 110, 253, 0.05);
}

.page-link {
  cursor: pointer;
}

.placeholder-glow .placeholder {
  animation: placeholder-glow 2s ease-in-out infinite;
}

@keyframes placeholder-glow {
  50% {
    opacity: 0.2;
  }
}

.table-hover tbody tr:hover {
  transform: translateY(-1px);
  transition: transform 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
</style>