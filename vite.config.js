import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: [
<<<<<<< HEAD
                "resources/js/app.js",
                "resources/js/pages/document-versions.js",
=======
                "resources/css/app.css",
                "resources/js/app.js"
>>>>>>> yen/8.1.chuyen_giao_dien_vueJs
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
<<<<<<< HEAD
    server: {
        host: true,
        port: 5173,
        hmr: { host: "localhost" },
        proxy: {
            "/api": "http://localhost:8080",
        },
    },
});
=======
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
>>>>>>> yen/8.1.chuyen_giao_dien_vueJs
