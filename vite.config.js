import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/js/app.js",
                "resources/js/pages/document-versions.js",
                "resources/js/pages/document-upload.js",
            ],
            refresh: true,
        }),
        vue(),
    ],
    server: {
        host: true,
        port: 5173,
        hmr: { host: "localhost" },
        proxy: {
            "/api": "http://localhost:8080",
        },
    },
});
