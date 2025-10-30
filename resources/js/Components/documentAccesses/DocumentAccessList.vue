<template>
    <div>
        <!-- LOADING -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2 text-muted">
                Đang tải danh sách quyền chia sẻ...
            </div>
        </div>
        <div v-else class="card shadow-sm border-0">
            <!-- header -->
            <div
                class="card-header bg-light d-flex justify-content-between align-items-center"
            >
                <span class="fw-bold">
                    <i class="bi bi-person-check me-2"></i> Danh sách quyền chia
                    sẻ
                </span>
                <!-- add -->
                <button class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Thêm quyền
                </button>
            </div>

            <div class="card-body">
                <!-- table -->
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
                                <th>#</th>
                                <th>Người dùng</th>
                                <th>Vai trò</th>
                                <th class="text-center">Xem</th>
                                <th class="text-center">Tải xuống</th>
                                <th class="text-center">Chỉnh sửa</th>
                                <th class="text-center">Xóa</th>
                                <th>Ngày hết hạn</th>
                                <th>Người cấp quyền</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(access, index) in accesses.data"
                                :key="access.access_id"
                            >
                                <td>
                                    {{ index + 1 }}
                                </td>
                                <td>
                                    {{ access.granted_to_user?.name || "-" }}
                                </td>
                                <td>
                                    {{ access.granted_to_role?.name || "-" }}
                                </td>
                                <td class="text-center">
                                    <i
                                        :class="
                                            access.can_view
                                                ? 'bi bi-check-circle-fill text-success'
                                                : 'bi bi-x-circle text-muted'
                                        "
                                    ></i>
                                </td>
                                <td class="text-center">
                                    <i
                                        :class="
                                            access.can_download
                                                ? 'bi bi-check-circle-fill text-success'
                                                : 'bi bi-x-circle text-muted'
                                        "
                                    ></i>
                                </td>
                                <td class="text-center">
                                    <i
                                        :class="
                                            access.can_edit
                                                ? 'bi bi-check-circle-fill text-success'
                                                : 'bi bi-x-circle text-muted'
                                        "
                                    ></i>
                                </td>
                                <td class="text-center">
                                    <i
                                        :class="
                                            access.can_delete
                                                ? 'bi bi-check-circle-fill text-success'
                                                : 'bi bi-x-circle text-muted'
                                        "
                                    ></i>
                                </td>
                                <td>
                                    {{
                                        formatDate(access.expiration_date) ||
                                        "-"
                                    }}
                                </td>
                                <td>{{ access.granted_by?.name || "-" }}</td>
                                <!-- action -->
                                <td class="text-center">
                                    <!-- edit -->
                                    <button
                                        class="btn btn-sm btn-outline-primary me-1"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <!-- delete -->
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- pagination -->
                    <nav
                        v-if="accesses.last_page && accesses.last_page > 1"
                        class="mt-3"
                    >
                        <ul class="pagination justify-content-end mb-0">
                            <li
                                class="page-item"
                                :class="{ disabled: !accesses.prev_page_url }"
                            >
                                <button
                                    class="page-link"
                                    @click="
                                        changePage(accesses.current_page - 1)
                                    "
                                >
                                    Trước
                                </button>
                            </li>

                            <li
                                v-for="page in accesses.last_page"
                                :key="page"
                                class="page-item"
                                :class="{
                                    active: page === accesses.current_page,
                                }"
                            >
                                <button
                                    class="page-link"
                                    @click="changePage(page)"
                                >
                                    {{ page }}
                                </button>
                            </li>

                            <li
                                class="page-item"
                                :class="{ disabled: !accesses.next_page_url }"
                            >
                                <button
                                    class="page-link"
                                    @click="
                                        changePage(accesses.current_page + 1)
                                    "
                                >
                                    Sau
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";

const props = defineProps({
    documentId: { type: [String, Number], required: true },
});

const accesses = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    next_page_url: null,
    prev_page_url: null,
});

const loading = ref(true);

// format date
const formatDate = (dateStr) =>
    dateStr ? new Date(dateStr).toLocaleDateString("vi-VN") : "-";

// fetch accesses
const fetchAccesses = async (page = 1) => {
    loading.value = true;
    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/accesses`,
            { params: { page } }
        );
        if (res.data.success) {
            accesses.value = res.data.data;
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

// pagination
const changePage = (page) => {
    if (page < 1 || page > accesses.value.last_page) return;
    fetchAccesses(page).then(() =>
        window.scrollTo({ top: 0, behavior: "smooth" })
    );
};

onMounted(() => fetchAccesses());

watch(
    () => props.documentId,
    () => {
        loading.value = true;
        fetchAccesses();
    }
);
</script>
