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
                        <i class="bi bi-pencil-square me-2"></i>
                        Cập nhật quyền truy cập
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
                                Đối tượng được truy cập
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
                                        @change="onNoExpiryChange"
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
                                <i
                                    v-if="loading"
                                    class="bi bi-arrow-repeat spin me-1"
                                ></i>
                                <i v-else class="bi bi-save me-1"></i>
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
import Swal from "sweetalert2";

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

// Url
const url = `/documents`;

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
    { key: "can_share", label: "chia sẻ tiếp" },
];

// Hien thi ten doi tuong chia se
const targetDisplay = computed(() => {
    if (!props.access) return "—";

    const { granted_to_type, granted_to_user_id, granted_to_role_id } =
        props.access;

    if (granted_to_type === "user") {
        const user = props.users.find(
            (u) =>
                u.user_id === granted_to_user_id || u.id === granted_to_user_id,
        );
        return user
            ? `Người dùng: ${user.name}`
            : `Người dùng (ID ${granted_to_user_id})`;
    }

    if (granted_to_type === "role") {
        const role = props.roles.find(
            (r) =>
                r.role_id === granted_to_role_id || r.id === granted_to_role_id,
        );
        return role
            ? `Vai trò: ${role.name}`
            : `Vai trò (ID ${granted_to_role_id})`;
    }

    return "—";
});
const onNoExpiryChange = () => {
    if (form.value.no_expiry) {
        form.value.expiration_date = "";
    }
};

// Cap nhat form
watch(
    () => props.access,
    (a) => {
        if (!a) return;
        const date = a.expiration_date ? new Date(a.expiration_date) : null;
        form.value = {
            expiration_date: date ? date.toISOString().split("T")[0] : "",
            no_expiry: !!a.no_expiry,
            can_view: !!a.can_view,
            can_download: !!a.can_download,
            can_edit: !!a.can_edit,
            can_delete: !!a.can_delete,
            can_upload: !!a.can_upload,
            can_share: !!a.can_share,
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
    timer,
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
        timer,
        showConfirmButton: !timer,
        allowOutsideClick: !timer,
    });
};

const submitUpdate = async () => {
    try {
        loading.value = true;
        error.value = null;

        const res = await axios.put(
            `/api/documents/${props.documentId}/accesses/${props.access.access_id}`,
            form.value,
        );

        if (res.data.success) {
            closeModal();
            await showSwal({
                icon: "success",
                title: "Cập nhật thành công",
                text: res.data.message,
                timer: 2000,
            });

            emit("updated");
        } else {
            closeModal();
            await showSwal({
                icon: "error",
                title: "Lỗi",
                text: res.data.message,
            });

            if (res.data.message?.includes("Tài liệu không tồn tại")) {
                window.location.href = url;
                return;
            }
        }
    } catch (err) {
        console.error(err);
        await showSwal({
            icon: "error",
            title: "Lỗi hệ thống",
            text: "Có lỗi xảy ra. Vui lòng thử lại.",
        });
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
