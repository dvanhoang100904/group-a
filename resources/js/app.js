import "./bootstrap";
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap/dist/js/bootstrap.bundle.min.js";
import "../css/app.css";
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
