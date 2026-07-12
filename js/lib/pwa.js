/**
 * Register the service worker for the public app (excluding admin pages).
 * Returns a promise that resolves when registration is complete (or fails).
 * @returns {Promise<void>}
 */
export async function registerServiceWorker() {
    if (!("serviceWorker" in navigator)) {
        return;
    }

    if (window.location.pathname.startsWith("/admin")) {
        return;
    }

    try {
        const registration = await navigator.serviceWorker.register("/sw.js", {
            scope: "/",
        });

        // Ensure service worker is activated before considering registration complete
        await navigator.serviceWorker.ready;

        return registration;
    } catch (error) {
        // Service worker registration is non-fatal in dev/test environments.
        // Log for debugging purposes but don't throw.
        if (typeof console !== "undefined" && console.warn) {
            console.warn("Service worker registration failed:", error);
        }
    }
}
