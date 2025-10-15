import { createApp } from "vue";

import "./bootstrap";

import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap/dist/js/bootstrap.bundle.min.js";

import ExampleComponent from "./Components/ExampleComponent.vue";

const app = createApp({});

// Đăng ký component
app.component("example-component", ExampleComponent);

app.mount("#app");
