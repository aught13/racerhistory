/* global bootstrap */

export function enforceAdminLightTheme() {
    const root = document.documentElement;
    if (!root) {
        return;
    }

    // Set both Bootstrap and custom theme attributes
    root.setAttribute("data-bs-theme", "light");
    root.setAttribute("data-theme", "light");

    // Remove any dark mode classes
    root.classList.remove("dark-mode", "theme-dark", "dark");
    root.classList.add("light-mode", "theme-light");

    if (document.body) {
        document.body.classList.remove("dark-mode", "theme-dark", "dark");
        document.body.classList.add("light-mode", "theme-light");
    }
}

export function reinitBootstrap(root = document) {
    if (typeof bootstrap === "undefined") {
        return;
    }

    root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        bootstrap.Tooltip?.getOrCreateInstance(element);
    });

    root.querySelectorAll('[data-bs-toggle="popover"]').forEach((element) => {
        bootstrap.Popover?.getOrCreateInstance(element);
    });
}

export function disposeBootstrap(root = document) {
    if (typeof bootstrap === "undefined") {
        return;
    }

    root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        bootstrap.Tooltip?.getInstance(element)?.dispose();
    });

    root.querySelectorAll('[data-bs-toggle="popover"]').forEach((element) => {
        bootstrap.Popover?.getInstance(element)?.dispose();
    });
}

export function initAdminRuntimeLifecycle() {
    if (typeof window !== "undefined" && window.__RH_ADMIN_RUNTIME_INIT__) {
        return;
    }

    if (typeof window !== "undefined") {
        window.__RH_ADMIN_RUNTIME_INIT__ = true;
    }

    // Enforce light theme and reinit Bootstrap immediately before Turbo renders
    document.addEventListener("turbo:before-render", (event) => {
        // Dispose old Bootstrap instances to prevent ghost tooltips
        disposeBootstrap(event.detail.newBody || document);
        enforceAdminLightTheme();
    });

    document.addEventListener("turbo:load", () => {
        enforceAdminLightTheme();
        reinitBootstrap();
    });

    document.addEventListener("turbo:frame-load", () => {
        enforceAdminLightTheme();
        reinitBootstrap();
    });

    document.addEventListener("turbo:before-cache", () => {
        disposeBootstrap();
    });

    // Enforce theme and initialize Bootstrap on initial page load
    enforceAdminLightTheme();
    reinitBootstrap();
}
