/* global bootstrap */

export function enforceAdminLightTheme() {
    const root = document.documentElement;
    if (!root) {
        return;
    }

    // Disable any active media query listener from public routes
    if (
        window.__rh_theme_mq &&
        typeof window.__rh_theme_mq.removeEventListener === "function"
    ) {
        try {
            window.__rh_theme_mq.removeEventListener(
                "change",
                window.__rh_theme_mq_listener,
            );
        } catch {
            // Ignore detach errors
        }
        window.__rh_theme_mq = null;
        window.__rh_theme_mq_listener = null;
    }

    // Inject CSS that overrides media query-based dark mode styles.
    // This prevents @media (prefers-color-scheme: dark) from applying on admin.
    if (!document.getElementById("__rh_admin_light_theme_override")) {
        const style = document.createElement("style");
        style.id = "__rh_admin_light_theme_override";
        // Bootstrap and custom CSS look for these to determine theme.
        // We inject rules with higher specificity to force light theme.
        style.textContent = `
            :root {
                color-scheme: light !important;
                /* Bootstrap light theme CSS variables */
                --bs-body-bg: #ffffff !important;
                --bs-body-color: #212529 !important;
                --bs-border-color: #dee2e6 !important;
                --bs-link-color: #0d6efd !important;
            }
            body {
                color: #212529 !important;
                background-color: #ffffff !important;
            }
            /* Force light theme regardless of media queries */
            html:not(.dark-mode),
            html[data-theme="light"],
            html[data-bs-theme="light"] {
                background-color: #ffffff !important;
                color: #212529 !important;
                color-scheme: light !important;
            }
        `;
        document.head.appendChild(style);
    }

    // Set both Bootstrap and custom theme attributes
    root.setAttribute("data-bs-theme", "light");
    root.setAttribute("data-theme", "light");
    root.dataset.theme = "light";

    // Clean up any system theme source indicator
    delete root.dataset.themeSource;

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

    // Clear Turbo cache to prevent stale cached pages (e.g., /login with dark mode)
    // from being restored
    // eslint-disable-next-line no-undef
    if (typeof Turbo !== "undefined" && Turbo.cache) {
        // eslint-disable-next-line no-undef
        Turbo.cache.clear();
    }

    // Enforce light theme immediately and disable theme system
    enforceAdminLightTheme();

    // Before Turbo renders, sync HTML tag attributes to ensure light theme is applied
    // even when transitioning from pages that have dark mode set on the <html> tag
    document.addEventListener("turbo:before-render", (event) => {
        // Dispose old Bootstrap instances to prevent ghost tooltips
        disposeBootstrap(event.detail.newBody || document);

        // Ensure HTML tag has light theme attributes before Turbo morphs
        // This is critical because Turbo only replaces <body>, not <html> attributes
        const root = document.documentElement;
        root.setAttribute("data-bs-theme", "light");
        root.setAttribute("data-theme", "light");
        root.dataset.theme = "light";
        delete root.dataset.themeSource;

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
