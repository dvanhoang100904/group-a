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
                    <!-- Filter search -->
                    <DocumentSharedFilter
                        v-model="filters"
                        :users="users"
                        @filter="fetchSharedDocuments(1)"
                        @reset="resetFilters"
                    />
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
                                            v-for="perm in sharedDocument.permissions"
                                            :key="perm"
                                            :class="[
                                                'badge me-1',
                                                permissionMap[perm].badge,
                                            ]"
                                        >
                                            <i
                                                :class="
                                                    permissionMap[perm].icon
                                                "
                                                class="me-1"
                                            ></i>
                                            {{ permissionMap[perm].label }}
                                        </span>
                                    </td>

                                    <td>
                                        {{
                                            formatDate(sharedDocument.shared_at)
                                        }}
                                    </td>
                                    <td>
                                        <button
                                            class="btn border-0 text-primary"
                                            title="Xem chi tiết"
                                            @click="
                                                goToDetail(
                                                    sharedDocument.document_id,
                                                )
                                            "
                                        >
                                            <i class="bi bi-eye"></i>
                                        </button>
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
import DocumentSharedFilter from "./DocumentSharedFilter.vue";

// shared
const sharedDocuments = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 5,
});

const goToDetail = (documentId) => {
    window.location.href = `/documents/${documentId}`;
};

// Loading
const loading = ref(false);

// Filter search
const filters = ref({ keyword: "", user_id: "", from_date: "", to_date: "" });
// Users
const users = ref([]);

// Format date
const formatDate = (dateStr) => new Date(dateStr).toLocaleString("vi-VN");

// PermissionMap
const permissionMap = {
    view: {
        badge: "bg-info-subtle text-info",
        icon: "bi bi-eye",
        label: "Xem",
    },
    upload: {
        badge: "bg-primary-subtle text-primary",
        icon: "bi bi-upload",
        label: "Tải lên",
    },
    download: {
        badge: "bg-success-subtle text-success",
        icon: "bi bi-download",
        label: "Tải xuống",
    },
    edit: {
        badge: "bg-warning-subtle text-warning",
        icon: "bi bi-pencil",
        label: "Chỉnh sửa",
    },
    delete: {
        badge: "bg-danger-subtle text-danger",
        icon: "bi bi-trash",
        label: "Xóa",
    },
    share: {
        badge: "bg-secondary-subtle text-secondary",
        icon: "bi bi-share",
        label: "Chia sẻ tiếp",
    },
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

// Fetch users
const fetchUsers = async () => {
    try {
        const res = await axios.get("/api/shared/users");
        if (res.data.success) {
            users.value = res.data.data;
        } else {
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
            text: "Có lỗi xảy ra. Vui lòng thử lại.",
        });
    }
};

// Fetch document shared
const fetchSharedDocuments = async (page = 1) => {
    loading.value = true;

    try {
        const params = Object.fromEntries(
            Object.entries({
                page,
                keyword: filters.value.keyword,
                user_id: filters.value.user_id,
                from_date: filters.value.from_date,
                to_date: filters.value.to_date,
            }).filter(([_, v]) => v !== "" && v !== undefined),
        );

        const res = await axios.get("/api/shared", { params });

        if (res.data.success) {
            const mappedData = res.data.data.map((doc) => {
                const access =
                    doc.accesses?.sort((a, b) =>
                        b.created_at.localeCompare(a.created_at),
                    )[0] || null;
                const permissions = [];
                if (access) {
                    if (access.can_view) permissions.push("view");
                    if (access.can_upload) permissions.push("upload");
                    if (access.can_download) permissions.push("download");
                    if (access.can_edit) permissions.push("edit");
                    if (access.can_delete) permissions.push("delete");
                    if (access.can_share) permissions.push("share");
                }

                return {
                    ...doc,
                    permissions,
                    shared_by: access ? access.grantedBy?.name : null,
                    shared_at: access ? access.created_at : null,
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

// Reset filter
const resetFilters = () => {
    filters.value = { keyword: "", user_id: "", from_date: "", to_date: "" };
    fetchSharedDocuments(1).then(() =>
        window.scrollTo({ top: 0, behavior: "smooth" }),
    );
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
    await Promise.all([fetchSharedDocuments(), fetchUsers()]);
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
