<template>
  <div class="upload-container bg-white rounded-3 shadow-sm p-4">
    <!-- Header -->
    <div class="mb-4 pb-3 border-bottom">
      <h2 class="h4 mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-cloud-upload text-primary fs-3"></i>
        <span class="fw-bold">Tải tài liệu lên hệ thống</span>
      </h2>
    </div>

    <!-- Drag & Drop Zone -->
    <div
      class="upload-zone border border-2 rounded-3 p-4 text-center mb-4 position-relative"
      :class="isDragging ? 'border-primary bg-primary bg-opacity-10' : 'border-dashed bg-light'"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleDrop"
      style="transition: all 0.3s ease; border-style: dashed;"
    >
      <div class="mb-3">
        <i 
          :class="isDragging ? 'bi bi-inbox-fill text-primary' : 'bi bi-folder2-open text-secondary'"
          style="font-size: 4rem;"
        ></i>
      </div>

      <p class="text-muted mb-3">
        {{ isDragging ? 'Thả file vào đây để tải lên' : 'Kéo và thả file vào đây hoặc' }}
      </p>

      <div class="d-flex justify-content-center gap-2 flex-wrap">
        <button
          @click="triggerFileInput"
          class="btn btn-primary d-flex align-items-center gap-2"
        >
          <i class="bi bi-folder-plus"></i>
          <span>Chọn file</span>
        </button>

        <button
          @click="fetchCurrentFolderIndex"
          class="btn btn-outline-secondary d-flex align-items-center gap-2"
        >
          <i class="bi bi-arrow-clockwise"></i>
          <span>Làm mới</span>
        </button>
      </div>

      <input
        ref="fileInput"
        type="file"
        multiple
        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
        class="d-none"
        @change="handleFileSelect"
      />

      <div class="mt-3">
        <small class="text-muted d-block">
          <i class="bi bi-info-circle me-1"></i>
          Hỗ trợ: PDF, Word, Excel, PowerPoint, TXT, ZIP (tối đa 50MB/file)
        </small>
        <small v-if="currentFolderIndex" class="text-secondary d-block mt-1">
          <i class="bi bi-folder me-1"></i>
          Thư mục: <strong>{{ currentFolderIndex }}</strong>
        </small>
      </div>
    </div>

    <!-- File List -->
    <div v-if="files.length">
      <!-- List Header -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-files text-primary"></i>
          <span>Danh sách file</span>
          <span class="badge bg-secondary">{{ files.length }}</span>
        </h5>

        <div class="d-flex gap-2">
          <button
            v-if="pendingFiles > 0"
            @click="uploadAll"
            :disabled="uploading"
            class="btn btn-success btn-sm d-flex align-items-center gap-1"
          >
            <i :class="uploading ? 'bi bi-hourglass-split' : 'bi bi-upload'"></i>
            <span>{{ uploading ? 'Đang tải...' : `Tải lên tất cả (${pendingFiles})` }}</span>
          </button>

          <button
            @click="clearAll"
            class="btn btn-danger btn-sm d-flex align-items-center gap-1"
          >
            <i class="bi bi-trash"></i>
            <span>Xóa tất cả</span>
          </button>
        </div>
      </div>

      <!-- Files -->
      <div class="file-list">
        <div
          v-for="(file, index) in files"
          :key="file.uid"
          class="file-item card mb-3 shadow-sm hover-shadow"
        >
          <div class="card-body">
            <div class="row align-items-start">
              
              <!-- File Icon & Info -->
              <div class="col-lg-8">
                <div class="d-flex gap-3 mb-3">
                  
                  <!-- Icon -->
                  <div class="file-icon-wrapper">
                    <i 
                      :class="getFileIconClass(file.name)" 
                      :style="`color: ${getFileColor(file.name)}`"
                      class="fs-1"
                    ></i>
                  </div>

                  <!-- Info -->
                  <div class="flex-grow-1">
                    <!-- File Name -->
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <input
                        v-model="file.title"
                        class="form-control form-control-sm"
                        :placeholder="file.originalNameNoExt"
                        style="max-width: 400px;"
                      />
                      <span class="text-muted small">.{{ file.ext }}</span>
                      <small class="text-muted">
                        <i class="bi bi-hdd"></i>
                        {{ file.size }}
                      </small>
                    </div>

                    <!-- Selects -->
                    <div class="row g-2 mb-2">
                      <div class="col-md-4">
                        <select
                          v-model="file.type_id"
                          class="form-select form-select-sm"
                          :class="{'is-invalid': file.validationErrors?.type_id}"
                        >
                          <option value="">-- Loại tài liệu --</option>
                          <option v-for="t in types" :key="t.type_id" :value="t.type_id">
                            {{ t.name }}
                          </option>
                        </select>
                      </div>

                      <div class="col-md-3">
                        <select v-model="file.subject_id" class="form-select form-select-sm">
                          <option value="">-- Môn học --</option>
                          <option v-for="s in subjects" :key="s.subject_id" :value="s.subject_id">
                            {{ s.name }}
                          </option>
                        </select>
                      </div>

                      <div class="col-md-3">
                        <select v-model="file.permission" class="form-select form-select-sm">
                          <option v-for="(label, key) in permissionOptions" :key="key" :value="key">
                            {{ label }}
                          </option>
                        </select>
                      </div>

                      <div class="col-md-2 d-flex align-items-center">
                        <small class="text-muted">
                          <i class="bi bi-folder2"></i>
                          {{ file.folderIndex || currentFolderIndex || 'N/A' }}
                        </small>
                      </div>
                    </div>

                    <!-- Validation Errors -->
                    <div v-if="file.validationErrors" class="alert alert-danger alert-sm py-1 px-2 mb-2">
                      <small v-for="(errors, field) in file.validationErrors" :key="field">
                        <i class="bi bi-exclamation-circle"></i>
                        {{ errors[0] }}
                      </small>
                    </div>

                    <!-- Progress Bar -->
                    <div v-if="file.progress !== null" class="mb-2">
                      <div class="progress" style="height: 8px;">
                        <div
                          class="progress-bar"
                          :class="{
                            'bg-danger': file.status === 'error',
                            'bg-warning': file.status === 'done' && file.errorMessage,
                            'bg-primary': file.status === 'uploading',
                            'bg-success': file.status === 'done' && !file.errorMessage
                          }"
                          :style="{ width: file.progress + '%' }"
                        ></div>
                      </div>
                      <small class="text-muted">{{ file.progress }}%</small>
                    </div>

                    <!-- Status Badge -->
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <span
                        class="badge d-flex align-items-center gap-1"
                        :class="{
                          'bg-secondary': file.status === 'pending',
                          'bg-primary': file.status === 'uploading',
                          'bg-success': file.status === 'done' && !file.errorMessage && file.preview_ready,
                          'bg-warning': file.status === 'done' && file.errorMessage,
                          'bg-danger': file.status === 'error',
                          'bg-info': file.is_converting
                        }"
                      >
                        <i
                          :class="{
                            'bi bi-pause-circle': file.status === 'pending',
                            'bi bi-hourglass-split': file.status === 'uploading',
                            'bi bi-arrow-repeat spin': file.is_converting,
                            'bi bi-check-circle-fill': file.status === 'done' && file.preview_ready,
                            'bi bi-check-circle': file.status === 'done' && !file.errorMessage && !file.preview_ready,
                            'bi bi-exclamation-triangle-fill': file.status === 'done' && file.errorMessage,
                            'bi bi-x-circle-fill': file.status === 'error'
                          }"
                        ></i>
                        <span>{{
                          file.status === 'pending' ? 'Chờ tải' :
                          file.status === 'uploading' ? 'Đang tải...' :
                          file.is_converting ? 'Tạo preview...' :
                          file.status === 'done' && file.preview_ready ? 'Hoàn tất' :
                          file.status === 'done' && file.errorMessage ? 'Cảnh báo' :
                          file.status === 'done' ? 'Hoàn tất' :
                          'Lỗi'
                        }}</span>
                      </span>

                      <small v-if="file.errorMessage" class="text-danger">
                        <i class="bi bi-info-circle"></i>
                        {{ file.errorMessage }}
                      </small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="col-lg-4 text-lg-end">
                <div class="d-flex flex-column gap-2 d-lg-inline-flex">
                  <button
                    v-if="file.status === 'pending'"
                    @click="uploadSingle(file, index)"
                    :disabled="!file.type_id"
                    class="btn btn-sm d-flex align-items-center gap-1 justify-content-center"
                    :class="file.type_id ? 'btn-primary' : 'btn-secondary disabled'"
                  >
                    <i class="bi bi-upload"></i>
                    <span>Tải lên</span>
                  </button>

                  <button
                    v-else-if="file.status === 'done' && file.preview_url"
                    @click="downloadPreview(file)"
                    class="btn btn-info btn-sm d-flex align-items-center gap-1 justify-content-center"
                  >
                    <i class="bi bi-eye"></i>
                    <span>Xem preview</span>
                  </button>

                  <button
                    @click="removeFile(index)"
                    class="btn btn-danger btn-sm d-flex align-items-center gap-1 justify-content-center"
                  >
                    <i class="bi bi-trash"></i>
                    <span>Xóa</span>
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-5">
      <i class="bi bi-inbox text-muted" style="font-size: 5rem;"></i>
      <p class="text-muted mt-3 mb-0">Chưa có file nào được chọn</p>
      <small class="text-muted">Kéo thả hoặc nhấn nút "Chọn file" để bắt đầu</small>
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
const subjects = ref([])
const currentFolderIndex = ref(null)

const permissionOptions = {
  view: 'Chỉ xem',
  edit: 'Chỉnh sửa',
  download: 'Tải xuống',
  full: 'Toàn quyền'
}

// ===== COMPUTED =====
const pendingFiles = computed(() =>
  files.value.filter(f => f.status === 'pending').length
)

// ===== LIFECYCLE =====
onMounted(async () => {
  await fetchTypes()
  await fetchSubjects()
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
      alert(`File "${f.name}" vượt quá 50MB!`)
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
      subject_id: '',
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

const fetchSubjects = async () => {
  try {
    const res = await axios.get('/api/subjects')
    subjects.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
  } catch (err) {
    console.error('Không lấy được subjects:', err)
    subjects.value = []
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

  if (!fileObj.type_id) {
    fileObj.validationErrors = { type_id: ['Vui lòng chọn loại tài liệu'] }
    return
  }

  fileObj.status = 'uploading'
  fileObj.progress = 0
  fileObj.errorMessage = null
  fileObj.validationErrors = null
  fileObj.is_converting = false
  fileObj.preview_ready = false

  if (!fileObj.folderIndex) {
    try {
      const resp = await axios.get('/api/folders/current')
      fileObj.folderIndex = resp.data.folderIndex || currentFolderIndex.value
    } catch {
      fileObj.folderIndex = currentFolderIndex.value
    }
  }

  const formData = new FormData()
  formData.append('file', fileObj.file)
  formData.append('title', fileObj.title || fileObj.originalNameNoExt)
  formData.append('type_id', fileObj.type_id)
  if (fileObj.subject_id) {
    formData.append('subject_id', fileObj.subject_id)
  }
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

    if (data.success === true) {
      fileObj.status = 'done'
      fileObj.progress = 100
      fileObj.preview_ready = !!data.preview_ready
      fileObj.preview_url = data.preview_url || null
      fileObj.is_converting = !!data.conversion_started

      if (data.preview_error) {
        fileObj.errorMessage = data.preview_error
      }

      if (fileObj.is_converting && !fileObj.preview_ready) {
        const documentId = data.document?.document_id || data.document_id
        if (documentId) {
          pollPreviewStatus(documentId, fileObj)
        }
      }

    } else {
      fileObj.status = 'error'
      fileObj.progress = 0
      fileObj.errorMessage = data.message || 'Upload thất bại'
    }

  } catch (error) {
    fileObj.status = 'error'
    fileObj.progress = 0

    if (error.response?.status === 422 && error.response?.data?.errors) {
      fileObj.validationErrors = error.response.data.errors
      const firstError = Object.values(error.response.data.errors)[0]
      fileObj.errorMessage = Array.isArray(firstError) ? firstError[0] : firstError
    }
    else if (error.response?.data?.message) {
      fileObj.errorMessage = error.response.data.message
    }
    else if (error.response?.data?.error) {
      fileObj.errorMessage = error.response.data.error
    }
    else if (error.code === 'ECONNABORTED') {
      fileObj.errorMessage = 'Quá thời gian kết nối'
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
      fileObj.errorMessage = null
    } else {
      setTimeout(() => pollPreviewStatus(documentId, fileObj, attempt + 1), 2000)
    }
  } catch (err) {
    console.warn(`Poll attempt ${attempt + 1} failed:`, err)
    setTimeout(() => pollPreviewStatus(documentId, fileObj, attempt + 1), 2000)
  }
}

// ===== UTILITY =====
const getFileIconClass = (filename) => {
  const ext = filename.split('.').pop().toLowerCase()
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

const getFileColor = (filename) => {
  const ext = filename.split('.').pop().toLowerCase()
  const colors = {
    pdf: '#dc3545',     // Red
    doc: '#2b579a',     // Blue (Word)
    docx: '#2b579a',
    xls: '#217346',     // Green (Excel)
    xlsx: '#217346',
    ppt: '#d24726',     // Orange (PowerPoint)
    pptx: '#d24726',
    txt: '#6c757d',     // Gray
    zip: '#ffc107'      // Yellow
  }
  return colors[ext] || '#6c757d'
}
</script>

<style scoped>
.upload-container {
  max-width: 1200px;
  margin: 0 auto;
}

.upload-zone {
  cursor: pointer;
  min-height: 200px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.file-item {
  transition: all 0.2s ease;
}

.file-item:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.hover-shadow:hover {
  transform: translateY(-2px);
}

.border-dashed {
  border-style: dashed !important;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.spin {
  animation: spin 1s linear infinite;
}

.alert-sm {
  font-size: 0.875rem;
}

.file-icon-wrapper {
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>