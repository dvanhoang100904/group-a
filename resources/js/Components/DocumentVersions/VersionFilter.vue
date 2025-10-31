<template>
    <form
        class="row g-3 align-items-end mb-3"
        @submit.prevent="$emit('filter')"
    >
        <div class="col-md-2">
            <label class="form-label">Từ khóa</label>
            <input
                v-model="localFilters.keyword"
                type="text"
                class="form-control form-control-sm"
                placeholder="Tìm theo ghi chú hoặc số phiên bản"
            />
        </div>

        <div class="col-md-2">
            <label class="form-label">Người upload</label>
            <select
                v-model.number="localFilters.user_id"
                class="form-select form-select-sm"
            >
                <option value="">Tất cả</option>
                <option
                    v-for="user in users"
                    :key="user.user_id"
                    :value="user.user_id"
                >
                    {{ user.name }}
                </option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">Trạng thái</label>
            <select
                v-model="localFilters.status"
                class="form-select form-select-sm"
            >
                <option value="">Tất cả</option>
                <option value="true">Phiên bản hiện tại</option>
                <option value="false">Phiên bản cũ</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">Từ ngày</label>
            <input
                v-model="localFilters.date_from"
                type="date"
                class="form-control form-control-sm"
            />
        </div>

        <div class="col-md-2">
            <label class="form-label">Đến ngày</label>
            <input
                v-model="localFilters.date_to"
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
                @click="handleReset"
            >
                <i class="bi bi-x-circle me-1"></i> Reset
            </button>
        </div>
    </form>
</template>

<script setup>
import { reactive, watch } from "vue";

const props = defineProps({
    modelValue: { type: Object, required: true },
    users: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:modelValue", "filter", "reset"]);

const localFilters = reactive({ ...props.modelValue });

// dong bo hai chieu v-model
watch(localFilters, (newVal) => emit("update:modelValue", { ...newVal }), {
    deep: true,
});

// Neu cha thay doi filters reset thi dong bo lai
watch(
    () => props.modelValue,
    (newVal) => Object.assign(localFilters, newVal),
    { deep: true }
);

// Reset local filters
function handleReset() {
    Object.assign(localFilters, {
        keyword: "",
        user_id: "",
        status: "",
        date_from: "",
        date_to: "",
    });
    emit("reset");
}
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
