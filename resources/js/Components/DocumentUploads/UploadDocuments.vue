<template>
  <div class="upload-container bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">📁 Tải tài liệu lên hệ thống</h2>

    <!-- Drag & Drop Zone -->
    <div
      class="border-2 border-dashed rounded-lg p-8 text-center transition-all duration-300"
      :class="isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50'"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleDrop"
    >
      <div class="text-6xl mb-4">{{ isDragging ? '📥' : '📂' }}</div>
      <p class="text-gray-600 mb-4">
        {{ isDragging ? 'Thả file tại đây' : 'Kéo thả file vào đây hoặc' }}
      </p>
      <button
        @click="triggerFileInput"
        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        Chọn file từ máy tính
      </button>
      <input
        ref="fileInput"
        type="file"
        multiple
        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
        class="hidden"
        @change="handleFileSelect"
      />
      <p class="text-xs text-gray-500 mt-2">
        Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP (tối đa 50MB/file)
      </p>
    </div>

    <!-- File List -->
    <div v-if="files.length" class="mt-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-700">
          📋 Danh sách file ({{ files.length }})
        </h3>
        <button
          v-if="pendingFiles > 0"
          @click="uploadAll"
          :disabled="uploading"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 transition"
        >
          {{ uploading ? '⏳ Đang tải...' : `⬆️ Tải lên tất cả (${pendingFiles})` }}
        </button>
      </div>

      <!-- List of Files -->
      <div class="space-y-3">
        <div
          v-for="(file, index) in files"
          :key="index"
          class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1 mr-4">
              <div class="flex items-center mb-2">
                <span class="text-2xl mr-2">{{ getFileIcon(file.name) }}</span>
                <div>
                  <p class="font-medium text-gray-800">{{ file.name }}</p>
                  <p class="text-sm text-gray-500">{{ file.size }}</p>
                </div>
              </div>

              <!-- Progress Bar -->
              <div v-if="file.progress !== null" class="mb-2">
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                  <div
                    class="h-2.5 rounded-full transition-all duration-300"
                    :class="file.status === 'error' ? 'bg-red-500' : 'bg-blue-600'"
                    :style="{ width: file.progress + '%' }"
                  ></div>
                </div>
                <p class="text-xs text-gray-600 mt-1">{{ file.progress }}%</p>
              </div>

              <!-- Status Badge -->
              <div class="flex items-center mt-2">
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
                <span v-if="file.errorMessage" class="text-xs text-red-600 ml-2">
                  {{ file.errorMessage }}
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col gap-2">
              <button
                v-if="file.status === 'pending'"
                @click="uploadSingle(file, index)"
                class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition"
              >
                ⬆️ Tải
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
import { ref, computed } from 'vue'
import axios from 'axios'

// === Reactive States ===
const files = ref([])
const isDragging = ref(false)
const uploading = ref(false)
const fileInput = ref(null)

// === Computed ===
const pendingFiles = computed(() =>
  files.value.filter(f => f.status === 'pending').length
)

// === File Selection ===
const triggerFileInput = () => fileInput.value.click()

const handleFileSelect = (event) => {
  prepareFiles(event.target.files)
  event.target.value = null
}

const handleDrop = (event) => {
  isDragging.value = false
  prepareFiles(event.dataTransfer.files)
}

// === Prepare Files ===
const prepareFiles = (fileList) => {
  for (const f of fileList) {
    const sizeMB = (f.size / 1024 / 1024).toFixed(2)
    if (f.size > 50 * 1024 * 1024) {
      alert(`❌ File "${f.name}" vượt quá 50MB!`)
      continue
    }
    files.value.push({
      file: f,
      name: f.name,
      size: `${sizeMB} MB`,
      progress: 0,
      status: 'pending',
      errorMessage: null
    })
  }
}

// === Remove File ===
const removeFile = (index) => files.value.splice(index, 1)

// === Upload Single File ===
const uploadSingle = async (fileObj, index) => {
  if (fileObj.status === 'uploading' || fileObj.status === 'done') return

  fileObj.status = 'uploading'
  fileObj.errorMessage = null

  const formData = new FormData()
  formData.append('file', fileObj.file)

  try {
    const response = await axios.post('/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      timeout: 20000, // 20s timeout
      onUploadProgress: (event) => {
        fileObj.progress = Math.round((event.loaded * 100) / event.total)
      },
    })

    if (response.data.success) {
      fileObj.status = 'done'
      fileObj.progress = 100
    } else {
      throw new Error(response.data.message || 'Upload thất bại (Server phản hồi không hợp lệ)')
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

// === Upload All Files ===
const uploadAll = async () => {
  if (uploading.value) return
  uploading.value = true

  for (let i = 0; i < files.value.length; i++) {
    const f = files.value[i]
    if (f.status === 'pending') await uploadSingle(f, i)
  }

  uploading.value = false
}

// === File Icon ===
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
  max-width: 900px;
  margin: 0 auto;
}
</style>
