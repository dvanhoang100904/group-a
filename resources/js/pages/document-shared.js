import { createApp } from "vue";

import DocumentSharedList from "../Components/DocumentShared/DocumentSharedList.vue";

const dsl = document.getElementById("document-shared-list");

if (dsl) {
    createApp(DocumentSharedList).mount(dsl);
}
