<template>
    <div>
        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-2 text-muted">Đang tải danh sách phiên bản...</div>
        </div>
        <div v-else class="card shadow-sm border-0">
            <div
                class="card-header bg-light d-flex justify-content-between align-items-center"
            >
                <span class="fw-bold">
                    <i class="bi bi-list-ul me-1"></i>
                    Danh sách phiên bản
                </span>
                <!-- Upload -->
                <button
                    class="btn btn-sm btn-primary px-3"
                    @click="uploadVersion()"
                >
                    <i class="bi bi-upload me-1"></i> Tải lên
                </button>
            </div>

            <div class="card-body">
                <!-- Filter search -->
                <DocumentVersionFilter
                    v-model="filters"
                    :users="users"
                    @filter="fetchVersions(1)"
                    @reset="resetFilters"
                />

                <!-- Tables -->
                <div
                    v-if="!versions.data || versions.data.length === 0"
                    class="text-center text-muted py-4"
                >
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    Chưa có phiên bản nào
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr class="text-center">
                                <th>STT</th>
                                <th>Phiên bản</th>
                                <th>Người cập nhật</th>
                                <th>Ngày cập nhật</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                class="text-center"
                                v-for="(version, index) in versions.data"
                                :key="version.version_id"
                            >
                                <td>{{ index + 1 }}</td>
                                <td>v{{ version.version_number }}</td>
                                <td>
                                    {{ version.user?.name || "Không rõ" }}
                                </td>
                                <td>
                                    {{ formatDate(version.created_at) }}
                                </td>
                                <td>
                                    <span
                                        v-if="version.is_current_version"
                                        class="badge bg-success-subtle text-success"
                                    >
                                        <i class="bi bi-check-circle me-1"></i>
                                        Hiện tại
                                    </span>
                                    <span
                                        v-else
                                        class="badge bg-secondary-subtle text-secondary"
                                    >
                                        <i class="bi bi-clock-history me-1"></i>
                                        Cũ
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="text-center">
                                    <!-- View -->
                                    <button
                                        class="btn border-0 text-primary"
                                        title="Xem chi tiết"
                                        @click="
                                            detailVersion(version.version_id)
                                        "
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Download -->
                                    <button
                                        class="btn border-0 text-primary"
                                        :disabled="loading"
                                        title="Tải xuống"
                                        @click="downloadVersion(version)"
                                    >
                                        <i class="bi bi-download"></i>
                                    </button>

                                    <!-- Restore -->
                                    <button
                                        class="btn border-0 text-primary"
                                        title="Khôi phục phiên bản này"
                                        :disabled="loading"
                                        @click="restoreVersion(version)"
                                    >
                                        <i
                                            class="bi bi-arrow-counterclockwise"
                                        ></i>
                                    </button>

                                    <!-- Delete -->
                                    <button
                                        class="btn border-0 text-primary"
                                        title="Xóa phiên bản"
                                        :disabled="loading"
                                        @click="deleteVersion(version)"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <DocumentVersionPagination
                        :current-page="versions.current_page"
                        :last-page="versions.last_page"
                        :max-pages-to-show="7"
                        @page-changed="changePage"
                    />
                </div>

                <!-- Version detail modal-->
                <DocumentVersionDetailModal
                    ref="detailModal"
                    :document-id="documentId"
                    :format-file-size="formatFileSize"
                    :format-mime-type="formatMimeType"
                    :format-date="formatDate"
                />

                <!-- Version upload modal -->
                <DocumentVersionUploadModal
                    ref="uploadModal"
                    :document-id="documentId"
                    :format-file-size="formatFileSize"
                    @uploaded="fetchVersions"
                />

                <!-- Version Compare -->
                <DocumentVersionCompare
                    :document-id="props.documentId"
                    :versions="versions.data"
                    :format-file-size="formatFileSize"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import DocumentVersionDetailModal from "./DocumentVersionDetailModal.vue";
import DocumentVersionUploadModal from "./DocumentVersionUploadModal.vue";
import DocumentVersionCompare from "./DocumentVersionCompare.vue";
import DocumentVersionPagination from "./DocumentVersionPagination.vue";
import DocumentVersionFilter from "./DocumentVersionFilter.vue";

const props = defineProps({
    documentId: { type: [String, Number], required: true },
});

// Versions
const versions = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 5,
});

// Users
const users = ref([]);

// Loading
const loading = ref(false);

const loadingActions = ref({});

// Detail
const detailModal = ref(null);

// Upload
const uploadModal = ref(null);

// Filter search
const filters = ref({
    keyword: "",
    user_id: "",
    status: "",
    date_from: "",
    date_to: "",
});

// Format date
const formatDate = (dateStr) => new Date(dateStr).toLocaleString("vi-VN");

// Format file size
const formatFileSize = (bytes) => {
    if (!bytes) return null;
    const sizes = ["B", "KB", "MB", "GB", "TB"];
    let i = 0;
    let size = bytes;
    while (size >= 1024 && i < sizes.length - 1) {
        size /= 1024;
        i++;
    }
    return `${size.toFixed(2)} ${sizes[i]}`;
};

// Format mime type
const formatMimeType = (mime) => {
    if (!mime) return "-";
    if (mime.includes("pdf")) return "PDF";
    if (mime.includes("word")) return "Word";
    if (mime.includes("excel")) return "Excel";
    if (mime.includes("image")) return "Hình ảnh";
    return mime;
};

// Fetch users
const fetchUsers = async () => {
    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/versions/users`,
        );
        if (res.data.success) {
            users.value = res.data.data;
        }
    } catch (err) {
        console.error(err);
    }
};

// Fetch document versions
const fetchVersions = async (page = 1) => {
    loading.value = true;
    try {
        const params = Object.fromEntries(
            Object.entries({
                page,
                keyword: filters.value.keyword,
                user_id: filters.value.user_id,
                status:
                    filters.value.status === ""
                        ? undefined
                        : filters.value.status === true ||
                          filters.value.status === "true",
                from_date: filters.value.date_from,
                to_date: filters.value.date_to,
            }).filter(([_, v]) => v !== undefined && v !== ""),
        );

        const res = await axios.get(
            `/api/documents/${props.documentId}/versions`,
            { params },
        );

        if (res.data.success) {
            versions.value = {
                data: res.data.data || [],
                current_page: res.data.pagination?.current_page || 1,
                last_page: res.data.pagination?.last_page || 1,
                per_page: res.data.pagination?.per_page || 5,
            };
        } else {
            versions.value = {
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 5,
            };
            Swal.fire({
                icon: "error",
                title: "Lỗi",
                text: res.data?.message ?? "Không thể tải danh sách phiên bản",
            });
        }
    } catch (err) {
        console.error(err);
        Swal.fire({
            icon: "error",
            title: "Lỗi hệ thống",
            text: "Vui lòng thử lại!",
        });
    } finally {
        loading.value = false;
    }
};

// Download version
const downloadVersion = async (version) => {
    if (!version.file_path) {
        alert("Không tìm thấy file để tải.");
        return;
    }

    if (
        !confirm(
            `Bạn có chắc muốn tải xuống phiên bản #${version.version_number}?`,
        )
    ) {
        return;
    }

    loadingActions.value[version.version_id] = true;

    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/versions/${version.version_id}/download`,
            { responseType: "blob" },
        );

        // Ten file
        const fileName =
            version.file_name ||
            version.file_path.split("/").pop() ||
            "file_download";

        // Tao link download
        const url = window.URL.createObjectURL(res.data);
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", fileName);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (err) {
        console.error("Lỗi tải file:", err);
        alert("Không thể tải file. Kiểm tra console để biết lỗi chi tiết.");
    } finally {
        loadingActions.value[version.version_id] = false;
    }
};

// Restore version
const restoreVersion = async (version) => {
    if (
        !confirm(
            `Bạn có chắc muốn khôi phục phiên bản #${version.version_number}?`,
        )
    ) {
        return;
    }

    loadingActions.value[version.version_id] = true;

    try {
        const res = await axios.post(
            `/api/documents/${props.documentId}/versions/${version.version_id}/restore`,
        );
        alert(res.data.message || "Đã khôi phục thành công!");
        // Sau khi khoi phuc reload lai danh sach
        await fetchVersions(versions.value.current_page);
    } catch (err) {
        console.log(err);
        alert(
            err.response?.data?.message ||
                "Không thể khôi phục phiên bản này. Vui lòng thử lại.",
        );
    } finally {
        loadingActions.value[version.version_id] = false;
    }
};

// Delete version
const deleteVersion = async (version) => {
    if (
        !confirm(
            `Bạn có chắc chắn muốn xóa phiên bản #${version.version_number}?`,
        )
    ) {
        return;
    }

    loadingActions.value[version.version_id] = true;

    try {
        const res = await axios.delete(
            `/api/documents/${props.documentId}/versions/${version.version_id}`,
        );

        alert(res.data.message || "Đã xóa phiên bản thành công!");

        // Sau khi xoa, reload lai danh sach phien ban
        await fetchVersions(versions.value.current_page);
    } catch (err) {
        console.error(err);
        alert(
            err.response?.data?.message ||
                "Không thể xóa phiên bản này. Vui lòng thử lại.",
        );
    } finally {
        loadingActions.value[version.version_id] = false;
    }
};

// Pagination
const changePage = (page) => {
    if (page < 1 || page > versions.value.last_page) return;
    fetchVersions(page).then(() =>
        window.scrollTo({ top: 0, behavior: "smooth" }),
    );
};

// Reset filter
const resetFilters = () => {
    filters.value = {
        keyword: "",
        user_id: "",
        status: "",
        date_from: "",
        date_to: "",
    };
    fetchVersions(1).then(() =>
        window.scrollTo({ top: 0, behavior: "smooth" }),
    );
};

// Detail version
const detailVersion = (versionId) => {
    if (detailModal.value) {
        detailModal.value.showModalVersion(versionId);
    }
};

// Upload version
const uploadVersion = () => {
    if (uploadModal.value) {
        uploadModal.value.showModal();
    }
};

onMounted(async () => {
    loading.value = true;
    await Promise.all([fetchVersions(), fetchUsers()]);
    loading.value = false;
});

watch(
    () => props.documentId,
    async () => {
        loading.value = true;
        await Promise.all([fetchVersions(), fetchUsers()]);
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
