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

              <a href="/documents" class="btn btn-secondary btn-block">
                <i class="fas fa-arrow-left"></i> Quay lại
              </a>
            </div>

            <!-- Version History -->
            <div v-if="document.versions && document.versions.length > 1" class="version-history">
              <h5 class="history-title">Lịch sử phiên bản</h5>
              <div class="version-list">
                <div
                  v-for="version in sortedVersions"
                  :key="version.version_id"
                  :class="['version-item', { current: version.is_current_version }]"
                  @click="switchVersion(version)"
                  style="cursor: pointer;"
                >
                  <div class="version-header">
                    <span class="version-number">
                      v{{ version.version_number }}
                      <span v-if="version.is_current_version" class="badge badge-success">Hiện tại</span>
                    </span>
                    <span class="version-date">{{ formatDate(version.created_at) }}</span>
                  </div>

                  <p v-if="version.change_note" class="version-note">{{ version.change_note }}</p>

                  <div class="version-meta">
                    <small>{{ version.user?.name }}</small>
                    <small>{{ formatSize(version.size) }}</small>
                  </div>

                  <a
                    :href="`/documents/versions/${version.version_id}/download`"
                    class="btn btn-sm btn-outline-primary"
                    @click.stop
                  >
                    <i class="fas fa-download"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Preview Section -->
        <div class="col-md-8 col-lg-9">
          <div class="document-preview-card">
            <h4 class="preview-title">
              <i class="fas fa-file-alt"></i> Xem trước tài liệu
            </h4>

            <div class="preview-container">
              <!-- Sử dụng PreviewFile Component -->
              <PreviewFile
                v-if="currentVersion && currentVersion.preview_url"
                :file-url="currentVersion.preview_url"
                :file-name="currentVersion.file_name"
              />

              <!-- Fallback khi không có file -->
              <div v-else class="no-preview">
                <i class="fas fa-file-alt fa-5x"></i>
                <h5>Không có preview</h5>
                <p>Chưa có phiên bản nào được tải lên hoặc file không có sẵn.</p>
              </div>
            </div>

            <!-- Tags -->
            <div v-if="document.tags && document.tags.length > 0" class="document-tags">
              <label><i class="fas fa-tags"></i> Tags:</label>
              <span v-for="tag in document.tags" :key="tag.id" class="tag-badge">{{ tag.name }}</span>
            </div>
          </div>

          <!-- Related -->
          <div v-if="relatedDocuments.length > 0" class="related-documents">
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
import PreviewFile from '../PreviewFile/PreviewFile.vue';

export default {
  name: 'DocumentDetailPage',
  components: {
    PreviewFile
  },
  data() {
    return {
      document: null,
      currentVersion: null,
      relatedDocuments: [],
      canEdit: true, // TODO: kiểm tra quyền user thực tế
    };
  },
  computed: {
    sortedVersions() {
      return [...(this.document?.versions || [])].sort((a, b) => b.version_number - a.version_number);
    },
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
    switchVersion(version) {
      // Chuyển đổi xem phiên bản khác
      this.currentVersion = version;
    },
    formatSize(bytes) {
      if (!bytes) return '';
      if (bytes < 1024) return bytes + ' B';
      if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
      return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
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

.info-group {
  margin-bottom: 15px;
}
.info-group label {
  font-weight: 600;
  color: #666;
  font-size: 13px;
  margin-bottom: 5px;
  display: block;
}
.info-value {
  color: #333;
  font-size: 14px;
  margin: 0;
  word-wrap: break-word;
}
.info-value i {
  margin-right: 6px;
  color: #888;
}

.action-buttons {
  margin-top: 25px;
  padding-top: 20px;
  border-top: 2px solid #f0f0f0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.version-history {
  margin-top: 25px;
  padding-top: 20px;
  border-top: 2px solid #f0f0f0;
}

.history-title {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 15px;
  color: #333;
}

.version-list {
  max-height: 400px;
  overflow-y: auto;
  padding-right: 5px;
}
.version-list::-webkit-scrollbar {
  width: 6px;
}
.version-list::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 3px;
}

.version-item {
  padding: 12px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  margin-bottom: 10px;
  transition: all 0.2s ease;
  background-color: #fff;
}
.version-item.current {
  background: #f0fff0;
  border-color: #28a745;
}
.version-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.version-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.version-number {
  font-weight: 600;
  color: #333;
}
.version-date {
  font-size: 12px;
  color: #999;
}
.version-note {
  font-size: 13px;
  color: #666;
  margin: 8px 0;
}
.version-meta {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #999;
  margin-top: 8px;
}

.document-preview-card {
  background: #fff;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  min-height: 600px;
}

.preview-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #f1f3f4;
}

.preview-container {
  position: relative;
  min-height: 500px;
}

.no-preview {
  text-align: center;
  padding: 100px 20px;
  color: #999;
}
.no-preview i {
  color: #ddd;
  margin-bottom: 20px;
  font-size: 48px;
}
.no-preview h5 {
  margin-bottom: 10px;
  font-weight: 600;
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
  display: inline-block;
  padding: 5px 12px;
  background: #e9ecef;
  border-radius: 15px;
  font-size: 12px;
  margin-right: 8px;
  margin-bottom: 8px;
}

.related-documents {
  margin-top: 30px;
  background: #fff;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}
.related-documents h5 {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 20px;
}
.related-card {
  padding: 15px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  margin-bottom: 15px;
  transition: all 0.2s ease;
}
.related-card:hover {
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}
.related-card h6 {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 8px;
}

@media (max-width: 768px) {
  .document-info-card {
    margin-bottom: 20px;
  }
  .document-preview-card {
    padding: 20px;
  }
  .preview-container {
    min-height: 400px;
  }
}
</style>