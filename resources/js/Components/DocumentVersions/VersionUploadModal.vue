<template>
    <div>
        <!-- modal upload -->
        <div class="modal fade" ref="modalRef" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <!-- header -->
                    <div class="modal-header btn-light">
                        <h5 class="modal-title">Tải lên phiên bản mới</h5>
                        <button
                            type="button"
                            class="btn-close"
                            @click="closeModal"
                        ></button>
                    </div>

                    <!-- body -->
                    <div class="modal-body">
                        <form @submit.prevent="submitUpload">
                            <div class="mb-3">
                                <label class="form-label">Chọn file</label>
                                <input
                                    type="file"
                                    class="form-control"
                                    ref="fileInput"
                                    required
                                />
                            </div>

                            <div class="mb-3">
                                <label class="form-label"
                                    >Ghi chú thay đổi</label
                                >
                                <textarea
                                    v-model="changeNote"
                                    class="form-control"
                                    rows="3"
                                ></textarea>
                            </div>

                            <!-- Progress bar -->
                            <div v-if="progress > 0" class="mb-2">
                                <div class="progress">
                                    <div
                                        class="progress-bar"
                                        role="progressbar"
                                        :style="{ width: progress + '%' }"
                                        :aria-valuenow="progress"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                    >
                                        {{ progress }}%
                                    </div>
                                </div>
                            </div>

                            <div v-if="error" class="text-danger mb-2">
                                {{ error }}
                            </div>
                            <div v-if="success" class="text-success mb-2">
                                {{ success }}
                            </div>
                        </form>
                    </div>

                    <!-- footer -->
                    <div class="modal-footer bg-light">
                        <!-- action -->
                        <div
                            class="d-flex w-100 justify-content-between align-items-center"
                        >
                            <!-- upload -->
                            <button
                                type="submit"
                                class="btn btn-sm btn-primary"
                                :disabled="loading"
                            >
                                <span
                                    v-if="loading"
                                    class="spinner-border spinner-border-sm me-2"
                                ></span>
                                <i class="bi bi-upload me-2"></i> Tải lên
                            </button>

                            <!-- close -->
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

const fileInput = ref(null);
const changeNote = ref("");
const loading = ref(false);
const error = ref(null);
const success = ref(null);
const progress = ref(0);

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
    fileInput.value.value = null;
    changeNote.value = "";
    loading.value = false;
    error.value = null;
    success.value = null;
    progress.value = 0;
};

const submitUpload = async () => {
    if (!fileInput.value.files.length) {
        error.value = "Vui lòng chọn file";
        return;
    }

    loading.value = true;
    error.value = null;
    success.value = null;
    progress.value = 0;

    const formData = new FormData();
    formData.append("file", fileInput.value.files[0]);
    // console.log("Ghi chú thay đổi:", changeNote.value);
    formData.append("change_note", changeNote.value);

    try {
        const res = await axios.post(
            `/api/documents/${props.documentId}/versions/upload`,
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

            // Emit event để component cha refresh list
            resetForm();
            hideModal();
        } else {
            error.value = res.data.message || "Upload thất bại";
        }
    } catch (e) {
        error.value = "Có lỗi xảy ra khi tải lên";
    } finally {
        loading.value = false;
        progress.value = 0;
    }
};

defineExpose({ showModal, hideModal, closeModal });
</script>
