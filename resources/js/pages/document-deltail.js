import { createApp } from 'vue';
import DocumentDetailModal from '../Components/DocumentList/DocumentDetailModal.vue';
import PreviewFile from '../Components/PreviewFile/PreviewFile.vue'; // đúng đường dẫn

const app = createApp({
  data() {
    return {
      visible: true,
      documentId: window.location.pathname.split('/').pop(),
    }
  },
  template: `
    <DocumentDetailModal 
      :visible="visible"
      :document-id="documentId"
      @close="visible = false"
    />
  `
});

app.component('DocumentDetailModal', DocumentDetailModal);
app.component('PreviewFile', PreviewFile);
app.mount('#document-detail-app');
