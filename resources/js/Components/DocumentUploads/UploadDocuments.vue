<template>
  <div class="upload-container bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">📁 Tải tài liệu lên hệ thống</h2>

    <!-- Drag & Drop Zone -->
    <div class="border-2 border-dashed rounded-lg p-6 text-center transition-all duration-300"
      :class="isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50'"
      @dragover.prevent="isDragging = true" 
      @dragleave.prevent="isDragging = false" 
      @drop.prevent="handleDrop">
      
      <div class="text-6xl mb-3">{{ isDragging ? '📥' : '📂' }}</div>
      <p class="text-gray-600 mb-3">
        {{ isDragging ? 'Thả file tại đây' : 'Kéo thả file vào đây hoặc' }}
      </p>
      
      <div class="flex items-center justify-center gap-3">
        <button @click="triggerFileInput"
          class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
          Chọn file từ máy tính
        </button>
        <button @click="fetchCurrentFolderIndex" 
          class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition">
          Làm mới thư mục lưu
        </button>
      </div>

      <input ref="fileInput" type="file" multiple 
        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
        class="hidden" @change="handleFileSelect" />

      <p class="text-xs text-gray-500 mt-3">
        Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP (tối đa 50MB/file)
      </p>
      <p v-if="currentFolderIndex" class="text-xs text-gray-600 mt-2">
        Thư mục lưu hiện tại: <b>{{ currentFolderIndex }}</b>
      </p>
    </div>

    <!-- File List -->
    <div v-if="files.length" class="mt-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-700">
          📋 Danh sách file ({{ files.length }})
        </h3>
        <div class="flex items-center gap-3">
          <button v-if="pendingFiles > 0" @click="uploadAll" :disabled="uploading"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 transition">
            {{ uploading ? '⏳ Đang tải...' : `⬆️ Tải lên tất cả (${pendingFiles})` }}
          </button>
          <button @click="clearAll" 
            class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
            🗑️ Xóa tất cả
          </button>
        </div>
      </div>

      <!-- List of Files -->
      <div class="space-y-3">
        <div v-for="(file, index) in files" :key="file.uid"
          class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
          <div class="flex items-start justify-between gap-4">
            
            <!-- File Info Section -->
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <div class="text-2xl">{{ getFileIcon(file.name) }}</div>
                <div class="flex-1">
                  
                  <!-- Editable document name -->
                  <div class="flex gap-2 items-center">
                    <input v-model="file.title" 
                      class="border px-2 py-1 rounded w-64"
                      :placeholder="file.originalNameNoExt" />
                    <span class="text-sm text-gray-500">.{{ file.ext }}</span>
                    <span class="ml-3 text-xs text-gray-500">({{ file.size }})</span>
                  </div>

                  <!-- Type & Permission Selection -->
                  <div class="flex items-center gap-3 mt-2">
                    <select v-model="file.type_id" 
                      class="border rounded px-2 py-1 text-sm"
                      :class="{'border-red-500': file.validationErrors?.type_id}">
                      <option value="">-- Chọn loại (bắt buộc) --</option>
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
                      Thư mục: <b>{{ file.folderIndex || currentFolderIndex || '(chưa xác định)' }}</b>
                    </div>
                  </div>

                  <!-- Validation Errors -->
                  <div v-if="file.validationErrors" class="mt-2">
                    <p v-for="(errors, field) in file.validationErrors" :key="field" 
                      class="text-xs text-red-600">
                      • {{ errors[0] }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Progress Bar -->
              <div v-if="file.progress !== null" class="mb-2">
                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                  <div class="h-2.5 rounded-full transition-all duration-300"
                    :class="{
                      'bg-red-500': file.status === 'error',
                      'bg-yellow-500': file.status === 'done' && file.errorMessage,
                      'bg-blue-600': file.status === 'uploading',
                      'bg-green-600': file.status === 'done' && !file.errorMessage
                    }"
                    :style="{ width: file.progress + '%' }"></div>
                </div>
                <p class="text-xs text-gray-600 mt-1">{{ file.progress }}%</p>
              </div>

              <!-- Status Badge -->
              <div class="flex items-center gap-3 mt-2 flex-wrap">
                <span class="px-3 py-1 rounded-full text-xs font-medium" :class="{
                  'bg-gray-200 text-gray-700': file.status === 'pending',
                  'bg-blue-200 text-blue-700': file.status === 'uploading',
                  'bg-green-200 text-green-700': file.status === 'done' && !file.errorMessage && file.preview_ready,
                  'bg-green-100 text-green-800': file.status === 'done' && !file.errorMessage && !file.preview_ready,
                  'bg-yellow-200 text-yellow-800': file.status === 'done' && file.errorMessage,
                  'bg-indigo-200 text-indigo-700': file.is_converting,
                  'bg-red-200 text-red-700': file.status === 'error'
                }">
                  {{
                    file.status === 'pending' ? '⏸️ Chờ tải' :
                    file.status === 'uploading' ? '⏳ Đang tải...' :
                    file.is_converting ? '🔄 Đang tạo preview...' :
                    file.status === 'done' && file.preview_ready ? '✅ Hoàn tất (có preview)' :
                    file.status === 'done' && file.errorMessage ? '⚠️ Đã tải lên (preview lỗi)' :
                    file.status === 'done' ? '✅ Hoàn tất' :
                    '❌ Lỗi tải lên'
                  }}
                </span>

                <!-- Error/Warning Message -->
                <span v-if="file.errorMessage" 
                  class="text-xs ml-2"
                  :class="{
                    'text-yellow-600': file.status === 'done',
                    'text-red-600': file.status === 'error'
                  }">
                  {{ file.errorMessage }}
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col gap-2 items-end">
              <button v-if="file.status === 'pending'" 
                @click="uploadSingle(file, index)"
                :disabled="!file.type_id"
                :class="file.type_id ? 'bg-blue-500 hover:bg-blue-600' : 'bg-gray-400 cursor-not-allowed'"
                class="px-3 py-1 text-white text-sm rounded transition">
                ⬆️ Tải
              </button>

              <button v-else-if="file.status === 'done' && file.preview_url" 
                @click="downloadPreview(file)"
                class="px-3 py-1 bg-indigo-500 text-white text-sm rounded hover:bg-indigo-600 transition">
                📄 Xem preview
              </button>

              <button @click="removeFile(index)"
                class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600 transition">
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

// ===== REACTIVE STATES =====
const files = ref([])
const isDragging = ref(false)
const uploading = ref(false)
const fileInput = ref(null)
const types = ref([])
const currentFolderIndex = ref(null)

const permissionOptions = {
  view: '👁️ Chỉ xem',
  edit: '✏️ Sửa',
  download: '⬇️ Tải xuống',
  full: '🛠️ Toàn quyền'
}

// ===== COMPUTED =====
const pendingFiles = computed(() =>
  files.value.filter(f => f.status === 'pending').length
)

// ===== LIFECYCLE =====
onMounted(async () => {
  await fetchTypes()
  await fetchCurrentFolderIndex()
})

// ===== HELPERS =====
const triggerFileInput = () => fileInput.value.click()

const handleFileSelect = (event) => {
  prepareFiles(event.target.files)
  event.target.value = null
}

const handleDrop = (event) => {
  isDragging.value = false
  prepareFiles(event.dataTransfer.files)
}

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
      title: baseName,
      ext,
      name: f.name,
      size: `${sizeMB} MB`,
      progress: 0,
      status: 'pending',
      errorMessage: null,
      validationErrors: null,
      type_id: '',
      permission: 'view',
      folderIndex: null,
      is_converting: false,
      preview_ready: false,
      preview_url: null
    })
  }
}

// ===== API CALLS =====
const fetchTypes = async () => {
  try {
    const res = await axios.get('/api/types')
    types.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
  } catch (err) {
    console.error('Không lấy được types:', err)
    types.value = []
  }
}

const fetchCurrentFolderIndex = async () => {
  try {
    const res = await axios.get('/api/folders/current')
    currentFolderIndex.value = res.data?.folderIndex || null
  } catch (err) {
    console.warn('Không thể lấy folder hiện tại:', err)
    currentFolderIndex.value = null
  }
}

// ===== FILE ACTIONS =====
const removeFile = (index) => files.value.splice(index, 1)
const clearAll = () => files.value.splice(0, files.value.length)

const downloadPreview = (fileObj) => {
  if (!fileObj.preview_url) {
    alert('Preview chưa sẵn sàng hoặc không khả dụng.')
    return
  }
  window.open(fileObj.preview_url, '_blank')
}

// ===== UPLOAD SINGLE FILE =====
const uploadSingle = async (fileObj, index) => {
  if (fileObj.status === 'uploading' || fileObj.status === 'done') return

  // ✅ Validate type_id trước khi upload
  if (!fileObj.type_id) {
    fileObj.validationErrors = { type_id: ['Vui lòng chọn loại tài liệu'] }
    return
  }

  // Reset states
  fileObj.status = 'uploading'
  fileObj.progress = 0
  fileObj.errorMessage = null
  fileObj.validationErrors = null
  fileObj.is_converting = false
  fileObj.preview_ready = false

  // Get folder index
  if (!fileObj.folderIndex) {
    try {
      const resp = await axios.get('/api/folders/current')
      fileObj.folderIndex = resp.data.folderIndex || currentFolderIndex.value
    } catch {
      fileObj.folderIndex = currentFolderIndex.value
    }
  }

  // Build FormData
  const formData = new FormData()
  formData.append('file', fileObj.file)
  formData.append('title', fileObj.title || fileObj.originalNameNoExt)
  formData.append('type_id', fileObj.type_id)
  formData.append('permission', fileObj.permission)
  if (fileObj.folderIndex) {
    formData.append('folder_index', fileObj.folderIndex)
  }

  try {
    const response = await axios.post('/api/documents/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: e => {
        if (e.total) {
          fileObj.progress = Math.round((e.loaded * 100) / e.total)
        }
      },
    })

    const data = response.data || {}

    // ✅ CHECK: Backend có trả success = true không?
    if (data.success === true) {
      fileObj.status = 'done'
      fileObj.progress = 100
      fileObj.preview_ready = !!data.preview_ready
      fileObj.preview_url = data.preview_url || null
      fileObj.is_converting = !!data.conversion_started

      // ⚠️ Nếu có preview_error → hiển thị warning
      if (data.preview_error) {
        fileObj.errorMessage = data.preview_error
      }

      // 🔄 Poll preview nếu đang convert async
      if (fileObj.is_converting && !fileObj.preview_ready) {
        const documentId = data.document?.document_id || data.document_id
        if (documentId) {
          pollPreviewStatus(documentId, fileObj)
        }
      }

    } else {
      // ❌ Backend trả success = false
      fileObj.status = 'error'
      fileObj.progress = 0
      fileObj.errorMessage = data.message || 'Upload thất bại'
    }

  } catch (error) {
    // ❌ Axios error
    fileObj.status = 'error'
    fileObj.progress = 0

    // Handle Laravel validation errors (422)
    if (error.response?.status === 422 && error.response?.data?.errors) {
      fileObj.validationErrors = error.response.data.errors
      const firstError = Object.values(error.response.data.errors)[0]
      fileObj.errorMessage = Array.isArray(firstError) ? firstError[0] : firstError
    }
    // Handle other errors
    else if (error.response?.data?.message) {
      fileObj.errorMessage = error.response.data.message
    }
    else if (error.response?.data?.error) {
      fileObj.errorMessage = error.response.data.error
    }
    else if (error.code === 'ECONNABORTED') {
      fileObj.errorMessage = '⏱️ Quá thời gian kết nối'
    }
    else {
      fileObj.errorMessage = error.message || 'Lỗi không xác định'
    }

    console.error('Upload error:', error)
  }
}

// ===== UPLOAD ALL =====
const uploadAll = async () => {
  if (uploading.value) return
  uploading.value = true

  for (let i = 0; i < files.value.length; i++) {
    const f = files.value[i]
    if (f.status === 'pending') {
      await uploadSingle(f, i)
    }
  }

  uploading.value = false
}

// ===== POLL PREVIEW STATUS =====
const pollPreviewStatus = async (documentId, fileObj, attempt = 0) => {
  if (attempt > 20) {
    fileObj.is_converting = false
    fileObj.errorMessage = 'Không tạo được preview sau 40 giây.'
    return
  }

  try {
    const res = await axios.get(`/api/documents/${documentId}/preview-status`)
    
    if (res.data.preview_ready) {
      fileObj.preview_ready = true
      fileObj.preview_url = res.data.preview_url
      fileObj.is_converting = false
      fileObj.errorMessage = null // Clear error nếu có
    } else {
      setTimeout(() => pollPreviewStatus(documentId, fileObj, attempt + 1), 2000)
    }
  } catch (err) {
    console.warn(`Poll attempt ${attempt + 1} failed:`, err)
    setTimeout(() => pollPreviewStatus(documentId, fileObj, attempt + 1), 2000)
  }
}

// ===== UTILITY =====
const getFileIcon = (filename) => {
  const ext = filename.split('.').pop().toLowerCase()
  const icons = {
    pdf: '📕', 
    doc: '📘', 
    docx: '📘',
    xls: '📗', 
    xlsx: '📗',
    ppt: '📙', 
    pptx: '📙',
    txt: '📄', 
    zip: '🗜️'
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