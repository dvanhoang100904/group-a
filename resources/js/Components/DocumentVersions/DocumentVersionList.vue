<template>
    <div>
        <!-- Table -->
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="text-center">#</th>
                    <th>Phiên bản</th>
                    <th>Ghi chú thay đổi</th>
                    <th>Người cập nhật</th>
                    <th>Ngày cập nhật</th>
                    <th>Kích thước</th>
                    <th>Loại file</th>
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
                        <strong>v{{ version.version_number }}</strong>
                    </td>
                    <td>{{ version.change_note || "-" }}</td>
                    <td>{{ version.user?.name || "Không rõ" }}</td>
                    <td>{{ formatDate(version.created_at) }}</td>
                    <td>{{ formatFileSize(version.file_size) || "—" }}</td>
                    <td>{{ formatMimeType(version.mime_type) }}</td>
                    <td>
                        <span
                            v-if="version.is_current_version"
                            class="badge bg-success-subtle text-success"
                        >
                            <i class="bi bi-check-circle me-1"></i>Hiện tại
                        </span>
                        <span
                            v-else
                            class="badge bg-secondary-subtle text-secondary"
                        >
                            <i class="bi bi-clock-history me-1"></i>Cũ
                        </span>
                    </td>

                    <!-- Actions -->
                    <td class="text-center">
                        <!-- View -->
                        <button
                            class="btn btn-sm btn-outline-primary me-1"
                            title="Xem chi tiết"
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
                            <i class="bi bi-arrow-counterclockwise"></i>
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
                    <td colspan="9" class="text-center text-muted py-4">
                        Không có phiên bản nào
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav v-if="versions.last_page && versions.last_page > 1" class="mt-3">
            <ul class="pagination justify-content-end mb-0">
                <li
                    class="page-item"
                    :class="{ disabled: !versions.prev_page_url }"
                >
                    <button
                        class="page-link"
                        @click="changePage(versions.current_page - 1)"
                    >
                        Trước
                    </button>
                </li>

                <li
                    v-for="page in versions.last_page"
                    :key="page"
                    class="page-item"
                    :class="{ active: page === versions.current_page }"
                >
                    <button class="page-link" @click="changePage(page)">
                        {{ page }}
                    </button>
                </li>

                <li
                    class="page-item"
                    :class="{ disabled: !versions.next_page_url }"
                >
                    <button
                        class="page-link"
                        @click="changePage(versions.current_page + 1)"
                    >
                        Sau
                    </button>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const props = defineProps({
    documentId: { type: [String, Number], required: true },
});

const versions = ref({ data: [] });
const loading = ref(false);

const fetchVersions = async (page = 1) => {
    loading.value = true;
    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/versions?page=${page}`
        );
        versions.value = res.data;
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page < 1 || page > versions.value.last_page) return;
    fetchVersions(page);
};

const formatDate = (dateStr) => new Date(dateStr).toLocaleString("vi-VN");

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

const formatMimeType = (mime) => {
    if (!mime) return "-";
    if (mime.includes("pdf")) return "PDF";
    if (mime.includes("word")) return "Word";
    if (mime.includes("excel")) return "Excel";
    if (mime.includes("image")) return "Hình ảnh";
    return mime;
};

onMounted(() => fetchVersions());
</script>
