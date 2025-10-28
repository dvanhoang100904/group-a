<template>
    <div>
        <!-- LOADING -->
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
                <button
                    class="btn btn-sm btn-primary"
                    @click="uploadModal.showModal()"
                >
                    <i class="bi bi-upload me-1"></i> Tải lên
                </button>
            </div>

            <div class="card-body">
                <!-- filter search -->
                <form
                    class="row g-3 align-items-end mb-3"
                    @submit.prevent="fetchVersions(1)"
                >
                    <div class="col-md-2">
                        <label class="form-label">Từ khóa</label>
                        <input
                            v-model="filters.keyword"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Tìm theo ghi chú hoặc số phiên bản"
                        />
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Người upload</label>
                        <select
                            v-model="filters.user_id"
                            class="form-select form-select-sm"
                        >
                            <option value="">Tất cả</option>
                            <option
                                v-for="u in users"
                                :key="u.id"
                                :value="Number(u.id)"
                            >
                                {{ u.name }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Trạng thái</label>
                        <select
                            v-model="filters.status"
                            class="form-select form-select-sm"
                        >
                            <option value="">Tất cả</option>
                            <option :value="true">Phiên bản hiện tại</option>
                            <option :value="false">Phiên bản cũ</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Từ ngày</label>
                        <input
                            v-model="filters.date_from"
                            type="date"
                            class="form-control form-control-sm"
                        />
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Đến ngày</label>
                        <input
                            v-model="filters.date_to"
                            type="date"
                            class="form-control form-control-sm"
                        />
                    </div>

                    <div class="col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-search me-1"></i> Lọc
                        </button>
                    </div>
                    <div class="col-md-1 d-grid">
                        <button
                            type="button"
                            class="btn btn-secondary btn-sm"
                            @click="resetFilters"
                        >
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </button>
                    </div>
                </form>

                <!-- tables -->
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
                            <tr>
                                <th class="text-center">#</th>
                                <th>Phiên bản</th>
                                <th>Người cập nhật</th>
                                <th>Ngày cập nhật</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(version, index) in versions.data"
                                :key="version.version_id"
                            >
                                <td class="text-center">{{ index + 1 }}</td>
                                <td>
                                    <strong
                                        >v{{ version.version_number }}</strong
                                    >
                                </td>
                                <td>{{ version.user?.name || "Không rõ" }}</td>
                                <td>{{ formatDate(version.created_at) }}</td>
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

                                <!-- action -->
                                <td class="text-center">
                                    <!-- view -->
                                    <button
                                        class="btn btn-sm btn-outline-primary me-1"
                                        title="Xem chi tiết"
                                        @click="
                                            versionIdToShow = version.version_id
                                        "
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- download -->
                                    <button
                                        class="btn btn-sm btn-outline-primary me-1"
                                        :disabled="loading"
                                        title="Tải xuống"
                                        @click="downloadVersion(version)"
                                    >
                                        <i class="bi bi-download"></i>
                                    </button>

                                    <!-- restore -->
                                    <button
                                        class="btn btn-sm btn-outline-primary me-1"
                                        title="Khôi phục phiên bản này"
                                        :disabled="loading"
                                        @click="restoreVersion(version)"
                                    >
                                        <i
                                            class="bi bi-arrow-counterclockwise"
                                        ></i>
                                    </button>

                                    <!-- delete -->
                                    <button
                                        class="btn btn-sm btn-outline-danger"
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

                    <!-- PAGINATION -->
                    <nav
                        v-if="versions.last_page && versions.last_page > 1"
                        class="mt-3"
                    >
                        <ul class="pagination justify-content-end mb-0">
                            <li
                                class="page-item"
                                :class="{ disabled: !versions.prev_page_url }"
                            >
                                <button
                                    class="page-link"
                                    @click="
                                        changePage(versions.current_page - 1)
                                    "
                                >
                                    Trước
                                </button>
                            </li>

                            <li
                                v-for="page in versions.last_page"
                                :key="page"
                                class="page-item"
                                :class="{
                                    active: page === versions.current_page,
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
                                :class="{ disabled: !versions.next_page_url }"
                            >
                                <button
                                    class="page-link"
                                    @click="
                                        changePage(versions.current_page + 1)
                                    "
                                >
                                    Sau
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>

                <!-- Modal detail version-->
                <VersionDetailModal
                    :document-id="documentId"
                    :version-id="versionIdToShow"
                    :format-file-size="formatFileSize"
                    :format-mime-type="formatMimeType"
                    :format-date="formatDate"
                    @update:versionId="versionIdToShow = $event"
                />

                <!-- Modal upload version -->
                <VersionUploadModal
                    ref="uploadModal"
                    :document-id="documentId"
                    :format-file-size="formatFileSize"
                    @uploaded="fetchVersions"
                />

                <!-- Compare version -->
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
import VersionDetailModal from "./VersionDetailModal.vue";
import VersionUploadModal from "./VersionUploadModal.vue";
import DocumentVersionCompare from "./DocumentVersionCompare.vue";

// Nhan props
const props = defineProps({
    documentId: { type: [String, Number], required: true },
});

// Luu danh sach cac phien ban
const versions = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    next_page_url: null,
    prev_page_url: null,
});
const users = ref([]);
// Trang thai loading
const loading = ref(true);
// Detail
const versionIdToShow = ref(null);
// Upload
const uploadModal = ref(null);
// filter search
const filters = ref({
    keyword: "",
    user_id: "",
    status: "",
    date_from: "",
    date_to: "",
});

// format date
const formatDate = (dateStr) => new Date(dateStr).toLocaleString("vi-VN");

// format file size
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

// format mime type
const formatMimeType = (mime) => {
    if (!mime) return "-";
    if (mime.includes("pdf")) return "PDF";
    if (mime.includes("word")) return "Word";
    if (mime.includes("excel")) return "Excel";
    if (mime.includes("image")) return "Hình ảnh";
    return mime;
};

// list users
const fetchUsers = async () => {
    try {
        const res = await axios.get("/api/users");
        if (res.data.success) users.value = res.data.data;
    } catch (e) {
        console.error("Không thể tải danh sách người dùng");
    }
};

// list document versions
const fetchVersions = async (page = 1) => {
    // Bat loading khi bat dau goi api
    loading.value = true;
    try {
        const params = Object.fromEntries(
            Object.entries({
                page,
                keyword: filters.value.keyword,
                user_id: filters.value.user_id,
                status: filters.value.status,
                from_date: filters.value.date_from,
                to_date: filters.value.date_to,
            }).filter(([_, v]) => v !== "")
        );

        // Goi api lay danh sach phien ban cua tai lieu theo id
        const res = await axios.get(
            `/api/documents/${props.documentId}/versions`,
            { params }
        );
        // Luu du lieu tra ve vao state versions
        if (res.data.success) {
            versions.value = res.data.data;
        } else {
            versions.value = { data: [] };
            alert(res.data?.message ?? "Không thể tải danh sách phiên bản");
        }
    } catch (err) {
        console.error(err);
        alert("Lỗi hệ thống, vui lòng thử lại!");
    } finally {
        // Tat loading
        loading.value = false;
    }
};

// download version
const downloadVersion = async (version) => {
    if (!version.file_path) {
        alert("Không tìm thấy đường dẫn file.");
        return;
    }

    if (
        !confirm(
            `Bạn có chắc muốn khôi phục phiên bản #${version.version_number} này không?`
        )
    ) {
        return;
    }

    loading.value = true;

    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/versions/${version.version_id}/download`,
            { responseType: "blob" }
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
        loading.value = false;
    }
};

// restore version
const restoreVersion = async (version) => {
    if (
        !confirm(
            `Bạn có chắc muốn khôi phục phiên bản #${version.version_number} này không?`
        )
    ) {
        return;
    }

    loading.value = true;

    try {
        const res = await axios.post(
            `/api/documents/${props.documentId}/versions/${version.version_id}/restore`
        );
        alert(res.data.message || "Đã khôi phục thành công!");
        // Sau khi khoi phuc reload lai danh sach
        await fetchVersions();
    } catch (err) {
        console.log(err);
        alert(
            err.response?.data?.message ||
                "Không thể khôi phục phiên bản này. Vui lòng thử lại."
        );
    } finally {
        loading.value = false;
    }
};

// delete version
const deleteVersion = async (version) => {
    if (
        !confirm(
            `Bạn có chắc chắn muốn xóa phiên bản #${version.version_number} này không?`
        )
    ) {
        return;
    }

    loading.value = true;

    try {
        const res = await axios.delete(
            `/api/documents/${props.documentId}/versions/${version.version_id}`
        );

        alert(res.data.message || "Đã xóa phiên bản thành công!");

        // Sau khi xoa, reload lai danh sach phien ban
        await fetchVersions();
    } catch (err) {
        console.error(err);
        alert(
            err.response?.data?.message ||
                "Không thể xóa phiên bản này. Vui lòng thử lại."
        );
    } finally {
        loading.value = false;
    }
};

// pagination
const changePage = (page) => {
    if (page < 1 || page > versions.value.last_page) return;
    fetchVersions(page).then(() =>
        window.scrollTo({ top: 0, behavior: "smooth" })
    );
};

// reset filter
const resetFilters = () => {
    filters.value = {
        keyword: "",
        user_id: "",
        status: "",
        date_from: "",
        date_to: "",
    };
    fetchVersions(1).then(() =>
        window.scrollTo({ top: 0, behavior: "smooth" })
    );
};

onMounted(() => {
    fetchVersions();
    fetchUsers();
});

watch(
    () => props.documentId,
    () => {
        loading.value = true;
        fetchVersions();
    }
);
</script>
