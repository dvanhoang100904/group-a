import { createApp } from "vue";

import DocumentAccessList from "../Components/DocumentAccesses/DocumentAccessList.vue";

const dal = document.getElementById("document-access-list");
if (dal) {
    createApp(DocumentAccessList, {
        documentId: dal.dataset.documentId,
    }).mount(dal);
}
