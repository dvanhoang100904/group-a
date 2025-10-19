<template>
    <!-- modal preview file -->
    <div
        class="modal fade"
        id="filePreviewModal"
        tabindex="-1"
        aria-labelledby="filePreviewModalLabel"
        aria-hidden="true"
    >
        <div
            class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"
        >
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filePreviewModalLabel">
                        Xem trước tài liệu
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Đóng"
                    ></button>
                </div>

                <div class="modal-body text-center">
                    <!-- Loading -->
                    <div v-if="loading" class="text-muted">Đang tải...</div>

                    <!-- PDF -->
                    <iframe
                        v-else-if="isPreviewable && mimeType.includes('pdf')"
                        :src="previewUrl"
                        width="100%"
                        height="600px"
                        style="border: none"
                    ></iframe>

                    <!-- Image -->
                    <img
                        v-else-if="isPreviewable && mimeType.includes('image')"
                        :src="previewUrl"
                        class="img-fluid rounded"
                        alt="Xem trước tài liệu"
                    />

                    <!-- Không hỗ trợ -->
                    <div v-else class="text-danger">
                        Không thể xem trước định dạng này.
                        <br />
                        <a
                            class="btn btn-outline-primary mt-3"
                            :href="previewUrl"
                            target="_blank"
                        >
                            <i class="bi bi-download me-1"></i> Tải xuống file
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
const props = defineProps({
    loading: Boolean,
    isPreviewable: Boolean,
    mimeType: String,
    previewUrl: String,
});
</script>
