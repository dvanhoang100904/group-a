<template>
  <div class="upload-container bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">📁 Tải tài liệu lên hệ thống</h2>

    <!-- Drag & Drop Zone -->
    <div
      class="border-2 border-dashed rounded-lg p-6 text-center transition-all duration-300"
      :class="isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50'"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleDrop"
    >
      <div class="text-6xl mb-3">{{ isDragging ? '📥' : '📂' }}</div>
      <p class="text-gray-600 mb-3">
        {{ isDragging ? 'Thả file tại đây' : 'Kéo thả file vào đây hoặc' }}
      </p>
      <div class="flex items-center justify-center gap-3">
        <button
          @click="triggerFileInput"
          class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
        >
          Chọn file từ máy tính
        </button>
        <button
          @click="fetchCurrentFolderIndex"
          class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition"
        >
          Làm mới thư mục lưu
        </button>
      </div>

      <input
        ref="fileInput"
        type="file"
        multiple
        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
        class="hidden"
        @change="handleFileSelect"
      />

      <p class="text-xs text-gray-500 mt-3">
        Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP (tối đa 50MB/file)
      </p>
      <p v-if="currentFolderIndex" class="text-xs text-gray-600 mt-2">Thư mục lưu hiện tại: <b>{{ currentFolderIndex }}</b></p>
    </div>

    <!-- File List -->
    <div v-if="files.length" class="mt-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-700">
          📋 Danh sách file ({{ files.length }})
        </h3>
        <div class="flex items-center gap-3">
          <button
            v-if="pendingFiles > 0"
            @click="uploadAll"
            :disabled="uploading"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 transition"
          >
            {{ uploading ? '⏳ Đang tải...' : `⬆️ Tải lên tất cả (${pendingFiles})` }}
          </button>
          <button
            @click="clearAll"
            class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition"
          >
            🗑️ Xóa tất cả
          </button>
        </div>
      </div>

      <!-- List of Files -->
      <div class="space-y-3">
        <div
          v-for="(file, index) in files"
          :key="file.uid"
          class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <div class="text-2xl">{{ getFileIcon(file.name) }}</div>
                <div class="flex-1">
                  <!-- Editable document name (without extension) -->
                  <div class="flex gap-2 items-center">
                    <input
                      v-model="file.title"
                      class="border px-2 py-1 rounded w-64"
                      :placeholder="file.originalNameNoExt"
                    />
                    <span class="text-sm text-gray-500">.{{ file.ext }}</span>
                    <span class="ml-3 text-xs text-gray-500">({{ file.size }})</span>
                  </div>

                  <!-- type & folder info -->
                  <div class="flex items-center gap-3 mt-2">
                    <select v-model="file.type_id" class="border rounded px-2 py-1 text-sm">
                      <option value="">-- Chọn loại (type) --</option>
                      <option v-for="t in types" :key="t.type_id" :value="t.type_id">
                        {{ t.name }}
                      </option>
                    </select>

                    <select v-model="file.permission" class="border rounded px-2 py-1 text-sm">
                      <option v-for="(label, key) in permissionOptions" :key="key" :value="key">
                        {{ label }}
                      </option>
                    </select>

                    <div class="text-xs text-gray-500 ml-2">
                      Thư mục: <b>{{ file.folderIndex || currentFolderIndex || '... (chưa xác định)' }}</b>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Progress Bar -->
              <div v-if="file.progress !== null" class="mb-2">
                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                  <div
                    class="h-2.5 rounded-full transition-all duration-300"
                    :class="file.status === 'error' ? 'bg-red-500' : 'bg-blue-600'"
                    :style="{ width: file.progress + '%' }"
                  ></div>
                </div>
                <p class="text-xs text-gray-600 mt-1">{{ file.progress }}%</p>
              </div>

              <!-- Status Badge & preview state -->
              <div class="flex items-center gap-3 mt-2">
                <span
                  class="px-3 py-1 rounded-full text-xs font-medium"
                  :class="{
                    'bg-gray-200 text-gray-700': file.status === 'pending',
                    'bg-blue-200 text-blue-700': file.status === 'uploading',
                    'bg-green-200 text-green-700': file.status === 'done',
                    'bg-red-200 text-red-700': file.status === 'error'
                  }"
                >
                  {{
                    file.status === 'pending' ? '⏸️ Chờ tải' :
                    file.status === 'uploading' ? '⏳ Đang tải...' :
                    file.status === 'done' ? '✅ Hoàn tất' :
                    '❌ Lỗi'
                  }}
                </span>

                <span v-if="file.is_converting" class="text-xs text-indigo-600">🔄 Đang tạo preview PDF...</span>
                <span v-else-if="file.preview_ready" class="text-xs text-green-600">📄 Preview sẵn sàng</span>

                <span v-if="file.errorMessage" class="text-xs text-red-600 ml-2">
                  {{ file.errorMessage }}
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col gap-2 items-end">
              <button
                v-if="file.status === 'pending'"
                @click="uploadSingle(file, index)"
                class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition"
              >
                ⬆️ Tải
              </button>

              <button
                v-else-if="file.status === 'done'"
                @click="downloadPreview(file)"
                class="px-3 py-1 bg-indigo-500 text-white text-sm rounded hover:bg-indigo-600 transition"
              >
                📄 Xem preview
              </button>

              <button
                @click="removeFile(index)"
                class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600 transition"
              >
                🗑️ Xóa
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8 text-gray-500">
      <p class="text-lg">Chưa có file nào được chọn</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { v4 as uuidv4 } from 'uuid'

// === Reactive States ===
const files = ref([])
const isDragging = ref(false)
const uploading = ref(false)
const fileInput = ref(null)
const types = ref([])
const currentFolderIndex = ref(null)

// permission options (key => label)
const permissionOptions = {
  view: '👁️ Chỉ xem',
  edit: '✏️ Sửa',
  download: '⬇️ Tải xuống',
  full: '🛠️ Toàn quyền'
}

// === Computed ===
const pendingFiles = computed(() =>
  files.value.filter(f => f.status === 'pending').length
)

// === Lifecycle ===
onMounted(async () => {
  await fetchTypes()
  await fetchCurrentFolderIndex()
})

// === Helpers ===
const triggerFileInput = () => fileInput.value.click()
const handleFileSelect = (event) => {
  prepareFiles(event.target.files)
  event.target.value = null
}
const handleDrop = (event) => {
  isDragging.value = false
  prepareFiles(event.dataTransfer.files)
}

// prepare files into local structure
const prepareFiles = (fileList) => {
  for (const f of fileList) {
    const sizeMB = (f.size / 1024 / 1024).toFixed(2)
    if (f.size > 50 * 1024 * 1024) {
      alert(`❌ File "${f.name}" vượt quá 50MB!`)
      continue
    }

    const parts = f.name.split('.')
    const ext = parts.length > 1 ? parts.pop().toLowerCase() : ''
    const baseName = parts.join('.')
    files.value.push({
      uid: uuidv4(),
      file: f,
      originalName: f.name,
      originalNameNoExt: baseName,
      title: baseName,          // editable title (without ext)
      ext,
      name: f.name,
      size: `${sizeMB} MB`,
      progress: 0,
      status: 'pending',        // pending | uploading | done | error
      errorMessage: null,
      type_id: '',              // selected type id
      permission: 'view',       // default permission
      folderIndex: null,        // assigned by backend
      is_converting: false,
      preview_ready: false,
      preview_url: null
    })
  }
}

// === API helpers ===
const fetchTypes = async () => {
  try {
    const res = await axios.get('/api/types')
    types.value = res.data?.data || []
  } catch (err) {
    console.error('Không lấy được types:', err)
    types.value = []
  }
}

const fetchCurrentFolderIndex = async () => {
  try {
    const res = await axios.get('/api/folders/current') // backend phải trả về { folderIndex: 'originals1' }
    currentFolderIndex.value = res.data?.folderIndex || null
  } catch (err) {
    console.warn('Không thể lấy folder hiện tại:', err)
    currentFolderIndex.value = null
  }
}

// === Remove / clear ===
const removeFile = (index) => files.value.splice(index, 1)
const clearAll = () => files.value.splice(0, files.value.length)

// === Download preview (open new tab) ===
const downloadPreview = (fileObj) => {
  if (!fileObj.preview_url) {
    alert('Preview chưa sẵn sàng.')
    return
  }
  window.open(fileObj.preview_url, '_blank')
}

// === Upload single file ===
const uploadSingle = async (fileObj, index) => {
  if (fileObj.status === 'uploading' || fileObj.status === 'done') return

  fileObj.status = 'uploading'
  fileObj.errorMessage = null
  fileObj.progress = 0

  // if folderIndex not set, ask backend for current folder
  if (!fileObj.folderIndex) {
    try {
      const resp = await axios.get('/api/folders/current')
      fileObj.folderIndex = resp.data.folderIndex || currentFolderIndex.value
    } catch (err) {
      console.warn('Không lấy được folderIndex, dùng mặc định', err)
      fileObj.folderIndex = currentFolderIndex.value
    }
  }

  const formData = new FormData()
  formData.append('file', fileObj.file)
  formData.append('title', fileObj.title)
  formData.append('type_id', fileObj.type_id || '')
  formData.append('permission', fileObj.permission)
  formData.append('folder_index', fileObj.folderIndex || '')
  // extra: if you want original name with ext
  formData.append('original_name', fileObj.originalName)

  try {
    const response = await axios.post('/api/documents/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      timeout: 120000, // 2 minutes for large uploads + convert
      onUploadProgress: (event) => {
        if (event.total) {
          fileObj.progress = Math.round((event.loaded * 100) / event.total)
        }
      },
    })

    // Expect backend to return useful info: preview_ready (bool), preview_url (if ready), conversion_started (bool)
    const data = response.data || {}

    if (data.success) {
      fileObj.status = 'done'
      fileObj.progress = 100

      // if backend indicates conversion is in progress => show converting
      fileObj.is_converting = !!data.conversion_started
      fileObj.preview_ready = !!data.preview_ready
      fileObj.preview_url = data.preview_url || null

      // if conversion started but not ready: poll for preview
      if (fileObj.is_converting && !fileObj.preview_ready) {
        pollPreviewStatus(data.document_id, fileObj)
      }

    } else {
      throw new Error(data.message || 'Upload thất bại (Server phản hồi không hợp lệ)')
    }
  } catch (error) {
    fileObj.status = 'error'
    fileObj.progress = 0
    fileObj.errorMessage =
      error.response?.data?.message ||
      (error.code === 'ECONNABORTED'
        ? '⏱️ Quá thời gian kết nối (timeout)'
        : error.message || 'Lỗi không xác định')
    console.error('❌ Upload error:', error)
  }
}

// === Upload all sequentially ===
const uploadAll = async () => {
  if (uploading.value) return
  uploading.value = true

  for (let i = 0; i < files.value.length; i++) {
    const f = files.value[i]
    if (f.status === 'pending') {
      // ensure minimal metadata: title and type
      if (!f.title) f.title = f.originalNameNoExt
      await uploadSingle(f, i)
    }
  }

  uploading.value = false
}

// === Poll preview status if conversion is async on backend ===
const pollPreviewStatus = async (documentId, fileObj, attempt = 0) => {
  if (attempt > 20) {
    fileObj.is_converting = false
    fileObj.errorMessage = 'Không tạo được preview sau nhiều lần thử.'
    return
  }

  try {
    const res = await axios.get(`/api/documents/${documentId}/preview-status`)
    if (res.data.preview_ready) {
      fileObj.preview_ready = true
      fileObj.preview_url = res.data.preview_url
      fileObj.is_converting = false
    } else {
      // wait then recheck
      setTimeout(() => pollPreviewStatus(documentId, fileObj, attempt + 1), 2000)
    }
  } catch (err) {
    setTimeout(() => pollPreviewStatus(documentId, fileObj, attempt + 1), 2000)
  }
}

// === Utility: get icon ===
const getFileIcon = (filename) => {
  const ext = filename.split('.').pop().toLowerCase()
  const icons = {
    pdf: '📕', doc: '📘', docx: '📘',
    xls: '📗', xlsx: '📗',
    ppt: '📙', pptx: '📙',
    txt: '📄', zip: '🗜️'
  }
  return icons[ext] || '📄'
}
</script>

<style scoped>
.upload-container {
  max-width: 980px;
  margin: 0 auto;
}
</style>
