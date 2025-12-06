import { ref } from 'vue';
import axios from 'axios';

export function useFolderAPI() {
    const loading = ref(false);
    const error = ref(null);

    const loadData = async (params = {}) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/folders', { params });

            if (response.data.success) {
                return response.data.data;
            } else {
                throw new Error(response.data.message || 'API response not successful');
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Lỗi khi tải dữ liệu';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const loadDocumentTypes = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/types');
            return response.data || [];
        } catch (err) {
            error.value = err.response?.data?.message || 'Lỗi khi tải loại tài liệu';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        loading,
        error,
        loadData,
        loadDocumentTypes
    };
}