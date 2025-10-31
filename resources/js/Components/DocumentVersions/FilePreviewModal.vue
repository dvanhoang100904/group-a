<template>
    <div class="modal fade" ref="modalRef" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xem trước tài liệu</h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="closeModal"
                    ></button>
                </div>

                <div class="modal-body p-0" style="min-height: 500px">
                    <div v-if="loading" class="text-center py-5">
                        <div
                            class="spinner-border text-primary"
                            role="status"
                        ></div>
                        <p class="mt-2">Đang tải preview...</p>
                    </div>

                    <div v-else-if="error" class="text-center py-5 text-danger">
                        <p>{{ error }}</p>
                    </div>

                    <iframe
                        v-else
                        :src="previewUrl"
                        style="width: 100%; height: 80vh"
                        frameborder="0"
                        allowfullscreen
                    ></iframe>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary px-3"
                        @click="closeModal"
                    >
                        <i class="bi bi-x-circle me-2"></i>Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import axios from "axios";

// Nhan props tu cha
const props = defineProps({
    versionId: Number,
    documentId: Number,
});
// ref modal chinh
const modalRef = ref(null);
// instance Bootstrap modal
let bsModal = null;

// Trang thai loading
const loading = ref(false);
// Luu thong bao loi
const error = ref(null);
// URL file preview PDF/Doc/PDF da convert
const previewUrl = ref(null);

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
    previewUrl.value = null;
    error.value = null;
};

watch(
    () => props.versionId,
    async (versionId) => {
        if (!versionId || !props.documentId) return;

        // Hien thi modal truoc khi load
        showModal();
        loading.value = true;
        error.value = null;
        previewUrl.value = null;

        try {
            const res = await axios.get(
                `/api/documents/${props.documentId}/versions/${versionId}/preview`
            );
            if (res.data.success && res.data.data?.preview_path) {
                previewUrl.value = res.data.data.preview_path;
            } else {
                error.value = res.data.message || "Không thể tải preview";
            }
        } catch (e) {
            error.value = "Lỗi khi tải preview";
        } finally {
            loading.value = false;
        }
    }
);

defineExpose({ showModal, hideModal, closeModal });
</script>

<style scoped>
.btn {
    border-radius: 0.5rem;
    font-weight: 500;
}

.bg-light {
    background-color: #f8fafd !important;
}
</style>
