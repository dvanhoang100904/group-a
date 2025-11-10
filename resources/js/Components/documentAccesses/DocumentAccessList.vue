<template>
    <div>
        <!-- Loading -->
        <transition name="fade">
            <div
                v-if="loading"
                class="text-center py-5"
                style="min-height: 460px"
            >
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">
                    Đang tải danh sách quyền chia sẻ...
                </div>
            </div>
        </transition>
        <!-- Loading -->
        <transition name="fade">
            <div v-if="!loading" class="card shadow-sm border-0">
                <!-- Header -->
                <div
                    class="card-header bg-light d-flex justify-content-between align-items-center"
                >
                    <span class="fw-bold">
                        <i class="bi bi-person-check me-2"></i> Danh sách quyền
                        chia sẻ
                    </span>
                    <!-- Add -->
                    <button
                        class="btn btn-sm btn-primary px-3"
                        @click="addAccess()"
                    >
                        <i class="bi bi-plus-circle me-1"></i> Thêm quyền
                    </button>
                </div>

                <div class="card-body">
                    <!-- Tables -->
                    <div
                        v-if="!accesses.data || accesses.data.length === 0"
                        class="text-center text-muted py-4"
                    >
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        Chưa có quyền chia sẻ nào
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th class="text-center">STT</th>
                                    <th class="text-center">
                                        Đối tượng được chia sẻ
                                    </th>
                                    <th class="text-center">Xem</th>
                                    <th class="text-center">Tải lên</th>
                                    <th class="text-center">Tải xuống</th>
                                    <th class="text-center">Chỉnh sửa</th>
                                    <th class="text-center">Xóa</th>
                                    <th class="text-center">Chia sẻ tiếp</th>
                                    <th class="text-center">Ngày hết hạn</th>
                                    <th class="text-center">Người cấp quyền</th>
                                    <th class="text-center">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(access, index) in accesses.data"
                                    :key="access.access_id"
                                >
                                    <td class="text-center">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="text-center">
                                        <span v-if="access.granted_to_user">
                                            Người dùng:
                                            {{ access.granted_to_user?.name }}
                                        </span>
                                        <span
                                            v-else-if="access.granted_to_role"
                                        >
                                            Vai trò:
                                            {{ access.granted_to_role?.name }}
                                        </span>
                                        <span v-else>-</span>
                                    </td>
                                    <!-- View -->
                                    <td class="text-center">
                                        <i
                                            :class="
                                                access.can_view
                                                    ? 'bi bi-check-circle-fill text-success'
                                                    : 'bi bi-x-circle text-muted'
                                            "
                                        ></i>
                                    </td>

                                    <!-- Upload -->
                                    <td class="text-center">
                                        <i
                                            :class="
                                                access.can_upload
                                                    ? 'bi bi-check-circle-fill text-success'
                                                    : 'bi bi-x-circle text-muted'
                                            "
                                        ></i>
                                    </td>

                                    <!-- Download -->
                                    <td class="text-center">
                                        <i
                                            :class="
                                                access.can_download
                                                    ? 'bi bi-check-circle-fill text-success'
                                                    : 'bi bi-x-circle text-muted'
                                            "
                                        ></i>
                                    </td>

                                    <!-- Edit -->
                                    <td class="text-center">
                                        <i
                                            :class="
                                                access.can_edit
                                                    ? 'bi bi-check-circle-fill text-success'
                                                    : 'bi bi-x-circle text-muted'
                                            "
                                        ></i>
                                    </td>

                                    <!-- Delete -->
                                    <td class="text-center">
                                        <i
                                            :class="
                                                access.can_delete
                                                    ? 'bi bi-check-circle-fill text-success'
                                                    : 'bi bi-x-circle text-muted'
                                            "
                                        ></i>
                                    </td>

                                    <!-- Share -->
                                    <td class="text-center">
                                        <i
                                            :class="
                                                access.can_share
                                                    ? 'bi bi-check-circle-fill text-success'
                                                    : 'bi bi-x-circle text-muted'
                                            "
                                        ></i>
                                    </td>

                                    <!-- No expiry -->
                                    <td class="text-center">
                                        <span
                                            v-if="access.no_expiry"
                                            class="badge bg-success-subtle text-success"
                                        >
                                            <i
                                                class="bi bi-infinity text-success me-1"
                                            ></i>
                                            Không giới hạn
                                        </span>
                                        <span v-else>
                                            {{
                                                formatDate(
                                                    access.expiration_date,
                                                ) || "-"
                                            }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        {{ access.granted_by?.name || "-" }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-center">
                                        <!-- Edit -->
                                        <button
                                            class="btn border-0 text-primary"
                                            title="Chỉnh sửa"
                                            @click="updateAccess(access)"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <!-- Delete -->
                                        <button
                                            class="btn border-0 text-primary"
                                            title="Xóa"
                                            @click="deleteAccess(access)"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <DocumentAccessPagination
                            :current-page="accesses.current_page"
                            :last-page="accesses.last_page"
                            :max-pages-to-show="5"
                            @page-changed="changePage"
                        />
                    </div>

                    <!-- Add modal -->
                    <DocumentAccessAddModal
                        ref="addModal"
                        :document-id="documentId"
                        :users="users"
                        :roles="roles"
                        @added="fetchAccesses"
                    />

                    <!-- Update modal -->
                    <DocumentAccessUpdateModal
                        ref="updateModal"
                        :document-id="documentId"
                        :access="selectedAccess"
                        :users="users"
                        :roles="roles"
                        @updated="fetchAccesses"
                    />
                </div></div
        ></transition>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import DocumentAccessPagination from "./DocumentAccessPagination.vue";
import DocumentAccessAddModal from "./DocumentAccessAddModal.vue";
import DocumentAccessUpdateModal from "./DocumentAccessUpdateModal.vue";

// Props
const props = defineProps({
    documentId: { type: [String, Number], required: true },
});

// Accesses
const accesses = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 5,
});

// Loading
const loading = ref(false);

// Loading actions
const loadingActions = ref({});

// Add modal
const addModal = ref(null);

// Selected access
const selectedAccess = ref(null);

// Update modal
const updateModal = ref(null);

// Users
const users = ref([]);

// Roles
const roles = ref([]);

// Url
const url = `/documents`;

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

// Fetch Users
const fetchUsers = async () => {
    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/accesses/users`,
        );
        if (res.data.success) {
            users.value = res.data.data;
        } else {
            await showSwal({
                icon: "error",
                title: "Lỗi",
                text: res.data.message,
            });

            if (res.data.message?.includes("Tài liệu không tồn tại")) {
                window.location.href = url;
                return;
            }
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

// Fetch roles
const fetchRoles = async () => {
    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/accesses/roles`,
        );
        if (res.data.success) {
            roles.value = res.data.data;
        } else {
            await showSwal({
                icon: "error",
                title: "Lỗi",
                text: res.data.message,
            });

            if (res.data.message?.includes("Tài liệu không tồn tại")) {
                window.location.href = url;
            }
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

// Fetch accesses
const fetchAccesses = async (page = 1) => {
    loading.value = true;
    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/accesses`,
            { params: { page } },
        );

        if (res.data.success) {
            accesses.value = {
                data: res.data.data || [],
                current_page: res.data.pagination?.current_page || 1,
                last_page: res.data.pagination?.last_page || 1,
                per_page: res.data.pagination?.per_page || 5,
            };
        } else {
            accesses.value = {
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

            if (res.data.message?.includes("Tài liệu không tồn tại")) {
                window.location.href = url;
                return;
            }
        }
    } catch (err) {
        console.error(err);
        await showSwal({
            icon: "error",
            title: "Lỗi hệ thống",
            text: "Có lỗi xảy ra. vui lòng thử lại.",
        });
    } finally {
        loading.value = false;
    }
};

// Delete access
const deleteAccess = async (access) => {
    const confirmResult = await showSwal({
        icon: "warning",
        title: "Xác nhận xóa?",
        text: `Bạn có chắc chắn muốn xóa quyền của "${
            access.granted_to_user?.name ||
            access.granted_to_role?.name ||
            "Không xác định"
        }"? Thao tác này không thể khôi phục.`,
        showCancelButton: true,
        confirmButtonText: "Xóa",
        confirmButtonColor: "#dc3545",
    });

    if (!confirmResult.isConfirmed) return;

    loadingActions.value[access.access_id] = true;

    Swal.fire({
        title: "Đang xóa...",
        text: "Vui lòng chờ",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    try {
        const res = await axios.delete(
            `/api/documents/${props.documentId}/accesses/${access.access_id}`,
        );

        if (res.data.success) {
            await showSwal({
                icon: "success",
                title: "Xóa thành công",
                text: res.data.message,
                timer: 2000,
            });
            await fetchAccesses(accesses.value.current_page);
        } else {
            await showSwal({
                icon: "error",
                title: "Lỗi",
                text: res.data.message,
            });

            if (res.data.message?.includes("Tài liệu không tồn tại")) {
                window.location.href = url;
                return;
            }
        }

        Swal.close();
    } catch (err) {
        console.error(err);
        Swal.close();
        await showSwal({
            icon: "error",
            title: "Lỗi hệ thống",
            text: "Có lỗi xảy ra. Vui lòng thử lại",
        });
    } finally {
        loadingActions.value[access.access_id] = false;
    }
};

// Update access
const updateAccess = (access) => {
    selectedAccess.value = { ...access };
    nextTick(() => updateModal.value.showModal());
};

// Format date
const formatDate = (dateStr) =>
    dateStr ? new Date(dateStr).toLocaleDateString("vi-VN") : "-";

// Change Page
const changePage = (page) => {
    if (page < 1 || page > accesses.value.last_page) return;
    fetchAccesses(page).then(() =>
        window.scrollTo({ top: 0, behavior: "smooth" }),
    );
};

// Add access
const addAccess = () => {
    if (addModal.value) {
        addModal.value.showModal();
    }
};

onMounted(async () => {
    loading.value = true;
    await Promise.all([fetchAccesses(), fetchUsers(), fetchRoles()]);
    loading.value = false;
});

watch(
    () => props.documentId,
    async () => {
        loading.value = true;
        await Promise.all([fetchAccesses(), fetchUsers(), fetchRoles()]);
        loading.value = false;
    },
);
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
