<template>
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal-box">
            <h5 class="fw-bold mb-3">Chỉnh sửa tài liệu</h5>

            <!-- Tên -->
            <div class="mb-3">
                <label class="form-label">Tên tài liệu</label>
                <input v-model="form.title" class="form-control" />
            </div>

            <!-- Mô tả -->
            <div class="mb-3">
                <label class="form-label">Mô tả</label>
                <textarea v-model="form.description" class="form-control" rows="3"></textarea>
            </div>

            <!-- Loại tài liệu -->
            <div class="mb-3">
                <label class="form-label">Loại tài liệu</label>
                <select v-model="form.type_id" class="form-control">
                    <option value="">— Không chọn —</option>
                    <option
                        v-for="t in types"
                        :key="t.type_id"
                        :value="t.type_id"
                    >
                        {{ t.name }}
                    </option>
                </select>
            </div>

            <!-- Môn học -->
            <div class="mb-3">
                <label class="form-label">Môn học</label>
                <select v-model="form.subject_id" class="form-control">
                    <option value="">— Không chọn —</option>
                    <option
                        v-for="s in subjects"
                        :key="s.subject_id"
                        :value="s.subject_id"
                    >
                        {{ s.name }}
                    </option>
                </select>
            </div>

            <!-- Thư mục -->
            <div class="mb-3">
                <label class="form-label">Thư mục</label>
                <select v-model="form.folder_id" class="form-control">
                    <option value="">— Không chọn —</option>
                    <option
                        v-for="f in folders"
                        :key="f.folder_id"
                        :value="f.folder_id"
                    >
                        {{ f.name }}
                    </option>
                </select>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <button class="btn btn-secondary" @click="$emit('close')">Hủy</button>
                <button class="btn btn-primary" @click="save">Lưu thay đổi</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const props = defineProps({
    documentId: Number
});

const emit = defineEmits(["close", "updated"]);

const form = ref({
    title: "",
    description: "",
    type_id: "",
    subject_id: "",
    folder_id: "",
});

const types = ref([]);
const subjects = ref([]);
const folders = ref([]);

// Load dữ liệu API
onMounted(async () => {
    try {
        const [typeRes, subjectRes, folderRes, detailRes] = await Promise.all([
            axios.get("/api/types"),
            axios.get("/api/subjects"),
            axios.get("/folders/tree"),        // API đúng của bạn
            axios.get(`/api/documents/${props.documentId}/detail`),
        ]);

        types.value = typeRes.data || [];
        subjects.value = subjectRes.data || [];
        folders.value = folderRes.data || [];

        // Gán dữ liệu tài liệu vào form
        Object.assign(form.value, {
            title: detailRes.data.document.title,
            description: detailRes.data.document.description,
            type_id: detailRes.data.document.type_id,
            subject_id: detailRes.data.document.subject_id,
            folder_id: detailRes.data.document.folder_id,
        });

    } catch (e) {
        console.error("Lỗi load dữ liệu:", e);
    }
});

// Lưu chỉnh sửa
const save = async () => {
    try {
        await axios.put(`/api/documents/${props.documentId}`, form.value);
        emit("updated", form.value);
        emit("close");
    } catch (e) {
        console.error("Lỗi khi lưu:", e);
    }
};
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
}

.modal-box {
    background: white;
    width: 500px;
    padding: 20px;
    border-radius: 10px;
    max-height: 90vh;
    overflow-y: auto;
}
</style>
