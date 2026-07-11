export function registerServiceWorker() {
    if (!("serviceWorker" in navigator)) {
        return;
    }

    if (window.location.pathname.startsWith("/admin")) {
        return;
    }

    const registerWorker = async () => {
        try {
            await navigator.serviceWorker.register("/sw.js", { scope: "/" });
        } catch {
            // Service worker registration is non-fatal in dev/test.
        }
    };

    // If document is already loaded, register immediately.
    // Otherwise, wait for the load event. This handles both cases where
    // this code runs before or after the browser's load event has fired.
    if (document.readyState === "loading") {
        window.addEventListener("load", registerWorker);
    } else {
        void registerWorker();
    }
}
