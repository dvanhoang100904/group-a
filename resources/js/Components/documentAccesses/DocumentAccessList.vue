<template>
    <div>
        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2 text-muted">
                Đang tải danh sách quyền chia sẻ...
            </div>
        </div>
        <div v-else class="card shadow-sm border-0">
            <!-- Header -->
            <div
                class="card-header bg-light d-flex justify-content-between align-items-center"
            >
                <span class="fw-bold">
                    <i class="bi bi-person-check me-2"></i> Danh sách quyền chia
                    sẻ
                </span>
                <!-- Add -->
                <button
                    class="btn btn-sm btn-primary px-3"
                    @click="addModal.showModal()"
                >
                    <i class="bi bi-plus-circle me-1"></i> Thêm mới
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
                                <th class="text-center">#</th>
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
                                        <i
                                            class="bi bi-person text-primary me-1"
                                        ></i>
                                        {{ access.granted_to_user?.name }}
                                    </span>
                                    <span v-else-if="access.granted_to_role">
                                        <i
                                            class="bi bi-people text-primary me-1"
                                        ></i>
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
                                <td class="text-center">
                                    {{
                                        formatDate(access.expiration_date) ||
                                        "-"
                                    }}
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
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <!-- Delete -->
                                    <button
                                        class="btn border-0 text-primary"
                                        title="Xóa"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <AccessPagination
                        :current-page="accesses.current_page"
                        :last-page="accesses.last_page"
                        :max-pages-to-show="7"
                        @page-changed="changePage"
                    />
                </div>

                <AccessAddModal
                    ref="addModal"
                    :document-id="documentId"
                    :users="users"
                    :roles="roles"
                    @added="fetchAccesses"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import AccessPagination from "./AccessPagination.vue";
import AccessAddModal from "./AccessAddModal.vue";

const props = defineProps({
    documentId: { type: [String, Number], required: true },
});

const accesses = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 5,
});

// Loading
const loading = ref(false);

// Add
const addModal = ref(null);

const users = ref([]);

const roles = ref([]);

const fetchUsers = async () => {
    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/accesses/users`,
        );
        if (res.data.success) {
            users.value = res.data.data;
        }
    } catch (err) {
        console.error(err);
    }
};

const fetchRoles = async () => {
    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/accesses/roles`,
        );
        if (res.data.success) {
            roles.value = res.data.data;
        }
    } catch (err) {
        console.error(err);
    }
};

// Format date
const formatDate = (dateStr) =>
    dateStr ? new Date(dateStr).toLocaleDateString("vi-VN") : "-";

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
                data: res.data.data.data || [],
                current_page: res.data.data.current_page || 1,
                last_page: res.data.data.last_page || 1,
                per_page: res.data.data.per_page || 5,
            };
        } else {
            accesses.value = { data: [] };
            alert(res.data.message || "Không thể tải danh sách quyền chia sẻ");
        }
    } catch (err) {
        console.error(err);
        alert("Lỗi hệ thống, vui lòng thử lại!");
    } finally {
        loading.value = false;
    }
};

// Pagination
const changePage = (page) => {
    if (page < 1 || page > accesses.value.last_page) return;
    fetchAccesses(page).then(() =>
        window.scrollTo({ top: 0, behavior: "smooth" }),
    );
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
</style>
