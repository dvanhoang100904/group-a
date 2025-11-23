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
                class="btn btn-primary btn-block"
              >
                <i class="fas fa-download"></i> Tải xuống
              </a>

              <button v-if="canEdit" class="btn btn-warning btn-block">
                <i class="fas fa-edit"></i> Chỉnh sửa
              </button>
              <a :href="`/documents/${document.document_id}/versions`" class="btn btn-info btn-block">
                <i class="fas fa-history me-1"></i> Phiên bản
              </a>

              <a href="/documents" class="btn btn-secondary btn-block">
                <i class="fas fa-arrow-left"></i> Quay lại
              </a>
            </div>
          </div>
        </div>

        <!-- Preview Section -->
        <div class="col-md-8 col-lg-9">
          <div class="document-preview-card">
            <h4 class="preview-title"><i class="fas fa-file-alt"></i> Xem trước tài liệu</h4>

            <div class="preview-container">
              <!-- PDF -->
              <iframe
                v-if="isPdf(currentVersion?.file_name)"
                :src="currentVersion?.preview_url"
                class="pdf-viewer"
              ></iframe>

              <!-- Image -->
              <div v-else-if="isImage(currentVersion?.file_name)" class="image-preview">
                <img :src="currentVersion?.preview_url" alt="Preview" class="img-fluid" />
              </div>

              <!-- Office: DOCX / XLSX / PPTX -->
              <iframe
                v-else-if="isOffice(currentVersion?.file_name)"
                :src="getPreviewUrl(currentVersion.file_name, currentVersion.file_url)"
                class="pdf-viewer"
              ></iframe>

              <!-- No preview -->
              <div v-else class="no-preview">
                <i class="fas fa-file-alt fa-5x"></i>
                <h5>Không có preview</h5>
                <p>Định dạng này chưa hỗ trợ xem trước. Hãy tải xuống để xem.</p>
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
      const id = window.location.pathname.split('/').pop();
      const res = await axios.get(`/api/documents/${id}/detail`);
      this.document = res.data.document;
      this.currentVersion = res.data.current_version;
      this.relatedDocuments = res.data.related_documents;
    },
    isPdf(fileName) {
      return fileName?.toLowerCase().endsWith('.pdf');
    },
    isImage(fileName) {
      return /\.(jpg|jpeg|png|gif)$/i.test(fileName || '');
    },
    isOffice(fileName) {
      return /\.(docx|doc|xlsx|xls|pptx|ppt)$/i.test(fileName || '');
    },
    getPreviewUrl(fileName, fileUrl) {
      if (!fileUrl) return null;
      if (this.isPdf(fileName) || this.isImage(fileName)) return fileUrl;
      if (this.isOffice(fileName)) {
        // Google Docs Viewer, encode URL để tránh lỗi space
        return `https://docs.google.com/gview?url=${encodeURIComponent(fileUrl)}&embedded=true`;
      }
      return null;
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
/* --- Các style giống trước --- */
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
.action-buttons { margin-top: 25px; padding-top: 20px; border-top: 2px solid #f0f0f0; display: flex; flex-direction: column; gap: 10px; }
.document-preview-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); min-height: 600px; }
.pdf-viewer { width: 100%; height: 700px; border: 1px solid #ddd; border-radius: 6px; }
.image-preview img { max-height: 700px; border: 1px solid #ddd; border-radius: 6px; display: block; margin: 0 auto; }
.no-preview { text-align: center; padding: 100px 20px; color: #999; }
.tag-badge { display:inline-block; padding:5px 12px; background:#e9ecef; border-radius:15px; font-size:12px; margin-right:8px; margin-bottom:8px; }
.related-documents { margin-top:30px; background:#fff; padding:25px; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.05); }
.related-card { padding:15px; border:1px solid #e0e0e0; border-radius:8px; margin-bottom:15px; transition:all 0.2s ease; }
.related-card:hover { box-shadow:0 4px 10px rgba(0,0,0,0.08); transform:translateY(-2px); }
</style>
