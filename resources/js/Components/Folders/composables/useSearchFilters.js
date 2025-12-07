import { ref, computed } from 'vue';

export function useSearchFilters() {
    const searchParams = ref({
        name: '',
        date: '',
        file_type: ''
    });

    const isSearchMode = ref(false);

    const hasActiveFilters = computed(() => {
        return searchParams.value.name.trim() !== '' ||
            searchParams.value.date !== '' ||
            searchParams.value.file_type !== '';
    });

    const handleSearch = () => {
        if (hasActiveFilters.value) {
            isSearchMode.value = true;
        }
    };

    const resetFilters = () => {
        searchParams.value = { name: '', date: '', file_type: '' };
        isSearchMode.value = false;
    };

    const validateDate = () => {
        if (searchParams.value.date) {
            const date = new Date(searchParams.value.date);
            if (isNaN(date.getTime())) {
                searchParams.value.date = '';
            }
        }
    };

    return {
        searchParams,
        isSearchMode,
        hasActiveFilters,
        handleSearch,
        resetFilters,
        validateDate
    };
}