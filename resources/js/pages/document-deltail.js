import { createApp } from 'vue'
import DocumentDetailModal from '../Components/DocumentList/DocumentDetailModal.vue'

const app = createApp({
  data() {
    return {
      visible: true,
      documentId: window.location.pathname.split('/').pop(), // lấy ID từ URL
    }
  },
  template: `
    <DocumentDetailModal 
      :visible="visible"
      :document-id="documentId"
      @close="visible = false"
    />
  `
})

app.component('DocumentDetailModal', DocumentDetailModal)
app.mount('#document-detail-app')
