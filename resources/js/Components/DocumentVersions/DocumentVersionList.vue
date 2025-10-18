<template>
    <div>
        <div v-if="loading" class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- table -->
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
                        <strong>v{{ version.version_number }}</strong>
                    </td>
                    <td>{{ version.user?.name || "Không rõ" }}</td>
                    <td>{{ formatDate(version.created_at) }}</td>
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

        <!-- pagination -->
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

    <!-- modal detail -->
    <div
        class="modal fade"
        id="versionDetailModal"
        tabindex="-1"
        aria-labelledby="versionDetailModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <!-- header -->
                <div class="modal-header bg-light">
                    <div class="d-flex align-items-center">
                        <div class="icon-wrapper bg-primary rounded p-2 me-3">
                            <i class="bi bi-file-earmark-text text-white"></i>
                        </div>
                        <div>
                            <!-- version number -->
                            <h5
                                class="modal-title mb-0"
                                id="versionDetailModalLabel"
                            >
                                Phiên bản v{{ selectedVersion?.version_number }}
                            </h5>
                            <div class="text-muted small mt-1">
                                <span
                                    v-if="selectedVersion?.is_current_version"
                                    class="badge bg-success-subtle text-success me-2"
                                >
                                    <i class="bi bi-check-circle me-1"></i>Hiện
                                    tại
                                </span>
                                <span class="text-muted"
                                    >Chi tiết phiên bản</span
                                >
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <!-- body -->
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="section-title mb-3">
                                <i class="bi bi-card-text me-2"></i>Thông tin
                                phiên bản
                            </h6>
                        </div>

                        <!-- change note -->
                        <div class="col-sm-12 mb-3">
                            <div class="card bg-light border-0">
                                <div class="card-body p-3">
                                    <h6 class="card-title fw-bold mb-2">
                                        <i class="bi bi-journal-text me-2"></i
                                        >Ghi chú thay đổi
                                    </h6>
                                    <p class="card-text mb-0">
                                        {{
                                            selectedVersion?.change_note ||
                                            "Không có ghi chú"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- mime type  -->
                        <div class="col-sm-6 mb-3">
                            <div class="info-item">
                                <div class="info-icon text-primary">
                                    <i class="bi bi-file-earmark"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label fw-bold">
                                        Loại file
                                    </div>
                                    <div class="info-value">
                                        {{
                                            formatMimeType(
                                                selectedVersion?.mime_type
                                            )
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- file size -->
                        <div class="col-sm-6 mb-3">
                            <div class="info-item">
                                <div class="info-icon text-primary">
                                    <i class="bi bi-hdd"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label fw-bold">
                                        Kích thước
                                    </div>
                                    <div class="info-value">
                                        {{
                                            formatFileSize(
                                                selectedVersion?.file_size
                                            )
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h6 class="section-title mb-3">
                                <i class="bi bi-person me-2"></i>Thông tin cập
                                nhật
                            </h6>
                        </div>

                        <!-- user name -->
                        <div class="col-sm-6 mb-3">
                            <div class="info-item">
                                <div class="info-icon text-primary">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label fw-bold">
                                        Người cập nhật
                                    </div>
                                    <div class="info-value">
                                        {{
                                            selectedVersion?.user?.name ||
                                            "Không rõ"
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- create at -->
                        <div class="col-sm-6 mb-3">
                            <div class="info-item">
                                <div class="info-icon text-primary">
                                    <i class="bi bi-calendar-event"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label fw-bold">
                                        Ngày cập nhật
                                    </div>
                                    <div class="info-value">
                                        {{
                                            formatDate(
                                                selectedVersion?.created_at
                                            )
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- is current version -->
                        <div class="col-sm-6 mb-3">
                            <div class="info-item">
                                <div class="info-icon text-primary">
                                    <i class="bi bi-info-circle"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label fw-bold">
                                        Trạng thái
                                    </div>
                                    <div class="info-value">
                                        <span
                                            v-if="
                                                selectedVersion?.is_current_version
                                            "
                                            class="badge bg-success-subtle text-success"
                                        >
                                            <i
                                                class="bi bi-check-circle me-1"
                                            ></i
                                            >Hiện tại
                                        </span>
                                        <span
                                            v-else
                                            class="badge bg-secondary-subtle text-secondary"
                                        >
                                            <i
                                                class="bi bi-clock-history me-1"
                                            ></i
                                            >Cũ
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- footer -->
                <div class="modal-footer bg-light">
                    <!-- action -->
                    <div
                        class="d-flex w-100 justify-content-between align-items-center"
                    >
                        <div class="file-actions">
                            <button
                                v-if="selectedVersion?.file_path"
                                class="btn btn-primary"
                                @click="previewFile(selectedVersion)"
                                title="Xem trước tài liệu"
                            >
                                <i class="bi bi-eye me-1"></i>
                                Xem trước
                            </button>
                        </div>

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal"
                        >
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
import * as bootstrap from "bootstrap";
import { ref, onMounted } from "vue";
import axios from "axios";

const props = defineProps({
    documentId: { type: [String, Number], required: true },
});

const versions = ref({ data: [] });
const loading = ref(false);
const selectedVersion = ref(null);
const previewUrl = ref(null);
const mimeType = ref("");
const isPreviewable = ref(false);

// list document versions
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
onMounted(() => fetchVersions());

// modal detail
const showVersionDetail = (version) => {
    selectedVersion.value = version;
    const modalEl = document.getElementById("versionDetailModal");
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
};

// modal preview file
const previewFile = (version) => {
    selectedVersion.value = version;
    previewUrl.value = null;
    isPreviewable.value = false;
    loading.value = true;

    const mime = version.mime_type || "";
    mimeType.value = mime;

    if (mime.includes("pdf") || mime.includes("image")) {
        isPreviewable.value = true;
    }

    previewUrl.value = version.file_path.startsWith("docs/")
        ? `/storage/${version.file_path}`
        : version.file_path;

    const modalEl = document.getElementById("filePreviewModal");
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    setTimeout(() => (loading.value = false), 300);
};
</script>
