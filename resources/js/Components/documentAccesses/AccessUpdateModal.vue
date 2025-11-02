<template>
    <div
        class="modal fade"
        tabindex="-1"
        ref="modalRef"
        aria-labelledby="accessUpdateModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2 text-primary"></i>
                        Cập nhật quyền chia sẻ
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="closeModal"
                    ></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <form class="row g-3" @submit.prevent="submitUpdate">
                        <!-- Doi tuong duoc chia se -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">
                                Đối tượng được chia sẻ
                            </label>
                            <input
                                type="text"
                                class="form-control bg-light"
                                :value="targetDisplay"
                                disabled
                            />
                        </div>

                        <!-- Ngay het han -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">
                                Ngày hết hạn
                            </label>
                            <div class="d-flex gap-2">
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="form.expiration_date"
                                    :disabled="form.no_expiry"
                                />
                                <div class="form-check align-self-center ms-3">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="updateNoExpiry"
                                        v-model="form.no_expiry"
                                    />
                                    <label
                                        class="form-check-label small"
                                        for="updateNoExpiry"
                                    >
                                        Không giới hạn
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Quyen truy cap -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">
                                Quyền truy cập
                            </label>
                            <div class="d-flex flex-wrap gap-3">
                                <div
                                    v-for="perm in permissions"
                                    :key="perm.key"
                                    class="form-check"
                                >
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        :id="'update_' + perm.key"
                                        v-model="form[perm.key]"
                                    />
                                    <label
                                        class="form-check-label"
                                        :for="'update_' + perm.key"
                                    >
                                        {{ perm.label }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Error -->
                        <div v-if="error" class="col-12">
                            <div class="alert alert-danger py-2">
                                {{ error }}
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="col-12 text-end mt-3">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary me-3 px-3"
                                @click="closeModal"
                            >
                                <i class="bi bi-x-circle me-1"></i> Hủy
                            </button>
                            <button
                                type="submit"
                                class="btn btn-sm btn-primary px-3"
                                :disabled="loading"
                            >
                                <i class="bi bi-save me-1"></i>
                                {{ loading ? "Đang lưu..." : "Cập nhật" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import axios from "axios";

const props = defineProps({
    documentId: { type: [String, Number], required: true },
    access: { type: Object, default: () => ({}) },
    users: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
});

const emit = defineEmits(["updated"]);

const modalRef = ref(null);
let bsModal = null;
const loading = ref(false);
const error = ref(null);

const form = ref({
    expiration_date: "",
    no_expiry: false,
    can_view: false,
    can_download: false,
    can_edit: false,
    can_delete: false,
    can_upload: false,
    can_share: false,
});

const permissions = [
    { key: "can_view", label: "Xem" },
    { key: "can_download", label: "Tải xuống" },
    { key: "can_edit", label: "Chỉnh sửa" },
    { key: "can_delete", label: "Xóa" },
    { key: "can_upload", label: "Tải lên" },
    { key: "can_share", label: "Chia sẻ tiếp" },
];

// Hien thi ten doi tuong chia se
const targetDisplay = computed(() => {
    if (!props.access) return "—";
    if (props.access.granted_to_type === "user") {
        const user = props.users.find(
            (user) => user.user_id === props.access.granted_to_user_id,
        );
        return user
            ? `Người dùng: ${user.name}`
            : "Người dùng (không xác định)";
    } else {
        const role = props.roles.find(
            (role) => role.role_id === props.access.granted_to_role_id,
        );
        return role ? `Vai trò: ${role.name}` : "Vai trò (không xác định)";
    }
});

// Cap nhat quyen
watch(
    () => props.access,
    (newVal) => {
        if (!newVal) return;
        form.value = {
            expiration_date: newVal.expiration_date
                ? newVal.expiration_date.split("T")[0]
                : "",
            no_expiry: !!newVal.no_expiry,
            can_view: !!newVal.can_view,
            can_download: !!newVal.can_download,
            can_edit: !!newVal.can_edit,
            can_delete: !!newVal.can_delete,
            can_upload: !!newVal.can_upload,
            can_share: !!newVal.can_share,
        };
    },
    { immediate: true },
);

const showModal = () => {
    if (!bsModal) bsModal = new bootstrap.Modal(modalRef.value);
    bsModal.show();
};
const hideModal = () => {
    if (bsModal) {
        bsModal.hide();
        bsModal = null;
    }
};
const closeModal = () => {
    hideModal();
    error.value = null;
};

const submitUpdate = async () => {
    try {
        loading.value = true;
        const res = await axios.put(
            `/api/documents/${props.documentId}/accesses/${props.access.access_id}`,
            form.value,
        );

        if (res.data.success) {
            emit("updated");
            closeModal();
        } else {
            error.value = res.data.message || "Không thể cập nhật quyền!";
        }
    } catch (err) {
        console.error(err);
        error.value = "Lỗi khi cập nhật quyền chia sẻ!";
    } finally {
        loading.value = false;
    }
};

defineExpose({ showModal, hideModal, closeModal });
</script>

<style scoped>
.btn {
    border-radius: 0.5rem;
    font-weight: 500;
}
.bg-light {
    background-color: #f8fafd !important;
}
</style>
