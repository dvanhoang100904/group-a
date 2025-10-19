import { createApp } from "vue";

import "../../css/app.css";
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap/dist/js/bootstrap.bundle.min.js";

import DocumentVersionList from "../components/documentVersions/DocumentVersionList.vue";

const el = document.getElementById("document-version-list");
if (el) {
    createApp(DocumentVersionList, {
        documentId: el.dataset.documentId,
    }).mount(el);
}
