// import { createApp } from "vue";

// import "./bootstrap";

// import "bootstrap/dist/css/bootstrap.min.css";
// import "bootstrap/dist/js/bootstrap.bundle.min.js";

// import ExampleComponent from "./Components/ExampleComponent.vue";

// const app = createApp({});

// Đăng ký component
// app.component("example-component", ExampleComponent);

// app.mount("#app");
import './bootstrap';
import { createApp } from 'vue';

// Import components
import FolderIndex from './Components/Folders/FolderIndex.vue';
import FolderCreate from './Components/Folders/FolderCreate.vue';
import FolderEdit from './Components/Folders/FolderEdit.vue';

const app = createApp({});

// Register components globally
app.component('folder-index', FolderIndex);
app.component('folder-create', FolderCreate);
app.component('folder-edit', FolderEdit);

app.mount('#app');