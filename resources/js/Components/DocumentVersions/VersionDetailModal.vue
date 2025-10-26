<template>
    <!-- modal detail version -->
    <div
        class="modal fade"
        ref="modalRef"
        tabindex="-1"
        aria-labelledby="versionDetailModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <!-- header -->
                <div class="modal-header bg-light">
                    <div class="d-flex align-items-center">
                        <div class="icon-wrapper bg-primary rounded p-2 me-3">
                            <i class="bi bi-file-earmark-text text-white"></i>
                        </div>
                        <div>
                            <h5
                                class="modal-title mb-0"
                                id="versionDetailModalLabel"
                            >
                                Phiên bản v{{ selectedVersion?.version_number }}
                            </h5>
                            <div class="text-muted small mt-1">
                                <span
                                    v-if="selectedVersion?.is_current_version"
                                    class="badge bg-success-subtle text-success me-2"
                                >
                                    <i class="bi bi-check-circle me-1"></i>Hiện
                                    tại
                                </span>
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        @click="closeModal"
                        aria-label="Close"
                    ></button>
                </div>

                <!-- body -->
                <div class="modal-body">
                    <template v-if="loading">
                        <!-- Loading skeleton / spinner -->
                        <div class="text-center py-5">
                            <div
                                class="spinner-border text-primary"
                                role="status"
                            >
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="section-title mb-3">
                                    <i class="bi bi-card-text me-2"></i>Thông
                                    tin phiên bản
                                </h6>
                            </div>

                            <!-- change note -->
                            <div class="col-sm-12 mb-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-3">
                                        <h6 class="card-title fw-bold mb-2">
                                            <i
                                                class="bi bi-journal-text me-2"
                                            ></i
                                            >Ghi chú thay đổi
                                        </h6>
                                        <p class="card-text mb-0">
                                            {{
                                                selectedVersion?.change_note ||
                                                "Không có ghi chú"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- mime type  -->
                            <div class="col-sm-6 mb-3">
                                <div class="info-item">
                                    <div class="info-icon text-primary">
                                        <i class="bi bi-file-earmark"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label fw-bold">
                                            Loại file
                                        </div>
                                        <div class="info-value">
                                            {{
                                                formatMimeType(
                                                    selectedVersion?.mime_type
                                                )
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- file size -->
                            <div class="col-sm-6 mb-3">
                                <div class="info-item">
                                    <div class="info-icon text-primary">
                                        <i class="bi bi-hdd"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label fw-bold">
                                            Kích thước
                                        </div>
                                        <div class="info-value">
                                            {{
                                                formatFileSize(
                                                    selectedVersion?.file_size
                                                )
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <h6 class="section-title mb-3">
                                    <i class="bi bi-person me-2"></i>Thông tin
                                    cập nhật
                                </h6>
                            </div>

                            <!-- user name -->
                            <div class="col-sm-6 mb-3">
                                <div class="info-item">
                                    <div class="info-icon text-primary">
                                        <i class="bi bi-person-circle"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label fw-bold">
                                            Người cập nhật
                                        </div>
                                        <div class="info-value">
                                            {{
                                                selectedVersion?.user?.name ||
                                                "Không rõ"
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- create at -->
                            <div class="col-sm-6 mb-3">
                                <div class="info-item">
                                    <div class="info-icon text-primary">
                                        <i class="bi bi-calendar-event"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label fw-bold">
                                            Ngày cập nhật
                                        </div>
                                        <div class="info-value">
                                            {{
                                                formatDate(
                                                    selectedVersion?.created_at
                                                )
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- is current version -->
                            <div class="col-sm-6 mb-3">
                                <div class="info-item">
                                    <div class="info-icon text-primary">
                                        <i class="bi bi-info-circle"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label fw-bold">
                                            Trạng thái
                                        </div>
                                        <div class="info-value">
                                            <span
                                                v-if="
                                                    selectedVersion?.is_current_version
                                                "
                                                class="badge bg-success-subtle text-success"
                                            >
                                                <i
                                                    class="bi bi-check-circle me-1"
                                                ></i
                                                >Hiện tại
                                            </span>
                                            <span
                                                v-else
                                                class="badge bg-secondary-subtle text-secondary"
                                            >
                                                <i
                                                    class="bi bi-clock-history me-1"
                                                ></i
                                                >Cũ
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- footer -->
                <div class="modal-footer bg-light">
                    <div
                        class="d-flex w-100 justify-content-between align-items-center"
                    >
                        <div class="file-actions">
                            <button
                                v-if="selectedVersion?.version_id"
                                class="btn btn-sm btn-primary"
                                @click="previewFile"
                                title="Xem trước tài liệu"
                            >
                                <i class="bi bi-eye me-1"></i> Xem trước
                            </button>
                        </div>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            @click="closeModal"
                        >
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- modal preview file -->
        <FilePreviewModal
            v-show="versionToPreview"
            v-model:version-id="versionToPreview"
            :document-id="selectedVersion?.document_id"
        />
    </div>
</template>

<script setup>
import { ref, watch, nextTick } from "vue";
import axios from "axios";
import FilePreviewModal from "./FilePreviewModal.vue";

// Nhan props tu cha
const props = defineProps({
    documentId: { type: [String, Number], required: true },
    versionId: { type: [String, Number], default: null },
    formatFileSize: Function,
    formatMimeType: Function,
    formatDate: Function,
});

const emit = defineEmits(["update:versionId"]);

const selectedVersion = ref(null);
const loading = ref(false);
const versionToPreview = ref(null);

// ref modal chinh
const modalRef = ref(null);
// instance Bootstrap modal
let bsModal = null;

// Hien thi modal
const showModal = () => {
    if (!bsModal) bsModal = new bootstrap.Modal(modalRef.value);
    bsModal.show();
};

// An modal
const hideModal = () => {
    if (bsModal) {
        bsModal.hide();
        bsModal = null;
    }
};

// Dong modal va reset
const closeModal = () => {
    hideModal();
    selectedVersion.value = null;
    emit("update:versionId", null);
};

// File preview
const previewFile = () => {
    versionToPreview.value = null;
    nextTick(() => {
        versionToPreview.value = selectedVersion.value.version_id;
    });
};

// Watch versionId changes
watch(
    () => props.versionId,
    async (versionId) => {
        if (!versionId) return;

        showModal();
        selectedVersion.value = null;
        loading.value = true;

        try {
            const res = await axios.get(
                `/api/documents/${props.documentId}/versions/${versionId}`
            );
            if (res.data.success) selectedVersion.value = res.data.data;
        } catch (err) {
            console.error(err);
            alert("Không thể tải chi tiết phiên bản");
            closeModal();
        } finally {
            loading.value = false;
        }
    }
);

defineExpose({ showModal, hideModal, closeModal });
</script>

<style scoped>
.info-item {
    display: flex;
    align-items: center;
    padding: 10px 0;
}

.info-icon {
    font-size: 1.2rem;
    width: 40px;
    text-align: center;
    margin-right: 12px;
}

.info-content {
    flex: 1;
}

.info-label {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 2px;
}

.info-value {
    font-size: 0.95rem;
    color: #212529;
}

.section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 8px;
}

.icon-wrapper {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.file-actions .btn {
    border-radius: 6px;
    font-weight: 500;
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
}

.card.bg-light {
    background-color: #f8f9fa !important;
}
</style>
