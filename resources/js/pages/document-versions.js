import { createApp } from "vue";

import DocumentVersionList from "../Components/DocumentVersions/DocumentVersionList.vue";

const dvl = document.getElementById("document-version-list");
if (dvl) {
    createApp(DocumentVersionList, {
        documentId: dvl.dataset.documentId,
    }).mount(dvl);
}
