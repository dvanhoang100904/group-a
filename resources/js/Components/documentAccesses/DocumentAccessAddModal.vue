<template>
    <div
        class="modal fade"
        tabindex="-1"
        ref="modalRef"
        aria-labelledby="accessAddModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header text-primary bg-light">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus me-2"></i> Thêm quyền chia
                        sẻ
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="closeModal"
                    ></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <form class="row g-3" @submit.prevent="submitAccess">
                        <!-- Users -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">
                                Người dùng
                            </label>
                            <select
                                class="form-select"
                                v-model="form.granted_to_user_id"
                                :disabled="form.granted_to_type === 'role'"
                            >
                                <option value="">Chọn người dùng</option>
                                <option
                                    v-for="user in users"
                                    :key="user.user_id"
                                    :value="user.user_id"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Roles -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">
                                Vai trò
                            </label>
                            <select
                                class="form-select"
                                v-model="form.granted_to_role_id"
                                :disabled="form.granted_to_type === 'user'"
                            >
                                <option value="">Chọn vai trò</option>
                                <option
                                    v-for="role in roles"
                                    :key="role.role_id"
                                    :value="role.role_id"
                                >
                                    {{ role.name }}
                                </option>
                            </select>
                        </div>

                        <!-- share type -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">
                                Chia sẻ cho
                            </label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        id="shareToUser"
                                        value="user"
                                        v-model="form.granted_to_type"
                                    />
                                    <label
                                        class="form-check-label"
                                        for="shareToUser"
                                        >Người dùng</label
                                    >
                                </div>
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        id="shareToRole"
                                        value="role"
                                        v-model="form.granted_to_type"
                                    />
                                    <label
                                        class="form-check-label"
                                        for="shareToRole"
                                        >Vai trò</label
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Expiration date -->
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
                                        id="no_expiry"
                                        v-model="form.no_expiry"
                                        @change="onNoExpiryChange"
                                    />
                                    <label
                                        class="form-check-label small"
                                        for="no_expiry"
                                    >
                                        Không giới hạn
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Permissions -->
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
                                        :id="perm.key"
                                        v-model="form[perm.key]"
                                    />
                                    <label
                                        class="form-check-label"
                                        :for="perm.key"
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
                                {{ loading ? "Đang lưu..." : "Lưu quyền" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

const props = defineProps({
    documentId: { type: [String, Number], required: true },
    users: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
});

const emit = defineEmits(["added"]);

// Ref modal chinh
const modalRef = ref(null);

// Instance bootstrap modal
let bsModal = null;

const loading = ref(false);
const error = ref(null);

const form = ref({
    granted_to_type: "user",
    granted_to_user_id: "",
    granted_to_role_id: "",
    expiration_date: "",
    no_expiry: false,
    can_view: true,
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

// Hien thi modal
const showModal = () => {
    if (!bsModal) bsModal = new bootstrap.Modal(modalRef.value);
    bsModal.show();
};

// An modal
const hideModal = () => {
    if (bsModal) {
        bsModal.hide();
        bsModal = null;
    }
};

// Dong modal
const closeModal = () => {
    hideModal();
    resetForm();
};

// Reset form
const resetForm = () => {
    form.value = {
        granted_to_type: "user",
        granted_to_user_id: "",
        granted_to_role_id: "",
        expiration_date: "",
        no_expiry: false,
        can_view: true,
        can_download: false,
        can_edit: false,
        can_delete: false,
        can_upload: false,
        can_share: false,
    };
    error.value = null;
};

// Validate form
const validateForm = () => {
    // kiem tra chon user/role
    if (
        form.value.granted_to_type === "user" &&
        !form.value.granted_to_user_id
    ) {
        error.value = "Vui lòng chọn người dùng!";
        return false;
    }
    if (
        form.value.granted_to_type === "role" &&
        !form.value.granted_to_role_id
    ) {
        error.value = "Vui lòng chọn vai trò!";
        return false;
    }

    // Kiem tra ngay hen han
    if (!form.value.no_expiry && !form.value.expiration_date) {
        error.value =
            "Vui lòng chọn ngày hết hạn hoặc đánh dấu không giới hạn!";
        return false;
    }
    if (!form.value.no_expiry && form.value.expiration_date) {
        const selectedDate = new Date(form.value.expiration_date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (selectedDate < today) {
            error.value = "Ngày hết hạn phải bằng hoặc sau hôm nay!";
            return false;
        }
    }

    error.value = null;
    return true;
};

const onNoExpiryChange = () => {
    if (form.value.no_expiry) {
        form.value.expiration_date = "";
    }
};

// Submit form
const submitAccess = async () => {
    if (!validateForm()) return;

    try {
        loading.value = true;
        const payload = { ...form.value };

        const res = await axios.post(
            `/api/documents/${props.documentId}/accesses`,
            payload,
        );

        if (res.data.success) {
            Swal.fire({
                icon: "success",
                title: "Thành công",
                text: "Đã thêm quyền chia sẻ!",
                timer: 1500,
                showConfirmButton: false,
            });
            emit("added");
            closeModal();
        } else {
            Swal.fire({
                icon: "error",
                title: "Lỗi",
                text: res.data.message || "Không thể thêm quyền chia sẻ!",
            });
        }
    } catch (err) {
        console.error(err);
        Swal.fire({
            icon: "error",
            title: "Lỗi hệ thống",
            text: "Đã xảy ra lỗi khi thêm quyền chia sẻ. Vui lòng thử lại!",
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

.card {
    transition: all 0.2s ease;
}

.bg-light {
    background-color: #f8fafd !important;
}
</style>
