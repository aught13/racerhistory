export function registerServiceWorker() {
    if (!("serviceWorker" in navigator)) {
        return;
    }

    if (window.location.pathname.startsWith("/admin")) {
        return;
    }

    window.addEventListener("load", async () => {
        try {
            await navigator.serviceWorker.register("/sw.js", { scope: "/" });
        } catch {
            // Service worker registration is non-fatal in dev/test.
        }
    });
}
