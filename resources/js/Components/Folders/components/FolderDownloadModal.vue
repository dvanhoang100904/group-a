<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center mb-4">
                    <i class="fas fa-download text-blue-500 text-2xl mr-3"></i>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Tải folder</h3>
                        <p class="text-sm text-gray-500">"{{ folderName }}"</p>
                    </div>
                </div>
                
                <!-- Thông tin folder -->
                <div v-if="folderInfo" class="mb-6">
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-gray-500">Số file</div>
                                <div class="font-medium">{{ folderInfo.file_count }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Thư mục con</div>
                                <div class="font-medium">{{ folderInfo.folder_count }}</div>
                            </div>
                            <div class="col-span-2">
                                <div class="text-gray-500">Tổng kích thước</div>
                                <div class="font-medium">{{ formatFileSize(folderInfo.total_size) }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Warning nếu folder lớn -->
                    <div v-if="folderInfo.total_size > maxSizeWarning" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2 mt-0.5"></i>
                            <div class="text-sm text-yellow-800">
                                Folder lớn ({{ formatFileSize(folderInfo.total_size) }}). 
                                Quá trình tải có thể mất vài phút.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Loading -->
                <div v-if="loading" class="mb-6">
                    <div class="flex items-center justify-center mb-4">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    </div>
                    <p class="text-center text-sm text-gray-600">
                        {{ loadingMessage }}
                    </p>
                    <div v-if="progress > 0" class="mt-4">
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full transition-all duration-300" 
                                 :style="{ width: progress + '%' }"></div>
                        </div>
                        <p class="text-xs text-gray-500 text-center mt-2">
                            {{ progress }}%
                        </p>
                    </div>
                </div>
                
                <!-- Error -->
                <div v-if="error" class="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-500 mr-2 mt-0.5"></i>
                        <div class="text-sm text-red-800">
                            {{ error }}
                        </div>
                    </div>
                </div>
                
                <!-- Buttons -->
                <div class="flex justify-end space-x-3">
                    <button @click="$emit('close')" 
                            :disabled="loading"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors disabled:opacity-50">
                        Đóng
                    </button>
                    <button @click="startDownload" 
                            :disabled="loading || error || !folderInfo?.can_download"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg transition-colors disabled:opacity-50 flex items-center">
                        <i class="fas fa-download mr-2"></i>
                        {{ getButtonText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'FolderDownloadModal',
    props: {
        folder: {
            type: Object,
            required: true
        },
        show: {
            type: Boolean,
            default: false
        }
    },
    emits: ['close'],
    data() {
        return {
            loading: false,
            loadingMessage: 'Đang tải thông tin...',
            error: '',
            folderInfo: null,
            progress: 0,
            maxSizeWarning: 50 * 1024 * 1024, // 50MB
        };
    },
    computed: {
        folderName() {
            return this.sanitizeOutput(this.folder?.name || '');
        },
        getButtonText() {
            if (this.loading) return 'Đang xử lý...';
            if (!this.folderInfo) return 'Tải về';
            if (!this.folderInfo.can_download) return 'Không thể tải';
            return 'Tải về';
        }
    },
    watch: {
        show: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    this.loadFolderInfo();
                } else {
                    this.reset();
                }
            }
        }
    },
    methods: {
        sanitizeOutput(value) {
            if (value === null || value === undefined) return '';
            const div = document.createElement('div');
            div.textContent = value.toString();
            return div.innerHTML;
        },
        
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        async loadFolderInfo() {
            if (!this.folder || !this.folder.id) {
                this.error = 'Thông tin folder không hợp lệ';
                return;
            }

            try {
                this.loading = true;
                this.loadingMessage = 'Đang tải thông tin folder...';
                this.error = '';
                
                console.log('Loading folder info for:', this.folder.id);
                
                const response = await axios.get(`/api/folders/${this.folder.id}/download-info`);
                
                console.log('Folder info response:', response.data);
                
                if (response.data.success) {
                    this.folderInfo = response.data.data;
                    
                    // Kiểm tra quyền download
                    if (!this.folderInfo.can_download) {
                        this.error = 'Bạn không có quyền download folder này hoặc folder quá lớn';
                    }
                } else {
                    this.error = response.data.message || 'Không thể lấy thông tin folder';
                }
            } catch (error) {
                console.error('Load folder info error:', error);
                console.error('Error response:', error.response?.data);
                
                this.error = error.response?.data?.message || 'Lỗi khi tải thông tin folder';
            } finally {
                this.loading = false;
            }
        },
        
        async startDownload() {
            if (!this.folderInfo?.can_download) {
                this.error = 'Không thể tải folder này';
                return;
            }

            try {
                this.loading = true;
                this.loadingMessage = 'Đang tạo file ZIP...';
                this.error = '';
                this.progress = 20;
                
                console.log('Starting download for folder:', this.folder.id);
                
                // Gọi API download
                const response = await axios.get(`/api/folders/${this.folder.id}/download`, {
                    responseType: 'blob',
                    onDownloadProgress: (progressEvent) => {
                        if (progressEvent.total) {
                            this.progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                            this.loadingMessage = `Đang tải... ${this.progress}%`;
                        }
                    }
                });
                
                console.log('Download response received');
                
                // Tạo download link
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `${this.folderName}.zip`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                
                // Cleanup
                window.URL.revokeObjectURL(url);
                
                // Đóng modal sau khi download
                this.progress = 100;
                this.loadingMessage = 'Hoàn tất!';
                
                setTimeout(() => {
                    this.$emit('close');
                }, 1000);
                
            } catch (error) {
                console.error('Download error:', error);
                console.error('Error response:', error.response?.data);
                
                // Nếu response là blob, convert sang text để đọc error message
                if (error.response?.data instanceof Blob) {
                    const text = await error.response.data.text();
                    try {
                        const errorData = JSON.parse(text);
                        this.error = errorData.message || 'Lỗi khi tải folder';
                    } catch {
                        this.error = 'Lỗi khi tải folder';
                    }
                } else {
                    this.error = error.response?.data?.message || 'Lỗi khi tải folder';
                }
                
                this.loading = false;
                this.progress = 0;
            }
        },
        
        reset() {
            this.loading = false;
            this.loadingMessage = 'Đang tải thông tin...';
            this.error = '';
            this.folderInfo = null;
            this.progress = 0;
        }
    },
    beforeUnmount() {
        this.reset();
    }
}
</script>

<style scoped>
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>