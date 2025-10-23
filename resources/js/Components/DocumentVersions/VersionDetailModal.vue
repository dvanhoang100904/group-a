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
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="section-title mb-3">
                                <i class="bi bi-card-text me-2"></i>Thông tin
                                phiên bản
                            </h6>
                        </div>

                        <!-- change note -->
                        <div class="col-sm-12 mb-3">
                            <div class="card bg-light border-0">
                                <div class="card-body p-3">
                                    <h6 class="card-title fw-bold mb-2">
                                        <i class="bi bi-journal-text me-2"></i
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
                                <i class="bi bi-person me-2"></i>Thông tin cập
                                nhật
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
            v-model:version-id="versionToPreview"
            :file-path="selectedVersion?.file_path"
        />
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import FilePreviewModal from "./FilePreviewModal.vue";

// Nhan props tu cha
const props = defineProps({
    selectedVersion: Object,
    formatFileSize: Function,
    formatMimeType: Function,
    formatDate: Function,
});

const emit = defineEmits(["update:selectedVersion"]);

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
    versionToPreview.value = null;
    emit("update:selectedVersion", null);
};

// Watch props.selectedVersion để show/hide modal
watch(
    () => props.selectedVersion,
    (val) => {
        if (val) showModal();
        else hideModal();
    }
);

// File preview
const versionToPreview = ref(null);
const previewFile = () => {
    if (props.selectedVersion?.version_id) {
        versionToPreview.value = props.selectedVersion.version_id;
    }
};

defineExpose({ showModal, hideModal, closeModal });
</script>
