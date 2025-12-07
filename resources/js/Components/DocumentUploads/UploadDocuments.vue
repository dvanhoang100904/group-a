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
    <div class="upload-zone border border-2 rounded-3 p-4 text-center mb-4 position-relative"
      :class="isDragging ? 'border-primary bg-primary bg-opacity-10' : 'border-dashed bg-light'"
      @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop"
      style="transition: all 0.3s ease; border-style: dashed; min-height: 220px">
      <div class="mb-3">
        <i :class="isDragging ? 'bi bi-inbox-fill text-primary' : 'bi bi-folder2-open text-secondary'"
          style="font-size: 4rem"></i>
      </div>

      <p class="text-muted mb-3">
        {{ isDragging ? 'Thả file vào đây để tải lên' : 'Kéo và thả file vào đây hoặc' }}
      </p>

      <div class="d-flex justify-content-center gap-2 flex-wrap">
        <button @click="triggerFileInput" class="btn btn-primary d-flex align-items-center gap-2">
          <i class="bi bi-folder-plus"></i>
          <span>Chọn file</span>
        </button>
        <button @click="loadFolders" class="btn btn-outline-secondary d-flex align-items-center gap-2">
          <i class="bi bi-arrow-clockwise"></i>
          <span>Làm mới</span>
        </button>
      </div>

      <input ref="fileInput" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
        class="d-none" @change="handleFileSelect" />

      <div class="mt-3">
        <small class="text-muted d-block">
          <i class="bi bi-info-circle me-1"></i>
          Hỗ trợ: PDF, Word, Excel, PowerPoint, TXT, ZIP (tối đa 50MB/file)
        </small>
      </div>
    </div>

    <!-- Default settings -->
    <div class="mb-4 p-4 bg-light rounded-3 border">
      <div class="d-flex align-items-center mb-3">
        <i class="bi bi-gear-fill text-primary me-2 fs-5"></i>
        <h6 class="mb-0 fw-bold">Cài đặt mặc định cho tất cả file</h6>
      </div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Loại tài liệu</label>
          <select v-model="defaultSettings.type_id" class="form-select">
            <option value="">-- Không áp dụng --</option>
            <option v-for="t in types" :key="t.type_id" :value="t.type_id">{{ t.name }}</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label small fw-semibold">Môn học</label>
          <select v-model="defaultSettings.subject_id" class="form-select">
            <option value="">-- Không áp dụng --</option>
            <option v-for="s in subjects" :key="s.subject_id" :value="s.subject_id">{{ s.name }}</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label small fw-semibold">Quyền truy cập</label>
          <select v-model="defaultSettings.permission" class="form-select">
            <option v-for="(label, key) in permissionOptions" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>

        <div class="col-md-2 d-flex align-items-end">
          <button @click="applyDefaultSettings" class="btn btn-primary w-100">
            <i class="bi bi-arrow-down-circle me-1"></i> Áp dụng
          </button>
        </div>
      </div>
    </div>

    <!-- File list -->
    <div v-if="files.length">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-files text-primary"></i>
          Danh sách file
          <span class="badge bg-secondary">{{ files.length }}</span>
        </h5>

        <div class="d-flex gap-2">
          <button v-if="pendingFiles > 0" @click="uploadAll" :disabled="uploading"
            class="btn btn-success btn-sm d-flex align-items-center gap-1">
            <i :class="uploading ? 'bi bi-hourglass-split' : 'bi bi-upload'"></i>
            {{ uploading ? 'Đang tải...' : `Tải lên tất cả (${pendingFiles})` }}
          </button>
          <button @click="clearAll" class="btn btn-danger btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-trash"></i> Xóa tất cả
          </button>
        </div>
      </div>

      <div class="file-list">
        <div v-for="(file, index) in files" :key="file.uid"
          class="file-item card mb-3 border-start border-4 hover-shadow"
          :class="`border-${getFileColorBootstrap(file.name)}`">
          <div class="card-body p-3">
            <div class="row align-items-center">
              <!-- icon + name -->
              <div class="col-lg-5">
                <div class="d-flex gap-3 align-items-center">
                  <div class="file-icon-wrapper d-flex align-items-center justify-content-center rounded-3 p-2"
                    :style="`background-color: ${getFileColor(file.name)}15`">
                    <i :class="getFileIconClass(file.name)" :style="`color: ${getFileColor(file.name)}`"
                      class="fs-2"></i>
                  </div>

                  <div class="flex-grow-1">

                    <!-- Tên file -->
                    <input v-model="file.title" class="form-control form-control-sm fw-semibold mb-1"
                      :placeholder="file.originalNameNoExt" />

                    <!-- Mô tả file -->
                    <textarea v-model="file.description" class="form-control form-control-sm mb-2"
                      placeholder="Nhập mô tả cho file..." rows="2"></textarea>

                    <!-- Ext + Size -->
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-secondary">.{{ file.ext }}</span>
                      <small class="text-muted">
                        <i class="bi bi-hdd-fill"></i> {{ file.size }}
                      </small>
                    </div>
                  </div>

                </div>
              </div>

              <!-- settings -->
              <div class="col-lg-5">
                <div class="row g-2">
                  <div class="col-12">
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-light border-0"><i
                          class="bi bi-file-earmark-text text-primary"></i></span>
                      <select v-model="file.type_id" class="form-select"
                        :class="{ 'is-invalid': file.validationErrors?.type_id }">
                        <option value="">-- Loại tài liệu --</option>
                        <option v-for="t in types" :key="t.type_id" :value="t.type_id">{{ t.name }}</option>
                      </select>
                    </div>
                  </div>

                  <div class="col-6">
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-light border-0"><i class="bi bi-book text-info"></i></span>
                      <select v-model="file.subject_id" class="form-select">
                        <option value="">-- Môn học --</option>
                        <option v-for="s in subjects" :key="s.subject_id" :value="s.subject_id">{{ s.name }}</option>
                      </select>
                    </div>
                  </div>

                  <div class="col-6">
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-light border-0"><i
                          class="bi bi-shield-lock text-warning"></i></span>
                      <select v-model="file.permission" class="form-select">
                        <option v-for="(label, key) in permissionOptions" :key="key" :value="key">{{ label }}</option>
                      </select>
                    </div>
                  </div>

                  <!-- folder button -->
                  <div class="col-12">
                    <div class="folder-dropdown-wrapper" @click.stop>
                      <button :id="`folder-btn-${index}`" @click.stop="toggleFileFolderTree(index)"
                        class="btn btn-sm btn-outline-secondary w-100 text-start d-flex align-items-center justify-content-between">
                        <span class="d-flex align-items-center gap-2 text-truncate">
                          <i class="bi bi-folder2-open text-warning"></i>
                          <small>{{ file.folder ? file.folder.name : 'Thư mục gốc' }}</small>
                        </span>
                        <i :class="file.showFolderTree ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                      </button>
                    </div>

                    <!-- Teleport popup: render to body so it never gets cut or covered -->
                    <Teleport to="body">
                      <div v-if="file.showFolderTree" class="folder-popup-global" :style="file.dropdownPosition"
                        @click.stop>
                        <div class="p-2">
                          <div class="d-flex align-items-center px-2 py-1 hover-bg-light cursor-pointer rounded"
                            :class="{ 'bg-primary bg-opacity-10': !file.folder }"
                            @click="selectFileFolder(index, null)">
                            <i class="bi bi-hdd-stack text-primary me-2"></i>
                            <small class="fw-semibold">Thư mục gốc</small>
                            <i v-if="!file.folder" class="bi bi-check2 ms-auto text-success"></i>
                          </div>

                          <FoldersTreee v-if="folderTree.length" :folders="folderTree" :depth="0"
                            :selected-id="file.folder?.id" @select="selectFileFolder(index, $event)" />
                        </div>
                      </div>
                    </Teleport>
                  </div>
                </div>
              </div>

              <!-- actions -->
              <div class="col-lg-2 text-end">
                <div class="d-flex flex-column gap-1">
                  <button v-if="file.status === 'pending'" @click="uploadSingle(file)" :disabled="!file.type_id"
                    class="btn btn-sm w-100" :class="file.type_id ? 'btn-success' : 'btn-secondary opacity-50'">
                    <i class="bi bi-cloud-upload-fill"></i>
                  </button>

                  <button v-else-if="file.status === 'done' && file.preview_url" @click="downloadPreview(file)"
                    class="btn btn-info btn-sm w-100">
                    <i class="bi bi-eye-fill"></i>
                  </button>

                  <button @click="removeFile(index)" class="btn btn-outline-danger btn-sm w-100">
                    <i class="bi bi-trash3-fill"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- progress -->
            <div v-if="file.progress !== null" class="mt-2">
              <div class="progress" style="height: 6px">
                <div class="progress-bar" :class="{
                  'bg-danger': file.status === 'error',
                  'bg-warning': file.status === 'done' && file.errorMessage,
                  'bg-primary': file.status === 'uploading',
                  'bg-success': file.status === 'done' && !file.errorMessage
                }" :style="{ width: file.progress + '%' }"></div>
              </div>
            </div>

            <!-- status + errors -->
            <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
              <span class="badge d-flex align-items-center gap-1" :class="{
                'bg-secondary': file.status === 'pending',
                'bg-primary': file.status === 'uploading',
                'bg-success': file.status === 'done' && !file.errorMessage && file.preview_ready,
                'bg-warning': file.status === 'done' && file.errorMessage,
                'bg-danger': file.status === 'error',
                'bg-info': file.is_converting
              }">
                <i :class="{
                  'bi bi-pause-circle-fill': file.status === 'pending',
                  'bi bi-arrow-repeat spin': file.status === 'uploading' || file.is_converting,
                  'bi bi-check-circle-fill': file.status === 'done' && file.preview_ready,
                  'bi bi-exclamation-triangle-fill': file.status === 'done' && file.errorMessage,
                  'bi bi-x-circle-fill': file.status === 'error'
                }"></i>
                <span>{{ statusText(file) }}</span>
              </span>

              <small v-if="file.errorMessage" class="text-danger">
                <i class="bi bi-exclamation-diamond-fill"></i>
                {{ file.errorMessage }}
              </small>

              <small v-if="file.validationErrors" class="text-danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                {{ Object.values(file.validationErrors)[0][0] }}
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- empty -->
    <div v-else class="text-center py-5">
      <i class="bi bi-inbox text-muted" style="font-size: 5rem"></i>
      <p class="text-muted mt-3 mb-0">Chưa có file nào được chọn</p>
      <small class="text-muted">Kéo thả hoặc nhấn nút "Chọn file" để bắt đầu</small>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from 'axios'
import { v4 as uuidv4 } from 'uuid'
import FoldersTreee from '@/Components/DocumentUploads/FoldersTreee.vue'

// State
const files = ref([])
const isDragging = ref(false)
const uploading = ref(false)
const fileInput = ref(null)
const types = ref([])
const subjects = ref([])

// Folder tree
const folderTree = ref([])
const selectedFolder = ref(null)

// Default settings
const defaultSettings = ref({
  type_id: '',
  subject_id: '',
  permission: 'view'
})

// Permission options
const permissionOptions = {
  view: 'Chỉ xem',
  edit: 'Chỉnh sửa',
  download: 'Tải xuống',
  full: 'Toàn quyền'
}

// Computed
const pendingFiles = computed(() => files.value.filter(f => f.status === 'pending').length)

// Lifecycle
onMounted(async () => {
  await Promise.all([fetchTypes(), fetchSubjects(), loadFolders()])

  // click outside closes dropdowns
  document.addEventListener('click', handleDocumentClick)
  // esc closes dropdowns
  window.addEventListener('keydown', handleKeydown)
  // update position on scroll/resize
  window.addEventListener('scroll', handleWindowUpdate, true)
  window.addEventListener('resize', handleWindowUpdate)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
  window.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('scroll', handleWindowUpdate, true)
  window.removeEventListener('resize', handleWindowUpdate)
})

// API calls
const fetchTypes = async () => {
  try {
    const r = await axios.get('/api/types')
    types.value = r.data?.data || r.data || []
  } catch (err) {
    console.error('Lỗi load types:', err)
    types.value = []
  }
}

const fetchSubjects = async () => {
  try {
    const r = await axios.get('/api/subjects')
    subjects.value = r.data?.data || r.data || []
  } catch (err) {
    console.error('Lỗi load subjects:', err)
    subjects.value = []
  }
}

const loadFolders = async () => {
  try {
    const res = await axios.get('/api/folders/tree')
    if (res.data && (res.data.success || res.data.data)) {
      folderTree.value = res.data.data || res.data
    }
  } catch (err) {
    console.error('Lỗi load folder tree:', err)
    folderTree.value = []
  }
}

// File handling
const triggerFileInput = () => fileInput.value && fileInput.value.click()

const handleFileSelect = e => {
  prepareFiles(e.target.files)
  e.target.value = null
}

const handleDrop = e => {
  isDragging.value = false
  prepareFiles(e.dataTransfer.files)
}

const prepareFiles = fileList => {
  for (const f of fileList) {
    if (f.size > 50 * 1024 * 1024) {
      alert(`File "${f.name}" vượt quá 50MB!`)
      continue
    }

    const parts = f.name.split('.')
    const ext = parts.length > 1 ? parts.pop().toLowerCase() : ''
    const baseName = parts.join('.')

    // ensure dropdownPosition exist on each file object
    files.value.push({
      uid: uuidv4(),
      file: f,
      originalName: f.name,
      originalNameNoExt: baseName,
      title: baseName,
      ext,
      name: f.name,
      size: `${(f.size / 1024 / 1024).toFixed(2)} MB`,
      progress: 0,
      status: 'pending',
      errorMessage: null,
      validationErrors: null,
      type_id: defaultSettings.value.type_id,
      subject_id: defaultSettings.value.subject_id,
      permission: defaultSettings.value.permission,
      folder: null,
      showFolderTree: false,
      dropdownPosition: null,
      is_converting: false,
      preview_ready: false,
      preview_url: null
    })
  }
}

// Apply default settings to pending files
const applyDefaultSettings = () => {
  files.value.forEach(file => {
    if (file.status === 'pending') {
      if (defaultSettings.value.type_id) file.type_id = defaultSettings.value.type_id
      if (defaultSettings.value.subject_id) file.subject_id = defaultSettings.value.subject_id
      file.permission = defaultSettings.value.permission
    }
  })
}

// Dropdown / Teleport helpers
const closeAllDropdowns = () => {
  files.value.forEach(f => (f.showFolderTree = false))
}

const handleDocumentClick = (e) => {
  // if click is outside any folder button or teleport popup -> close
  // rely on stopPropagation on internal elements; simple approach: close all
  closeAllDropdowns()
}

const handleKeydown = (e) => {
  if (e.key === 'Escape' || e.key === 'Esc') {
    closeAllDropdowns()
  }
}

const handleWindowUpdate = () => {
  // recalc for open dropdowns
  files.value.forEach((f, idx) => {
    if (f.showFolderTree) updateDropdownPosition(idx)
  })
}

const toggleFileFolderTree = async (index) => {
  // prevent index OOB
  if (index < 0 || index >= files.value.length) return

  // close others
  files.value.forEach((f, i) => {
    if (i !== index) f.showFolderTree = false
  })

  // toggle current
  files.value[index].showFolderTree = !files.value[index].showFolderTree

  // compute position if opened
  if (files.value[index].showFolderTree) {
    await nextTick()
    updateDropdownPosition(index)
  }
}

const updateDropdownPosition = (index) => {
  const btn = document.getElementById(`folder-btn-${index}`)
  if (!btn) return

  const rect = btn.getBoundingClientRect()
  // default width same as button; allow small offset
  const gap = 6
  files.value[index].dropdownPosition = {
    position: 'absolute',
    top: `${rect.bottom + window.scrollY + gap}px`,
    left: `${rect.left + window.scrollX}px`,
    width: `${rect.width}px`,
    zIndex: 9999999
  }
}

const selectFileFolder = (index, folder) => {
  if (index < 0 || index >= files.value.length) return
  files.value[index].folder = folder
  files.value[index].showFolderTree = false
  files.value[index].dropdownPosition = null
}

// remove/clear/download
const removeFile = i => files.value.splice(i, 1)
const clearAll = () => (files.value = [])
const downloadPreview = f => f.preview_url && window.open(f.preview_url, '_blank')

// Upload functions
const uploadSingle = async (fileObj) => {
  if (!fileObj.type_id) {
    fileObj.validationErrors = { type_id: ['Vui lòng chọn loại tài liệu'] }
    return
  }

  fileObj.status = 'uploading'
  fileObj.progress = 0
  fileObj.errorMessage = null
  fileObj.validationErrors = null

  const form = new FormData()
  form.append('file', fileObj.file)
  form.append('description', fileObj.description || '')
  form.append('title', fileObj.title || fileObj.originalNameNoExt)
  form.append('type_id', fileObj.type_id)

  if (fileObj.subject_id) form.append('subject_id', fileObj.subject_id)
  form.append('permission', fileObj.permission)

  if (fileObj.folder?.id) form.append('folder_id', fileObj.folder.id)


  try {
    const res = await axios.post('/api/documents/upload', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: e => e.total && (fileObj.progress = Math.round((e.loaded * 100) / e.total))
    })

    if (res.data.success) {
      fileObj.status = 'done'
      fileObj.progress = 100
      fileObj.preview_ready = !!res.data.preview_ready
      fileObj.preview_url = res.data.preview_url || null
      fileObj.is_converting = !!res.data.conversion_started

      if (res.data.preview_error) {
        fileObj.errorMessage = res.data.preview_error
      }

      if (fileObj.is_converting && !fileObj.preview_ready && res.data.document_id) {
        pollPreviewStatus(res.data.document_id, fileObj)
      }
    } else {
      fileObj.status = 'error'
      fileObj.errorMessage = res.data?.message || 'Lỗi upload'
    }
  } catch (err) {
    fileObj.status = 'error'
    fileObj.errorMessage = err.response?.data?.message || err.message || 'Lỗi upload'
  }
}

const uploadAll = async () => {
  uploading.value = true
  for (const f of files.value.filter(f => f.status === 'pending')) {
    await uploadSingle(f)
  }
  uploading.value = false
}

const pollPreviewStatus = (id, fileObj, attempt = 0) => {
  if (attempt > 20) {
    fileObj.is_converting = false
    fileObj.errorMessage = 'Timeout preview'
    return
  }

  axios
    .get(`/api/documents/${id}/preview-status`)
    .then(r => {
      if (r.data.preview_ready) {
        fileObj.preview_ready = true
        fileObj.preview_url = r.data.preview_url
        fileObj.is_converting = false
      } else {
        setTimeout(() => pollPreviewStatus(id, fileObj, attempt + 1), 2000)
      }
    })
    .catch(() => setTimeout(() => pollPreviewStatus(id, fileObj, attempt + 1), 2000))
}

// helpers (status & icons)
const statusText = f => {
  if (!f) return ''
  if (f.status === 'pending') return 'Chờ tải'
  if (f.status === 'uploading') return 'Đang tải...'
  if (f.is_converting) return 'Tạo preview...'
  if (f.status === 'done' && f.preview_ready) return 'Hoàn tất'
  if (f.status === 'done' && f.errorMessage) return 'Cảnh báo'
  if (f.status === 'done') return 'Hoàn tất'
  return 'Lỗi'
}

const getFileIconClass = n => {
  const e = (n || '').split('.').pop().toLowerCase()
  const m = {
    pdf: 'bi-file-earmark-pdf-fill',
    doc: 'bi-file-earmark-word-fill',
    docx: 'bi-file-earmark-word-fill',
    xls: 'bi-file-earmark-excel-fill',
    xlsx: 'bi-file-earmark-excel-fill',
    ppt: 'bi-file-earmark-ppt-fill',
    pptx: 'bi-file-earmark-ppt-fill',
    txt: 'bi-file-earmark-text-fill',
    zip: 'bi-file-earmark-zip-fill'
  }
  return `bi ${m[e] || 'bi-file-earmark-fill'}`
}

const getFileColor = n => {
  const e = (n || '').split('.').pop().toLowerCase()
  const c = {
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
  return c[e] || '#6c757d'
}

const getFileColorBootstrap = n => {
  const e = (n || '').split('.').pop().toLowerCase()
  const c = {
    pdf: 'danger',
    doc: 'primary',
    docx: 'primary',
    xls: 'success',
    xlsx: 'success',
    ppt: 'warning',
    pptx: 'warning',
    txt: 'secondary',
    zip: 'info'
  }
  return c[e] || 'secondary'
}
</script>

<style scoped>
.upload-container {
  max-width: 1200px;
  margin: 0 auto;
}

.upload-zone {
  cursor: pointer;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.file-item {
  transition: all 0.2s ease;
  border-left-width: 4px !important;
}

.file-item:hover {
  box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1) !important;
  transform: translateY(-2px);
}

.file-icon-wrapper {
  min-width: 60px;
  min-height: 60px;
}

.hover-bg-light:hover {
  background-color: #f8f9fa !important;
}

.cursor-pointer {
  cursor: pointer;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

/* folder button wrapper inside card (no stacking needed here) */
.folder-dropdown-wrapper {
  position: relative;
  z-index: 1;
}

/* Teleport popup (rendered in body) */
.folder-popup-global {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  max-height: 360px;
  overflow-y: auto;
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.15);
  position: absolute !important;
  z-index: 9999999 !important;
  min-width: 200px;
  animation: popupIn .12s ease-out;
}

@keyframes popupIn {
  from {
    opacity: 0;
    transform: translateY(-4px) scale(0.995);
  }

  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* small helpers */
.folder-popup-global .hover-bg-light:hover {
  background: rgba(0, 0, 0, 0.05);
}
</style>
