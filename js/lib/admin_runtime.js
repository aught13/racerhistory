/* global bootstrap */

export function enforceAdminLightTheme() {
    const root = document.documentElement;
    if (!root) {
        return;
    }

    root.setAttribute("data-bs-theme", "light");
    root.setAttribute("data-theme", "light");
    root.classList.remove("dark-mode", "theme-dark");

    if (document.body) {
        document.body.classList.remove("dark-mode", "theme-dark");
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

    document.addEventListener("turbo:before-render", enforceAdminLightTheme);
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

    enforceAdminLightTheme();
    reinitBootstrap();
}
