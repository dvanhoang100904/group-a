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
                            v-for="v in versions"
                            :key="v.version_id"
                            :value="v.version_id"
                            :disabled="v.version_id === versionB"
                        >
                            v{{ v.version_number }}
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
                            v-for="v in versions"
                            :key="v.version_id"
                            :value="v.version_id"
                            :disabled="v.version_id === versionA"
                        >
                            v{{ v.version_number }}
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

            <div class="mt-4 border-top pt-3" v-if="differences.length">
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
                        <span v-else-if="diff.type === 'user'"
                            >Người cập nhật thay đổi: “{{ diff.old }} →
                            {{ diff.new }}”</span
                        >
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import axios from "axios";

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
        alert("Vui lòng chọn cả hai phiên bản");
        return;
    }

    if (versionA.value === versionB.value) {
        alert("Hai phiên bản phải khác nhau để so sánh");
        return;
    }

    // Huy request
    if (cancelToken) cancelToken.cancel("Request bị hủy do thao tác mới");
    cancelToken = axios.CancelToken.source();

    loading.value = true;
    differences.value = [];

    try {
        const res = await axios.get(
            `/api/documents/${props.documentId}/versions/compare`,
            {
                params: {
                    version_a: versionA.value,
                    version_b: versionB.value,
                },
                cancelToken: cancelToken.token,
            }
        );

        differences.value = res.data.success ? res.data.data : [];
    } catch (err) {
        if (!axios.isCancel(err)) {
            console.error(err);
            alert("Không thể so sánh phiên bản");
        }
    } finally {
        loading.value = false;
    }
};

// Watch versionA và versionB de tu clear differences khi doi lua chon
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
