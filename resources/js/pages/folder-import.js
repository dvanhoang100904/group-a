import { createApp } from 'vue';

// Import Folder components
import { FolderIndex, FolderCreate, FolderEdit } from '../Components/Folders/index.js';

const app = createApp({});

// Register Folder components globally
app.component('folder-index', FolderIndex);
app.component('folder-create', FolderCreate);
app.component('folder-edit', FolderEdit);

app.mount('#app');