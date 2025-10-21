<template>
    <div>
        <!-- loading -->
        <div v-if="loading" class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div
                class="card-header bg-light d-flex justify-content-between align-items-center"
            >
                <span class="fw-bold"
                    ><i class="bi bi-list-ul me-1"></i> Danh sách các phiên
                    bản</span
                >
                <div class="d-flex justify-content-end mb-1">
                    <!-- upload -->
                    <button
                        class="btn btn-sm btn-primary"
                        @click="uploadModal.showModal()"
                    >
                        <i class="bi bi-upload me-2"></i> Tải lên
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <!-- tables -->
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
                                        <i class="bi bi-check-circle me-1"></i
                                        >Hiện tại
                                    </span>
                                    <span
                                        v-else
                                        class="badge bg-secondary-subtle text-secondary"
                                    >
                                        <i class="bi bi-clock-history me-1"></i
                                        >Cũ
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="text-center">
                                    <!-- View -->
                                    <button
                                        class="btn btn-sm btn-outline-primary me-1"
                                        title="Xem chi tiết"
                                        @click="showVersionDetail(version)"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Download -->
                                    <button
                                        class="btn btn-sm btn-outline-primary me-1"
                                        title="Tải xuống"
                                    >
                                        <i class="bi bi-download"></i>
                                    </button>

                                    <!-- Restore -->
                                    <button
                                        class="btn btn-sm btn-outline-primary me-1"
                                        title="Khôi phục phiên bản này"
                                    >
                                        <i
                                            class="bi bi-arrow-counterclockwise"
                                        ></i>
                                    </button>

                                    <!-- Delete -->
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        title="Xóa phiên bản"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- No data -->
                            <tr v-if="versions.data.length === 0">
                                <td
                                    colspan="9"
                                    class="text-center text-muted py-4"
                                >
                                    Chưa có phiên bản nào
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- pagination -->
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

                    <!-- modal detail version -->
                    <VersionDetailModal
                        v-model:selected-version="selectedVersion"
                        :format-file-size="formatFileSize"
                        :format-mime-type="formatMimeType"
                        :format-date="formatDate"
                    />

                    <!-- modal upload version -->
                    <VersionUploadModal
                        ref="uploadModal"
                        :document-id="documentId"
                        @uploaded="fetchVersions"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import VersionDetailModal from "./VersionDetailModal.vue";
import VersionUploadModal from "./VersionUploadModal.vue";

// Nhan props
const props = defineProps({
    documentId: { type: [String, Number], required: true },
});

// Luu danh sach cac phien ban
const versions = ref({ data: [] });
// Trang thai loading
const loading = ref(false);
// Phien ban duoc chon
const selectedVersion = ref(null);
// Upload
const uploadModal = ref(null);

// list document versions
const fetchVersions = async (page = 1) => {
    // Bat loading khi bat dau goi api
    loading.value = true;
    try {
        // Goi api lay danh sach phien ban cua tai lieu theo id
        const res = await axios.get(
            `/api/documents/${props.documentId}/versions?page=${page}`
        );
        // Luu du lieu tra ve vao state versions
        if (res.data.success) {
            versions.value = res.data.data;
        } else {
            alert(res.data.message ?? "Thông báo tồn tại");
        }
    } catch (error) {
        alert("Lỗi hệ thống, vui lòng thử lại!");
    } finally {
        // Tat loading
        loading.value = false;
    }
};

// pagination
const changePage = (page) => {
    if (page < 1 || page > versions.value.last_page) return;
    fetchVersions(page);
};

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

// modal detail version
const showVersionDetail = (version) => {
    // Luu phien ban duoc chon vao state de hien trong modal
    selectedVersion.value = version;
};

onMounted(() => {
    fetchVersions();
});
</script>
