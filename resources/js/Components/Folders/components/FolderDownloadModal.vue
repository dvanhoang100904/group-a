<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10000] p-4">
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
                        Đang chuẩn bị file ZIP...
                    </p>
                    <div class="mt-4">
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
                        Hủy
                    </button>
                    <button @click="startDownload" 
                            :disabled="loading || error"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg transition-colors disabled:opacity-50 flex items-center">
                        <i class="fas fa-download mr-2"></i>
                        {{ loading ? 'Đang chuẩn bị...' : 'Tải về' }}
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
    data() {
        return {
            loading: false,
            error: '',
            folderInfo: null,
            progress: 0,
            maxSizeWarning: 50 * 1024 * 1024, // 50MB
            downloadUrl: null,
            pollingInterval: null
        };
    },
    computed: {
        folderName() {
            return this.sanitizeOutput(this.folder?.name || '');
        }
    },
    watch: {
        show(newVal) {
            if (newVal) {
                this.loadFolderInfo();
            } else {
                this.reset();
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
            try {
                this.loading = true;
                this.error = '';
                
                const response = await axios.get(`/api/folders/${this.folder.id}/download-info`);
                
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
                this.error = 'Lỗi khi tải thông tin folder';
            } finally {
                this.loading = false;
            }
        },
        
        async startDownload() {
    try {
        this.loading = true;
        this.error = '';
        this.progress = 10;
        
        // Gọi API download trực tiếp (không cần prepare)
        const response = await axios.get(`/api/folders/${this.folder.id}/download`, {
            responseType: 'blob'
        });
        
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
        setTimeout(() => {
            this.$emit('close');
        }, 1000);
        
    } catch (error) {
        console.error('Download error:', error);
        this.error = 'Lỗi khi tải folder';
        this.loading = false;
    }
},
        
        startPolling() {
            this.progress = 30;
            this.pollingInterval = setInterval(async () => {
                try {
                    // Gọi API kiểm tra trạng thái
                    const response = await axios.get(`/api/folders/${this.folder.id}/download-status`);
                    
                    if (response.data.success) {
                        if (response.data.data.status === 'ready') {
                            clearInterval(this.pollingInterval);
                            this.downloadUrl = response.data.data.download_url;
                            this.progress = 100;
                            this.downloadFile();
                        } else if (response.data.data.status === 'processing') {
                            this.progress = Math.min(this.progress + 10, 90);
                        } else if (response.data.data.status === 'failed') {
                            clearInterval(this.pollingInterval);
                            this.error = 'Không thể tạo file ZIP';
                            this.loading = false;
                        }
                    }
                } catch (error) {
                    console.error('Polling error:', error);
                }
            }, 2000);
        },
        
        downloadFile() {
            if (this.downloadUrl) {
                const a = document.createElement('a');
                a.href = this.downloadUrl;
                a.download = this.folderName + '.zip';
                a.rel = 'noopener noreferrer';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                
                // Đóng modal sau khi download
                setTimeout(() => {
                    this.$emit('close');
                }, 1000);
            }
        },
        
        reset() {
            this.loading = false;
            this.error = '';
            this.folderInfo = null;
            this.progress = 0;
            this.downloadUrl = null;
            
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
        }
    },
    beforeUnmount() {
        this.reset();
    }
}
</script>