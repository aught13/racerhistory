import { defineConfig } from "vite";

export default defineConfig({
    base: "/dist/",
    build: {
        manifest: "manifest.json",
        outDir: "webroot/dist",
        emptyOutDir: true,
        rollupOptions: {
            input: {
                main: "js/main.js",
            },
        },
    },
    server: {
        host: "0.0.0.0",
        port: 5173,
        strictPort: true,
        origin: "http://localhost:5173",
    },
});
