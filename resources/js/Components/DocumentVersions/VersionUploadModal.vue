<template>
    <div>
        <!-- Modal -->
        <div class="modal fade" ref="modalRef" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <!-- Header -->
                    <div class="modal-header bg-primary text-white">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-cloud-upload fs-4 me-2"></i>
                            <h5 class="modal-title fw-bold">
                                Tải lên phiên bản mới
                            </h5>
                        </div>
                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            @click="closeModal"
                        ></button>
                    </div>

                    <div class="modal-body p-4">
                        <form @submit.prevent="submitUpload">
                            <!-- File Upload Card -->
                            <div class="card border-dashed mb-4">
                                <div class="card-body text-center p-4">
                                    <i
                                        class="bi bi-file-earmark-arrow-up text-muted fs-1"
                                    ></i>
                                    <h6 class="mt-3 mb-1 fw-semibold">
                                        Chọn file để tải lên
                                    </h6>
                                    <p class="text-muted small mb-3">
                                        Chọn file từ thiết bị của bạn (tối đa
                                        10MB)
                                    </p>
                                    <input
                                        type="file"
                                        class="form-control d-none"
                                        ref="fileInput"
                                        required
                                        @change="onFileChange"
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        @click="$refs.fileInput.click()"
                                    >
                                        <i class="bi bi-folder2-open me-2"></i
                                        >Chọn file
                                    </button>

                                    <!-- Hiển thị file đã chọn -->
                                    <div
                                        v-if="selectedFile"
                                        class="mt-3 p-3 bg-light rounded"
                                    >
                                        <div
                                            class="d-flex align-items-center justify-content-between"
                                        >
                                            <div
                                                class="d-flex align-items-center"
                                            >
                                                <i
                                                    class="bi bi-file-text text-primary me-2"
                                                ></i>
                                                <div>
                                                    <div class="fw-semibold">
                                                        {{ selectedFile.name }}
                                                    </div>
                                                    <small class="text-muted">
                                                        {{
                                                            formatFileSize(
                                                                selectedFile.size
                                                            )
                                                        }}
                                                    </small>
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                @click="removeFile"
                                            >
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Change Note -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-journal-text me-2"></i>Ghi
                                    chú thay đổi
                                </label>
                                <textarea
                                    v-model="changeNote"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Mô tả những thay đổi trong phiên bản này..."
                                ></textarea>
                            </div>

                            <!-- Progress bar -->
                            <div v-if="progress > 0" class="mb-4">
                                <div
                                    class="d-flex justify-content-between mb-2"
                                >
                                    <span class="small text-muted"
                                        >Đang tải lên...</span
                                    >
                                    <span class="small fw-semibold"
                                        >{{ progress }}%</span
                                    >
                                </div>
                                <div class="progress" style="height: 8px">
                                    <div
                                        class="progress-bar progress-bar-striped progress-bar-animated"
                                        role="progressbar"
                                        :style="{ width: progress + '%' }"
                                        :aria-valuenow="progress"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                    ></div>
                                </div>
                            </div>

                            <!-- Status Messages -->
                            <div
                                v-if="error"
                                class="alert alert-danger d-flex align-items-center"
                            >
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                {{ error }}
                            </div>
                            <div
                                v-if="success"
                                class="alert alert-success d-flex align-items-center"
                            >
                                <i class="bi bi-check-circle me-2"></i>
                                {{ success }}
                            </div>

                            <!-- Actions -->
                            <div
                                class="d-flex justify-content-between align-items-center pt-3 border-top"
                            >
                                <button
                                    type="submit"
                                    class="btn btn-primary px-4"
                                    :disabled="
                                        loading || progress > 0 || !selectedFile
                                    "
                                >
                                    <template v-if="loading">
                                        <span
                                            class="spinner-border spinner-border-sm me-2"
                                        ></span>
                                        Đang tải lên...
                                    </template>
                                    <template v-else>
                                        <i class="bi bi-cloud-upload me-2"></i>
                                        Tải lên
                                    </template>
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary px-4"
                                    @click="closeModal"
                                    :disabled="loading"
                                >
                                    <i class="bi bi-x-circle me-2"></i>Đóng
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import axios from "axios";

// Nhan props tu cha
const props = defineProps({
    documentId: { type: [String, Number], required: true },
});

const emit = defineEmits(["uploaded"]);

// Ref modal chinh
const modalRef = ref(null);

// Instance bootstrap modal
let bsModal = null;

const changeNote = ref("");
const loading = ref(false);
const error = ref(null);
const success = ref(null);
const progress = ref(0);
const selectedFile = ref(null);

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

// Dong modal
const closeModal = () => {
    hideModal();
    resetForm();
};

// Reset form
const resetForm = () => {
    if (fileInput.value) {
        fileInput.value.value = "";
    }
    changeNote.value = "";
    loading.value = false;
    error.value = null;
    success.value = null;
    progress.value = 0;
    selectedFile.value = null;
};

// Xu ly khi chon file
const onFileChange = (event) => {
    const files = event.target.files;
    if (files.length > 0) {
        selectedFile.value = files[0];
        error.value = null;
    }
};

// Xoa file da chon
const removeFile = () => {
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

const submitUpload = async () => {
    if (!selectedFile.value) {
        error.value = "Vui lòng chọn file";
        return;
    }

    const maxSize = 10 * 1024 * 1024;
    const file = selectedFile.value;
    if (file.size > maxSize) {
        error.value = "Dung lượng file không được vượt quá 10MB";
        return;
    }

    loading.value = true;
    error.value = null;
    success.value = null;
    progress.value = 0;

    const formData = new FormData();
    formData.append("file", file);
    formData.append("change_note", changeNote.value);

    try {
        const res = await axios.post(
            `/api/documents/${props.documentId}/versions`,
            formData,
            {
                headers: { "Content-Type": "multipart/form-data" },
                onUploadProgress: (e) => {
                    progress.value = Math.round((e.loaded / e.total) * 100);
                },
            }
        );

        if (res.data.success) {
            success.value = res.data.message;

            emit("uploaded", res.data.data);

            setTimeout(() => {
                closeModal();
            }, 1500);
        } else {
            error.value = res.data.message || "Upload thất bại";
        }
    } catch (e) {
        if (e.response && e.response.data && e.response.data.message) {
            error.value = e.response.data.message;
        } else {
            error.value = "Có lỗi xảy ra khi tải lên";
        }
    } finally {
        loading.value = false;
        progress.value = 0;
    }
};

defineExpose({ showModal, hideModal, closeModal });
</script>
<style scoped>
.border-dashed {
    border: 2px dashed #dee2e6;
    border-radius: 0.75rem;
}

.border-dashed:hover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}

.progress-bar {
    transition: width 0.3s ease;
}

.card {
    transition: all 0.2s ease;
}

.form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.1);
}

.btn {
    border-radius: 0.5rem;
    font-weight: 500;
}

.bg-light {
    background-color: #f8f9fa !important;
}
</style>
