<template>
    <div class="card shadow-sm mt-4 border-0">
        <div class="card-header bg-light fw-bold">
            <i class="bi bi-shuffle me-1"></i> So sánh phiên bản
        </div>
        <div class="card-body">
            <form
                class="row g-3 align-items-center"
                @submit.prevent="compareVersions"
            >
                <div class="col-md-5">
                    <label for="versionA" class="form-label small fw-semibold"
                        >Phiên bản A</label
                    >
                    <select
                        id="versionA"
                        v-model="versionA"
                        class="form-select"
                    >
                        <option value="">Chọn phiên bản...</option>
                        <option
                            v-for="version in versions"
                            :key="version.version_id"
                            :value="version.version_id"
                            :disabled="version.version_id === versionB"
                        >
                            v{{ version.version_number }}
                        </option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label for="versionB" class="form-label small fw-semibold"
                        >Phiên bản B</label
                    >
                    <select
                        id="versionB"
                        v-model="versionB"
                        class="form-select"
                    >
                        <option value="">Chọn phiên bản...</option>
                        <option
                            v-for="version in versions"
                            :key="version.version_id"
                            :value="version.version_id"
                            :disabled="version.version_id === versionA"
                        >
                            v{{ version.version_number }}
                        </option>
                    </select>
                </div>

                <div class="col-md-2 text-end">
                    <button
                        class="btn btn-outline-primary w-100 mt-3 px-3"
                        :disabled="loading"
                    >
                        <i class="bi bi-arrow-left-right me-2"></i>
                        <span v-if="loading">Đang so sánh...</span>
                        <span v-else>So sánh</span>
                    </button>
                </div>
            </form>

            <div class="mt-4 border-top pt-3">
                <template v-if="differences.length">
                    <h6 class="fw-bold text-primary mb-3">Kết quả so sánh</h6>
                    <ul class="list-group small">
                        <li
                            v-for="(diff, index) in differences"
                            :key="index"
                            class="list-group-item"
                        >
                            <i :class="iconClass(diff.type) + ' me-2'"></i>
                            <span v-if="diff.type === 'size'">
                                Kích thước thay đổi từ
                                <strong
                                    >{{ formatFileSize(diff.old) }} →
                                    {{ formatFileSize(diff.new) }}</strong
                                >
                            </span>
                            <span v-else-if="diff.type === 'note'"
                                >Ghi chú mới: “{{ diff.new }}”</span
                            >
                            <span v-else-if="diff.type === 'user'">
                                Người cập nhật thay đổi: “{{ diff.old }} →
                                {{ diff.new }}”
                            </span>
                        </li>
                    </ul>
                </template>

                <template v-else-if="!loading && versionA && versionB">
                    <p class="text-muted">
                        Không có sự khác biệt nào giữa các phiên bản.
                    </p>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

const props = defineProps({
    documentId: { type: [String, Number], required: true },
    versions: { type: Array, required: true },
    formatFileSize: Function,
});

const versionA = ref("");
const versionB = ref("");
const differences = ref([]);
const loading = ref(false);
let cancelToken = null;

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
    });
};

const iconClass = (type) => {
    switch (type) {
        case "size":
            return "bi bi-check2-circle text-primary";
        case "note":
            return "bi bi-pencil-square text-primary";
        case "user":
            return "bi bi-person-check text-primary";
        default:
            return "bi bi-info-circle text-primary";
    }
};

const compareVersions = async () => {
    if (!versionA.value || !versionB.value) {
        await showSwal({
            icon: "warning",
            title: "Lỗi",
            text: "Vui lòng chọn cả hai phiên bản",
        });
        return;
    }

    if (versionA.value === versionB.value) {
        await showSwal({
            icon: "warning",
            title: "Lỗi",
            text: "Hai phiên bản phải khác nhau để so sánh",
        });
        return;
    }

    if (cancelToken) cancelToken.cancel("Request bị hủy do thao tác mới");
    cancelToken = axios.CancelToken.source();

    loading.value = true;
    differences.value = [];

    Swal.fire({
        title: "Đang so sánh...",
        text: "Vui lòng chờ",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/versions/compare`,
            {
                params: {
                    version_a: versionA.value,
                    version_b: versionB.value,
                },
                cancelToken: cancelToken.token,
            },
        );

        Swal.close();

        if (res.data.success) {
            differences.value = res.data.data.length ? res.data.data : [];
            if (!differences.value.length) {
                await showSwal({
                    icon: "info",
                    title: "Kết quả",
                    text: "Hai phiên bản giống nhau, không có sự khác biệt nào.",
                });
            }
        } else {
            differences.value = [];
            await showSwal({
                icon: "error",
                title: "Lỗi",
                text: res.data.message || "Không thể so sánh phiên bản",
            });
        }
    } catch (err) {
        Swal.close();
        if (!axios.isCancel(err)) {
            console.error(err);
            await showSwal({
                icon: "error",
                title: "Lỗi",
                text: "Đã xảy ra lỗi khi so sánh phiên bản. Vui lòng thử lại!",
            });
        }
    } finally {
        loading.value = false;
    }
};

// Clear differences khi đổi selection
watch([versionA, versionB], () => {
    differences.value = [];
});
</script>

<style scoped>
.btn {
    border-radius: 0.5rem;
    font-weight: 500;
}
.bg-light {
    background-color: #f8fafd !important;
}
.card {
    transition: all 0.2s ease;
}
</style>
