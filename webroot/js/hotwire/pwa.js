export function registerServiceWorker() {
    if (!("serviceWorker" in navigator)) {
        return;
    }

    // Avoid interfering with admin tooling unless explicitly desired.
    if (window.location.pathname.startsWith("/admin")) {
        return;
    }

    window.addEventListener("load", async () => {
        try {
            await navigator.serviceWorker.register("/sw.js", { scope: "/" });
        } catch {
            // Non-fatal. SW registration can fail in dev/test.
        }
    });
}
