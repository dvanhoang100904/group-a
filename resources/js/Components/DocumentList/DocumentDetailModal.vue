<template>
  <div class="document-detail-container" v-if="document">
    <div class="container-fluid">
      <div class="row">
        <!-- Sidebar: Thông tin tài liệu -->
        <div class="col-md-4 col-lg-3">
          <div class="document-info-card">
            <h4 class="info-title">Thông tin tài liệu</h4>

            <div v-for="item in infoItems" :key="item.label" class="info-group">
              <label>{{ item.label }}:</label>
              <p class="info-value" v-html="item.value"></p>
            </div>

            <!-- Current Version -->
            <div v-if="currentVersion" class="info-group">
              <label>Phiên bản hiện tại:</label>
              <p class="info-value">v{{ currentVersion.version_number }}</p>
              <label>Kích thước:</label>
              <p class="info-value">{{ formatSize(currentVersion.size) }}</p>
              <label>Định dạng:</label>
              <p class="info-value">{{ getExtension(currentVersion.file_name) }}</p>
            </div>

            <!-- Actions -->
            <div class="action-buttons">
              <a
                v-if="currentVersion"
                :href="`/documents/versions/${currentVersion.version_id}/download`"
                class="btn btn-primary btn-block mb-2"
              >
                <i class="bi bi-download me-1"></i> Tải xuống
              </a>

              <!--Document Accesses -->
              <a :href="`/documents/${document.document_id}/accesses`" class="btn btn-primary btn-block mb-2">
                <i class="bi bi-shield-lock me-1"></i> Cài đặt chia sẻ
              </a>

              <!-- Document Versions -->
              <a :href="`/documents/${document.document_id}/versions`" class="btn btn-outline-primary btn-block mb-2">
                <i class="bi bi-clock-history me-1"></i> Phiên bản
              </a>

              <button v-if="canEdit" class="btn btn-outline-primary btn-block mb-2">
                <i class="bi bi-pencil-square me-1"></i> Chỉnh sửa
              </button>

              <a href="/documents" class="btn btn-outline-secondary btn-block">
                <i class="bi bi-arrow-left me-1"></i> Quay lại
              </a>
            </div>
          </div>
        </div>

        <!-- Preview Section -->
        <div class="col-md-8 col-lg-9">
          <div class="document-preview-card">
            <h4 class="preview-title">
              <i class="fas fa-file-alt"></i> Xem trước tài liệu
              <!-- Debug info - xóa sau khi test -->
              <small v-if="currentVersion" class="text-muted" style="font-size: 12px; margin-left: 10px;">
                (Preview: {{ currentVersion.preview_url ? '✓' : '✗' }})
              </small>
            </h4>

            <div class="preview-container">
              <!-- Ưu tiên hiển thị preview_url nếu có -->
              <iframe
                v-if="getPreviewSource()"
                :src="getPreviewSource()"
                class="pdf-viewer"
              ></iframe>

              <!-- Image preview (fallback) -->
              <div v-else-if="isImage(currentVersion?.file_name)" class="image-preview">
                <img 
                  :src="currentVersion?.file_url" 
                  alt="Preview" 
                  class="img-fluid" 
                  @error="handleImageError"
                />
              </div>

              <!-- No preview -->
              <div v-else class="no-preview">
                <i class="fas fa-file-alt fa-5x"></i>
                <h5>Không có preview</h5>
                <p>Định dạng này chưa hỗ trợ xem trước. Hãy tải xuống để xem.</p>
                <!-- Debug info -->
                <small class="text-muted">File: {{ currentVersion?.file_name }}</small>
              </div>
            </div>

            <!-- Tags -->
            <div v-if="document.tags && document.tags.length" class="document-tags">
              <label><i class="fas fa-tags"></i> Tags:</label>
              <span v-for="tag in document.tags" :key="tag.id" class="tag-badge">{{ tag.name }}</span>
            </div>
          </div>

          <!-- Related Documents -->
          <div v-if="relatedDocuments.length" class="related-documents">
            <h5><i class="fas fa-link"></i> Tài liệu liên quan</h5>
            <div class="row">
              <div v-for="rel in relatedDocuments" :key="rel.document_id" class="col-md-6 col-lg-4">
                <div class="related-card">
                  <h6>{{ rel.title }}</h6>
                  <p class="text-muted">{{ truncate(rel.description, 80) }}</p>
                  <a :href="`/documents/${rel.document_id}/detail`" class="btn btn-sm btn-outline-primary">
                    Xem chi tiết
                  </a>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'DocumentDetailPage',
  data() {
    return {
      document: null,
      currentVersion: null,
      relatedDocuments: [],
      canEdit: true,
    };
  },
  computed: {
    infoItems() {
      if (!this.document) return [];
      return [
        { label: 'Tiêu đề', value: this.document.title },
        { label: 'Mô tả', value: this.document.description || 'Không có mô tả' },
        { label: 'Người upload', value: `<i class='fas fa-user'></i> ${this.document.user?.name}` },
        { label: 'Loại tài liệu', value: `<i class='fas fa-tag'></i> ${this.document.type?.name || 'N/A'}` },
        { label: 'Môn học', value: `<i class='fas fa-book'></i> ${this.document.subject?.name || 'N/A'}` },
        { label: 'Ngày tạo', value: `<i class='fas fa-calendar'></i> ${this.formatDate(this.document.created_at)}` },
        { label: 'Trạng thái', value: this.document.status },
      ];
    },
  },
  methods: {
    async fetchData() {
      try {
        const id = window.location.pathname.split('/').filter(Boolean).pop();
        const res = await axios.get(`/api/documents/${id}/detail`);
        
        this.document = res.data.document;
        this.currentVersion = res.data.current_version;
        this.relatedDocuments = res.data.related_documents || [];
        
        // Debug: Log ra console để kiểm tra
        console.log('Document ID:', id);
        console.log('Current Version:', this.currentVersion);
        console.log('Preview URL:', this.currentVersion?.preview_url);
        console.log('File URL:', this.currentVersion?.file_url);
        
        // Nếu backend chưa trả preview_url, tự tạo
        if (this.currentVersion && !this.currentVersion.preview_url) {
          this.buildPreviewUrl();
        }
      } catch (error) {
        console.error('Error fetching document:', error);
        alert('Không thể tải thông tin tài liệu');
      }
    },
    
    // Tự động tạo preview_url nếu backend chưa có
    buildPreviewUrl() {
      const docId = this.document.document_id;
      const versionNum = this.currentVersion.version_number;
      const previewUrl = `http://localhost:8080/storage/documents/${docId}/preview_${versionNum}.pdf`;
      
      // Kiểm tra file có tồn tại không
      this.checkFileExists(previewUrl).then(exists => {
        if (exists) {
          this.currentVersion.preview_url = previewUrl;
          console.log('Preview URL created:', previewUrl);
        }
      });
    },
    
    // Kiểm tra file preview có tồn tại
    async checkFileExists(url) {
      try {
        const response = await fetch(url, { method: 'HEAD' });
        return response.ok;
      } catch {
        return false;
      }
    },
    
    // Lấy nguồn preview (ưu tiên preview_url)
    getPreviewSource() {
      if (!this.currentVersion) return null;
      
      const fileName = this.currentVersion.file_name;
      
      // 1. Ưu tiên preview_url (PDF đã convert)
      if (this.currentVersion.preview_url) {
        return this.currentVersion.preview_url;
      }
      
      // 2. Nếu là PDF gốc, dùng file_url
      if (this.isPdf(fileName)) {
        return this.currentVersion.file_url;
      }
      
      // 3. Nếu là Office file, dùng Google Docs Viewer
      if (this.isOffice(fileName) && this.currentVersion.file_url) {
        return `https://docs.google.com/gview?url=${encodeURIComponent(this.currentVersion.file_url)}&embedded=true`;
      }
      
      return null;
    },
    
    isPdf(fileName) {
      return fileName?.toLowerCase().endsWith('.pdf');
    },
    
    isImage(fileName) {
      return /\.(jpg|jpeg|png|gif|webp)$/i.test(fileName || '');
    },
    
    isOffice(fileName) {
      return /\.(docx|doc|xlsx|xls|pptx|ppt)$/i.test(fileName || '');
    },
    
    handleImageError(e) {
      console.error('Image load error:', e);
      e.target.src = '/images/no-preview.png'; // Placeholder image
    },
    
    formatSize(bytes) {
      if (!bytes) return '';
      if (bytes < 1024) return bytes + ' B';
      else if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
      return (bytes / (1024*1024)).toFixed(2) + ' MB';
    },
    
    getExtension(file) {
      return file?.split('.').pop()?.toUpperCase() || 'N/A';
    },
    
    formatDate(date) {
      return new Date(date).toLocaleString('vi-VN');
    },
    
    truncate(text, len) {
      return text?.length > len ? text.slice(0, len) + '...' : text;
    },
  },
  mounted() {
    this.fetchData();
  },
};
</script>

<style scoped>
.document-detail-container {
  padding: 30px 0;
  min-height: calc(100vh - 100px);
  background-color: #f9fafb;
}
.document-info-card {
  background: #fff;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  margin-bottom: 20px;
  transition: box-shadow 0.2s ease;
}
.document-info-card:hover {
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
.info-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #f1f3f4;
  color: #333;
}
.info-group { margin-bottom: 15px; }
.info-group label { font-weight: 600; color: #666; font-size: 13px; display: block; margin-bottom: 5px; }
.info-value { color: #333; font-size: 14px; margin: 0; word-wrap: break-word; }
.info-value i { margin-right: 6px; color: #888; }
.action-buttons { 
  margin-top: 25px; 
  padding-top: 20px; 
  border-top: 2px solid #f0f0f0; 
  display: flex; 
  flex-direction: column; 
  gap: 10px; 
}
.document-preview-card { 
  background: #fff; 
  padding: 25px; 
  border-radius: 12px; 
  box-shadow: 0 2px 6px rgba(0,0,0,0.05); 
  min-height: 600px; 
}
.preview-title { 
  font-size: 18px; 
  font-weight: 600; 
  margin-bottom: 20px; 
  color: #333; 
  display: flex;
  align-items: center;
}
.preview-container {
  position: relative;
  min-height: 500px;
}
.pdf-viewer { 
  width: 100%; 
  height: 700px; 
  border: 1px solid #ddd; 
  border-radius: 6px; 
  background: #f5f5f5;
}
.image-preview { 
  text-align: center; 
  padding: 20px;
}
.image-preview img { 
  max-width: 100%;
  max-height: 700px; 
  border: 1px solid #ddd; 
  border-radius: 6px; 
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.no-preview { 
  text-align: center; 
  padding: 100px 20px; 
  color: #999; 
}
.no-preview i {
  color: #ccc;
  margin-bottom: 20px;
}
.document-tags { 
  margin-top: 20px; 
  padding-top: 20px; 
  border-top: 1px solid #f0f0f0; 
}
.document-tags label { 
  font-weight: 600; 
  color: #666; 
  margin-right: 10px; 
}
.tag-badge { 
  display:inline-block; 
  padding:5px 12px; 
  background:#e9ecef; 
  border-radius:15px; 
  font-size:12px; 
  margin-right:8px; 
  margin-bottom:8px;
  color: #495057;
}
.related-documents { 
  margin-top:30px; 
  background:#fff; 
  padding:25px; 
  border-radius:12px; 
  box-shadow:0 2px 6px rgba(0,0,0,0.05); 
}
.related-documents h5 { 
  font-size: 18px; 
  font-weight: 600; 
  margin-bottom: 20px; 
  color: #333; 
}
.related-card { 
  padding:15px; 
  border:1px solid #e0e0e0; 
  border-radius:8px; 
  margin-bottom:15px; 
  transition:all 0.2s ease;
  background: #fff;
}
.related-card:hover { 
  box-shadow:0 4px 10px rgba(0,0,0,0.08); 
  transform:translateY(-2px); 
  border-color: #007bff;
}
.related-card h6 { 
  font-size: 15px; 
  font-weight: 600; 
  margin-bottom: 10px; 
  color: #333; 
}
</style>