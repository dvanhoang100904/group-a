<template>
    <div class="modal fade" ref="modalRef" tabindex="-1" aria-hidden="true">
        <div
            class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-sm-down"
        >
            <div class="modal-content">
                <!-- Header -->
                <div
                    class="modal-header bg-light d-flex align-items-center justify-content-between"
                >
                    <div class="d-flex align-items-center">
                        <div class="icon-wrapper bg-primary rounded p-2 me-3">
                            <i class="bi bi-file-earmark-text text-white"></i>
                        </div>
                        <h5 class="modal-title mb-0">Xem trước tài liệu</h5>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        @click="closeModal"
                    ></button>
                </div>

                <!-- Toolbar -->
                <div
                    v-if="previewUrl"
                    class="px-3 py-2 border-bottom d-flex justify-content-end align-items-center"
                >
                    <div>
                        <button
                            class="btn btn-sm btn-primary me-3 px-3"
                            @click="zoomIn"
                        >
                            <i class="bi bi-zoom-in me-2"></i> Phóng to
                        </button>
                        <button
                            class="btn btn-sm btn-primary me-3 px-3"
                            @click="zoomOut"
                        >
                            <i class="bi bi-zoom-out me-2"></i> Thu nhỏ
                        </button>
                        <button
                            class="btn btn-sm btn-secondary px-3"
                            @click="resetZoom"
                        >
                            <i class="bi bi-arrows-angle-contract me-2"></i>
                            Đặt lại
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div
                    class="modal-body p-0"
                    :style="{ height: bodyHeight + 'px' }"
                >
                    <div v-if="loading" class="text-center py-5">
                        <div
                            class="spinner-border text-primary"
                            role="status"
                        ></div>
                        <p class="mt-2">Đang tải preview...</p>
                    </div>

                    <div v-else class="iframe-wrapper">
                        <iframe
                            ref="iframeRef"
                            :src="previewUrl"
                            frameborder="0"
                            allowfullscreen
                            style="width: 100%; height: 100%"
                        ></iframe>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer bg-light">
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
import { ref, nextTick, onMounted, onBeforeUnmount } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

const props = defineProps({
    documentId: Number,
});

const modalRef = ref(null);

const iframeRef = ref(null);

let bsModal = null;

const loading = ref(false);

const previewUrl = ref(null);

const zoomLevel = ref(1);

const bodyHeight = ref(window.innerHeight * 0.7);

const showModal = () => {
    if (!bsModal) bsModal = new bootstrap.Modal(modalRef.value);
    bsModal.show();
};

const hideModal = () => {
    if (bsModal) {
        bsModal.hide();
        bsModal = null;
    }
};

const closeModal = () => {
    hideModal();
    previewUrl.value = null;
    zoomLevel.value = 1;
};

// Form message
const showSwal = ({
    icon,
    title,
    text,
    showCancelButton = false,
    confirmButtonText = "Đồng ý",
    confirmButtonColor = "#0d6efd",
    cancelButtonText = "Hủy",
    cancelButtonColor = "#6c757d",
}) => {
    return Swal.fire({
        icon,
        title,
        text,
        showCancelButton,
        confirmButtonText,
        confirmButtonColor,
        cancelButtonText,
        cancelButtonColor,
    });
};

const showPreviewVersion = async (versionId) => {
    if (!versionId || !props.documentId) return;

    showModal();
    loading.value = true;
    previewUrl.value = null;

    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/versions/${versionId}/preview`,
        );
        if (res.data.success && res.data.data?.preview_path) {
            previewUrl.value = res.data.data.preview_path;
            nextTick(() => resetZoom());
        } else {
            hideModal();
            await showSwal({
                icon: "error",
                title: "Lỗi",
                text: res.data.message || "Không thể tải preview.",
            });
        }
    } catch (e) {
        console.error(e);
        hideModal();
        await showSwal({
            icon: "error",
            title: "Lỗi hệ thống",
            text: "Đã xảy ra lỗi khi tải preview. Vui lòng thử lại!",
        });
    } finally {
        loading.value = false;
    }
};

// Zoom functions
const applyZoom = () => {
    if (iframeRef.value) {
        iframeRef.value.style.transform = `scale(${zoomLevel.value})`;
        iframeRef.value.style.transformOrigin = "top left";
        iframeRef.value.style.width = `${100 / zoomLevel.value}%`;
        iframeRef.value.style.height = `${100 / zoomLevel.value}%`;
    }
};

const zoomIn = () => {
    zoomLevel.value += 0.1;
    applyZoom();
};
const zoomOut = () => {
    zoomLevel.value = Math.max(0.1, zoomLevel.value - 0.1);
    applyZoom();
};
const resetZoom = () => {
    zoomLevel.value = 1;
    applyZoom();
};

// Update body height on window resize
const updateBodyHeight = () => {
    bodyHeight.value = window.innerHeight * 0.7;
};
onMounted(() => window.addEventListener("resize", updateBodyHeight));

onBeforeUnmount(() => window.removeEventListener("resize", updateBodyHeight));

defineExpose({ showModal, hideModal, closeModal, showPreviewVersion });
</script>

<style scoped>
.preview-wrapper {
    overflow: auto;
    width: 100%;
    height: 100%;
}

.iframe-wrapper {
    width: 100%;
    height: 100%;
    overflow: auto;
}

.btn {
    border-radius: 0.5rem;
    font-weight: 500;
}

.bg-light {
    background-color: #f8fafd !important;
}

.icon-wrapper {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
