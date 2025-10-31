import { createApp } from "vue";

import DocumentAccessList from "../components/documentAccesses/DocumentAccessList.vue";

const el = document.getElementById("document-access-list");
if (el) {
    createApp(DocumentAccessList, {
        documentId: el.dataset.documentId,
    }).mount(el);
}
