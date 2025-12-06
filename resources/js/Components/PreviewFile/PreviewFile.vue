<template>
  <div class="preview-wrapper">

    <!-- PDF -->
    <iframe
      v-if="isPdf"
      :src="fileUrl"
      class="w-full h-[650px] border rounded"
    ></iframe>

    <!-- IMAGE -->  
    <img
      v-else-if="isImage"
      :src="fileUrl"
      class="max-w-full max-h-[650px] rounded shadow"
    />

    <!-- DOCX -->
    <div
      v-else-if="isDocx"
      ref="docxContainer"
      class="docx-container p-4 border rounded bg-white shadow-sm"
    ></div>

    <!-- GOOGLE DOCS VIEWER (Excel, PowerPoint, Doc cũ) -->
    <iframe
      v-else-if="isGoogleDocs"
      :src="googleDocsUrl"
      class="w-full h-[650px] border rounded"
    ></iframe>

    <!-- TEXT FILE -->
    <pre
      v-else-if="isText"
      class="p-4 bg-gray-100 rounded text-sm overflow-auto max-h-[650px]"
    ><code>{{ textContent }}</code></pre>

    <!-- FALLBACK -->
    <div v-else class="text-center py-10 text-gray-500">
      <i class="fas fa-file fa-3x mb-3"></i>
      <p>Không thể hiển thị tài liệu này.</p>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { renderAsync } from "docx-preview";

const props = defineProps({
  fileUrl: { type: String, required: true },
  fileName: { type: String, required: true }
});

// --- Detect File Type ---
const ext = props.fileName.split(".").pop().toLowerCase();

const isPdf = ext === "pdf";
const isImage = ["jpg", "jpeg", "png", "gif", "webp"].includes(ext);
const isDocx = ext === "docx";
const isGoogleDocs = ["xls", "xlsx", "ppt", "pptx", "doc"].includes(ext);
const isText = ["txt", "json", "md", "log"].includes(ext);

// --- Google Docs Viewer ---
const googleDocsUrl = `https://docs.google.com/viewer?url=${props.fileUrl}&embedded=true`;

const docxContainer = ref(null);
const textContent = ref("");

// --- Lifecycle ---
onMounted(async () => {
  
  // DOCX RENDERING
  if (isDocx) {
    try {
      const response = await fetch(props.fileUrl);
      const blob = await response.blob();
      await renderAsync(blob, docxContainer.value);
    } catch (e) {
      console.error("DOCX preview error:", e);
    }
  }

  // TEXT FILE PREVIEW
  if (isText) {
    const response = await fetch(props.fileUrl);
    textContent.value = await response.text();
  }
});
</script>

<style scoped>
.docx-container * {
  max-width: 100%;
}
</style>
