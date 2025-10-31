import { createApp } from 'vue'
import UploadDocuments from '../Components/DocumentUploads/UploadDocuments.vue'

// Mount Vue app vào #document-upload
const el = document.getElementById('document-upload')
if (el) {
    createApp(UploadDocuments).mount(el)
} else {
    console.error('❌ Không tìm thấy #document-upload element!')
}