import { createApp } from 'vue';

// Import Folder components
import FolderIndex from '../Components/Folders/FolderIndex.vue';
import FolderCreate from '../Components/Folders/FolderCreate.vue';
import FolderEdit from '../Components/Folders/FolderEdit.vue';

const app = createApp({});

// Register Folder components globally
app.component('folder-index', FolderIndex);
app.component('folder-create', FolderCreate);
app.component('folder-edit', FolderEdit);

app.mount('#app');