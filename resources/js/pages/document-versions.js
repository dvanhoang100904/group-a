import { createApp } from "vue";

import DocumentVersionList from "../components/documentVersions/DocumentVersionList.vue";

const el = document.getElementById("document-version-list");
if (el) {
    createApp(DocumentVersionList, {
        documentId: el.dataset.documentId,
    }).mount(el);
}
