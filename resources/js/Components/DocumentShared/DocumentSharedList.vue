<template>
    <div>
        <!-- Loading -->
        <transition name="fade">
            <div
                v-if="loading"
                class="text-center py-5"
                style="min-height: 520px"
            >
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2 text-muted">
                    Đang tải danh sách tài liệu được chia sẻ với tôi...
                </div>
            </div>
        </transition>
        <transition name="fade">
            <div v-if="!loading" class="card shadow-sm border-0 py-1">
                <div class="card-body">
                    <!-- Tables -->
                    <div
                        v-if="
                            !sharedDocuments.data ||
                            sharedDocuments.data.length === 0
                        "
                        class="text-center text-muted py-4"
                    >
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        Chưa có tài liệu nào được chia sẻ với bạn
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-primary">
                                <tr class="text-center">
                                    <th>STT</th>
                                    <th>Tiêu đề</th>
                                    <th>Người chia sẻ</th>
                                    <th>Quyền được cấp</th>
                                    <th>Ngày chia sẻ</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="(
                                        sharedDocument, index
                                    ) in sharedDocuments.data"
                                    :key="sharedDocument.document_id"
                                    class="text-center"
                                >
                                    <td>{{ index + 1 }}</td>
                                    <td>
                                        {{ sharedDocument.title }}
                                    </td>
                                    <td>
                                        {{
                                            sharedDocument.shared_by?.name ||
                                            "Không rõ"
                                        }}
                                    </td>
                                    <td>
                                        <span
                                            v-if="
                                                sharedDocument.permission ===
                                                'view'
                                            "
                                            class="badge bg-info-subtle text-info"
                                        >
                                            <i class="bi bi-eye me-1"></i> Xem
                                        </span>
                                        <span
                                            v-else-if="
                                                sharedDocument.permission ===
                                                'upload'
                                            "
                                            class="badge bg-primary-subtle text-primary"
                                        >
                                            <i class="bi bi-upload me-1"></i>
                                            Tải lên
                                        </span>
                                        <span
                                            v-else-if="
                                                sharedDocument.permission ===
                                                'download'
                                            "
                                            class="badge bg-success-subtle text-success"
                                        >
                                            <i class="bi bi-download me-1"></i>
                                            Tải xuống
                                        </span>
                                        <span
                                            v-else-if="
                                                sharedDocument.permission ===
                                                'edit'
                                            "
                                            class="badge bg-warning-subtle text-warning"
                                        >
                                            <i class="bi bi-pencil me-1"></i>
                                            Chỉnh sửa
                                        </span>
                                        <span
                                            v-else-if="
                                                sharedDocument.permission ===
                                                'delete'
                                            "
                                            class="badge bg-danger-subtle text-danger"
                                        >
                                            <i class="bi bi-trash me-1"></i> Xóa
                                        </span>
                                        <span
                                            v-else-if="
                                                sharedDocument.permission ===
                                                'share'
                                            "
                                            class="badge bg-secondary-subtle text-secondary"
                                        >
                                            <i class="bi bi-share me-1"></i>
                                            Chia sẻ tiếp
                                        </span>
                                        <span
                                            v-else
                                            class="badge bg-secondary-subtle text-secondary"
                                        >
                                            <i
                                                class="bi bi-question-circle me-1"
                                            ></i>
                                            Khác
                                        </span>
                                    </td>

                                    <td>
                                        {{
                                            formatDate(sharedDocument.shared_at)
                                        }}
                                    </td>
                                    <td>
                                        <!-- Detail  -->
                                        <button
                                            class="btn border-0 text-primary"
                                            title="Xem chi tiết tài liệu"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <!-- Download -->
                                        <button
                                            v-if="
                                                ['view', 'edit'].includes(
                                                    sharedDocument.permission,
                                                )
                                            "
                                            class="btn border-0 text-primary"
                                            title="Tải xuống"
                                        >
                                            <i class="bi bi-download"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <DocumentSharedPagination
                            :current-page="sharedDocuments.current_page"
                            :last-page="sharedDocuments.last_page"
                            :max-pages-to-show="5"
                            @page-changed="changePage"
                        />
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import DocumentSharedPagination from "./DocumentSharedPagination.vue";

// shared
const sharedDocuments = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 5,
});

// Loading
const loading = ref(false);

// Format date
const formatDate = (dateStr) => new Date(dateStr).toLocaleString("vi-VN");

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
    timer,
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
        timer,
        showConfirmButton: !timer,
        allowOutsideClick: !timer,
    });
};

// Fetch document shared
const fetchSharedDocuments = async (page = 1) => {
    loading.value = true;

    try {
        const res = await axios.get("/api/shared", { params: { page } });

        if (res.data.success) {
            const mappedData = res.data.data.map((documents) => {
                const access = documents.accesses?.[0] || null;

                let permission = "other";
                if (access) {
                    if (access.can_view) permission = "view";
                    else if (access.can_upload) permission = "upload";
                    else if (access.can_download) permission = "download";
                    else if (access.can_edit) permission = "edit";
                    else if (access.can_delete) permission = "delete";
                    else if (access.can_share) permission = "share";
                }

                return {
                    ...documents,
                    permission,
                    shared_by: access?.grantedBy || null,
                    shared_at: access?.created_at || null,
                };
            });

            sharedDocuments.value = {
                data: mappedData,
                current_page: res.data.pagination?.current_page || 1,
                last_page: res.data.pagination?.last_page || 1,
                per_page: res.data.pagination?.per_page || 5,
            };
        } else {
            sharedDocuments.value = {
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 5,
            };

            await showSwal({
                icon: "error",
                title: "Lỗi",
                text: res.data.message,
            });
        }
    } catch (err) {
        console.error(err);
        await showSwal({
            icon: "error",
            title: "Lỗi hệ thống",
            text: "Có lỗi xảy ra. Vui lòng thử lại!",
        });
    } finally {
        loading.value = false;
    }
};

// Pagination
const changePage = (page) => {
    if (page < 1 || page > sharedDocuments.value.last_page) return;
    fetchSharedDocuments(page).then(() =>
        window.scrollTo({ top: 0, behavior: "smooth" }),
    );
};

onMounted(async () => {
    loading.value = true;
    await Promise.all([fetchSharedDocuments()]);
    loading.value = false;
});
</script>

<style scoped>
.btn {
    border-radius: 0.5rem;
    font-weight: 500;
}

.card {
    transition: all 0.2s ease;
}

.bg-light {
    background-color: #f8fafd !important;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
