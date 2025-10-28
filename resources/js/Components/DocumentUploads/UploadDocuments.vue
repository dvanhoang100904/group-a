<template>
    <div class="upload-container">
        <!-- Header -->
        <div class="upload-header">
            <h2>📤 Upload Tài Liệu</h2>
            <p class="subtitle">Kéo thả file hoặc click để chọn</p>
        </div>

        <!-- Drop Zone -->
        <div
            class="drop-zone"
            :class="{ 'is-dragover': isDragover, 'has-files': files.length > 0 }"
            @drop.prevent="handleDrop"
            @dragover.prevent="isDragover = true"
            @dragleave.prevent="isDragover = false"
            @click="$refs.fileInput.click()"
        >
            <input
                ref="fileInput"
                type="file"
                multiple
                @change="handleFileSelect"
                style="display: none"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png"
            />

            <div v-if="files.length === 0" class="drop-zone-placeholder">
                <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" y1="3" x2="12" y2="15" />
                </svg>
                <p class="drop-text">Kéo thả file vào đây</p>
                <p class="drop-subtext">hoặc click để chọn file</p>
                <p class="drop-hint">Hỗ trợ: PDF, DOC, XLS, PPT, Images (Max 50MB/file)</p>
            </div>
        </div>

        <!-- File List -->
        <div v-if="files.length > 0" class="file-list">
            <div v-for="(file, index) in files" :key="index" class="file-item">
                <div class="file-info">
                    <div class="file-icon">📄</div>
                    <div class="file-details">
                        <p class="file-name">{{ file.name }}</p>
                        <p class="file-size">{{ formatFileSize(file.size) }}</p>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div v-if="file.uploading" class="progress-bar">
                    <div class="progress-fill" :style="{ width: file.progress + '%' }"></div>
                    <span class="progress-text">{{ file.progress }}%</span>
                </div>

                <!-- Status -->
                <div class="file-status">
                    <span v-if="file.uploaded" class="status-success">✓ Thành công</span>
                    <span v-else-if="file.error" class="status-error">✗ Lỗi: {{ file.error }}</span>
                    <button v-else @click="removeFile(index)" class="btn-remove">✕</button>
                </div>
            </div>
        </div>

        <!-- Metadata Form -->
        <div v-if="files.length > 0 && !isUploading" class="metadata-form">
            <h3>Thông tin tài liệu</h3>
            
            <div class="form-group">
                <label>Tiêu đề *</label>
                <input v-model="metadata.title" type="text" placeholder="Nhập tiêu đề tài liệu" required />
            </div>

            <div class="form-group">
                <label>Mô tả</label>
                <textarea v-model="metadata.description" rows="3" placeholder="Mô tả ngắn gọn về tài liệu"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Loại tài liệu *</label>
                    <select v-model="metadata.type_id" required>
                        <option value="">-- Chọn loại --</option>
                        <option v-for="type in types" :key="type.type_id" :value="type.type_id">
                            {{ type.name }}
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Môn học *</label>
                    <select v-model="metadata.subject_id" required>
                        <option value="">-- Chọn môn --</option>
                        <option v-for="subject in subjects" :key="subject.subject_id" :value="subject.subject_id">
                            {{ subject.name }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Thư mục</label>
                    <select v-model="metadata.folder_id">
                        <option value="">-- Không chọn --</option>
                        <option v-for="folder in folders" :key="folder.folder_id" :value="folder.folder_id">
                            {{ folder.name }}
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Trạng thái *</label>
                    <select v-model="metadata.status" required>
                        <option value="private">🔒 Riêng tư</option>
                        <option value="public">🌐 Công khai</option>
                        <option value="restricted">⚠️ Hạn chế</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Tags</label>
                <div class="tags-input">
                    <span v-for="tag in selectedTags" :key="tag" class="tag">
                        {{ tag }}
                        <button @click="removeTag(tag)" class="tag-remove">✕</button>
                    </span>
                    <input
                        v-model="tagInput"
                        @keydown.enter.prevent="addTag"
                        @keydown.comma.prevent="addTag"
                        type="text"
                        placeholder="Nhập tag và Enter"
                    />
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div v-if="files.length > 0" class="action-buttons">
            <button @click="clearAll" :disabled="isUploading" class="btn btn-secondary">
                Xóa tất cả
            </button>
            <button @click="uploadFiles" :disabled="isUploading || !isFormValid" class="btn btn-primary">
                <span v-if="isUploading">Đang upload...</span>
                <span v-else>📤 Upload ({{ files.length }} file)</span>
            </button>
        </div>

        <!-- Success Message -->
        <div v-if="uploadSuccess" class="success-message">
            ✓ Upload thành công! <a :href="successUrl">Xem tài liệu</a>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'UploadDocuments',
    data() {
        return {
            files: [],
            isDragover: false,
            isUploading: false,
            uploadSuccess: false,
            successUrl: '',
            metadata: {
                title: '',
                description: '',
                type_id: '',
                subject_id: '',
                folder_id: '',
                status: 'private'
            },
            types: [],
            subjects: [],
            folders: [],
            tagInput: '',
            selectedTags: []
        };
    },
    computed: {
        isFormValid() {
            return this.metadata.title && this.metadata.type_id && this.metadata.subject_id;
        }
    },
    mounted() {
        this.fetchMetadata();
    },
    methods: {
        async fetchMetadata() {
            try {
                const response = await axios.get('/api/upload/metadata');
                this.types = response.data.types || [];
                this.subjects = response.data.subjects || [];
                this.folders = response.data.folders || [];
            } catch (error) {
                console.error('Lỗi khi tải metadata:', error);
            }
        },
        handleDrop(e) {
            this.isDragover = false;
            const droppedFiles = Array.from(e.dataTransfer.files);
            this.addFiles(droppedFiles);
        },
        handleFileSelect(e) {
            const selectedFiles = Array.from(e.target.files);
            this.addFiles(selectedFiles);
        },
        addFiles(newFiles) {
            newFiles.forEach(file => {
                if (file.size > 50 * 1024 * 1024) {
                    alert(`File "${file.name}" vượt quá 50MB`);
                    return;
                }
                this.files.push({
                    file: file,
                    name: file.name,
                    size: file.size,
                    uploading: false,
                    uploaded: false,
                    progress: 0,
                    error: null
                });
            });
        },
        removeFile(index) {
            this.files.splice(index, 1);
        },
        clearAll() {
            this.files = [];
            this.uploadSuccess = false;
        },
        addTag() {
            const tag = this.tagInput.trim();
            if (tag && !this.selectedTags.includes(tag)) {
                this.selectedTags.push(tag);
            }
            this.tagInput = '';
        },
        removeTag(tag) {
            this.selectedTags = this.selectedTags.filter(t => t !== tag);
        },
        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        },
        async uploadFiles() {
            this.isUploading = true;
            this.uploadSuccess = false;

            for (let fileObj of this.files) {
                if (fileObj.uploaded) continue;

                fileObj.uploading = true;
                const formData = new FormData();
                formData.append('file', fileObj.file);
                formData.append('title', this.metadata.title);
                formData.append('description', this.metadata.description);
                formData.append('type_id', this.metadata.type_id);
                formData.append('subject_id', this.metadata.subject_id);
                formData.append('folder_id', this.metadata.folder_id);
                formData.append('status', this.metadata.status);
                formData.append('tags', JSON.stringify(this.selectedTags));

                try {
                    const response = await axios.post('/upload/store', formData, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                        onUploadProgress: (progressEvent) => {
                            fileObj.progress = Math.round(
                                (progressEvent.loaded * 100) / progressEvent.total
                            );
                        }
                    });

                    fileObj.uploaded = true;
                    fileObj.uploading = false;
                    this.successUrl = response.data.url || '/documents';
                } catch (error) {
                    fileObj.error = error.response?.data?.message || 'Upload thất bại';
                    fileObj.uploading = false;
                }
            }

            this.isUploading = false;
            const allSuccess = this.files.every(f => f.uploaded);
            if (allSuccess) {
                this.uploadSuccess = true;
                setTimeout(() => {
                    window.location.href = this.successUrl;
                }, 2000);
            }
        }
    }
};
</script>

<style scoped>
.upload-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.upload-header {
    text-align: center;
    margin-bottom: 30px;
}

.upload-header h2 {
    font-size: 28px;
    color: #333;
    margin-bottom: 8px;
}

.subtitle {
    color: #666;
    font-size: 14px;
}

.drop-zone {
    border: 3px dashed #d1d5db;
    border-radius: 12px;
    padding: 60px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f9fafb;
}

.drop-zone:hover {
    border-color: #3b82f6;
    background: #eff6ff;
}

.drop-zone.is-dragover {
    border-color: #10b981;
    background: #d1fae5;
}

.drop-zone.has-files {
    padding: 20px;
    border-style: solid;
}

.upload-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    color: #9ca3af;
    stroke-width: 2;
}

.drop-text {
    font-size: 18px;
    color: #374151;
    margin-bottom: 8px;
}

.drop-subtext {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 16px;
}

.drop-hint {
    color: #9ca3af;
    font-size: 12px;
}

.file-list {
    margin-top: 20px;
}

.file-item {
    display: flex;
    align-items: center;
    padding: 16px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 12px;
}

.file-info {
    display: flex;
    align-items: center;
    flex: 1;
    gap: 12px;
}

.file-icon {
    font-size: 32px;
}

.file-details {
    flex: 1;
}

.file-name {
    font-weight: 500;
    color: #111827;
    margin-bottom: 4px;
}

.file-size {
    font-size: 13px;
    color: #6b7280;
}

.progress-bar {
    flex: 1;
    height: 24px;
    background: #e5e7eb;
    border-radius: 12px;
    position: relative;
    overflow: hidden;
    margin: 0 16px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #10b981);
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: 600;
    color: #111827;
}

.file-status {
    margin-left: 16px;
}

.status-success {
    color: #10b981;
    font-weight: 500;
}

.status-error {
    color: #ef4444;
    font-size: 13px;
}

.btn-remove {
    background: #fee;
    border: none;
    color: #ef4444;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 16px;
}

.btn-remove:hover {
    background: #fecaca;
}

.metadata-form {
    background: white;
    padding: 24px;
    border-radius: 12px;
    margin-top: 24px;
    border: 1px solid #e5e7eb;
}

.metadata-form h3 {
    margin-bottom: 20px;
    color: #111827;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 8px;
    color: #374151;
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.tags-input {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 8px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    min-height: 42px;
}

.tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: #3b82f6;
    color: white;
    border-radius: 16px;
    font-size: 13px;
}

.tag-remove {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 14px;
}

.tags-input input {
    flex: 1;
    border: none;
    outline: none;
    min-width: 150px;
}

.action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #2563eb;
}

.btn-secondary {
    background: #e5e7eb;
    color: #374151;
}

.btn-secondary:hover:not(:disabled) {
    background: #d1d5db;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.success-message {
    margin-top: 20px;
    padding: 16px;
    background: #d1fae5;
    color: #065f46;
    border-radius: 8px;
    text-align: center;
    font-weight: 500;
}

.success-message a {
    color: #059669;
    text-decoration: underline;
    margin-left: 8px;
}
</style>