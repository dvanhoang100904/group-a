<template>
    <nav v-if="lastPage && lastPage > 1" class="mt-3">
        <ul class="pagination justify-content-end mb-0 flex-wrap">
            <!-- Prev -->
            <li class="page-item" :class="{ disabled: currentPage <= 1 }">
                <button class="page-link" @click="goToPage(currentPage - 1)">
                    <i class="bi bi-arrow-left me-1"></i> Trước
                </button>
            </li>

            <!-- First page + jump back -->
            <li v-if="startPage > 1" class="page-item">
                <button class="page-link" @click="goToPage(1)">1</button>
            </li>
            <li v-if="startPage > 2" class="page-item">
                <button
                    class="page-link"
                    @click="goToPage(currentPage - maxPagesToShow)"
                >
                    …
                </button>
            </li>

            <!-- Visible page window -->
            <li
                v-for="page in pages"
                :key="page"
                class="page-item"
                :class="{ active: page === currentPage }"
            >
                <button class="page-link" @click="goToPage(page)">
                    {{ page }}
                </button>
            </li>

            <!-- Jump forward + last page -->
            <li v-if="endPage < lastPage - 1" class="page-item">
                <button
                    class="page-link"
                    @click="goToPage(currentPage + maxPagesToShow)"
                >
                    …
                </button>
            </li>
            <li v-if="endPage < lastPage" class="page-item">
                <button class="page-link" @click="goToPage(lastPage)">
                    {{ lastPage }}
                </button>
            </li>

            <!-- Next -->
            <li
                class="page-item"
                :class="{ disabled: currentPage >= lastPage }"
            >
                <button class="page-link" @click="goToPage(currentPage + 1)">
                    Sau <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </li>
        </ul>
    </nav>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    currentPage: { type: Number, required: true },
    lastPage: { type: Number, required: true },
    maxPagesToShow: { type: Number, default: 3 },
});

const emit = defineEmits(["page-changed"]);

// Tính startPage và endPage
const startPage = computed(() => {
    let start = props.currentPage - Math.floor(props.maxPagesToShow / 2);
    if (start < 1) start = 1;
    if (start + props.maxPagesToShow - 1 > props.lastPage) {
        start = props.lastPage - props.maxPagesToShow + 1;
        if (start < 1) start = 1;
    }
    return start;
});

const endPage = computed(() => {
    let end = startPage.value + props.maxPagesToShow - 1;
    if (end > props.lastPage) end = props.lastPage;
    return end;
});

const pages = computed(() => {
    const arr = [];
    for (let i = startPage.value; i <= endPage.value; i++) arr.push(i);
    return arr;
});

const goToPage = (page) => {
    if (page < 1 || page > props.lastPage || page === props.currentPage) return;
    emit("page-changed", page);
};
</script>

<style scoped>
.page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}
.page-link {
    cursor: pointer;
}
</style>
