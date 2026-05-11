/**
 * admin-turbo.mjs
 *
 * Minimal Hotwire Turbo initialization for the admin layout.
 * Enables Turbo Drive (SPA-like page transitions) and Turbo Frames
 * (partial page updates within the admin-content frame).
 *
 * Initialises on both DOMContentLoaded and turbo:load.
 */
import * as Turbo from "@hotwired/turbo";

// Expose Turbo globally for debugging and for scripts that check window.Turbo
window.Turbo = Turbo;

/**
 * Admin UI intentionally runs in light mode only.
 * Turbo preserves document state across visits, so normalize theme each time.
 */
function enforceAdminLightTheme() {
    const root = document.documentElement;
    if (!root) return;

    root.setAttribute("data-bs-theme", "light");
    root.setAttribute("data-theme", "light");
    root.classList.remove("dark-mode", "theme-dark");

    if (document.body) {
        document.body.classList.remove("dark-mode", "theme-dark");
    }
}

/**
 * Re-initialise Bootstrap components (tooltips, popovers, modals, etc.)
 * that were inserted into the DOM by a Turbo Frame swap or Turbo Drive visit.
 */
function reinitBootstrap() {
    if (typeof bootstrap === "undefined") return;

    // Re-initialise tooltips
    document
        .querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach((el) => bootstrap.Tooltip.getOrCreateInstance(el));

    // Re-initialise popovers
    document
        .querySelectorAll('[data-bs-toggle="popover"]')
        .forEach((el) => bootstrap.Popover.getOrCreateInstance(el));
}

// Cleanup before Turbo caches the page so duplicated listeners/state are avoided.
document.addEventListener("turbo:before-cache", () => {
    // Destroy Bootstrap tooltips/popovers so they don't linger in the cache snapshot
    document
        .querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach((el) => bootstrap.Tooltip.getInstance(el)?.dispose());
    document
        .querySelectorAll('[data-bs-toggle="popover"]')
        .forEach((el) => bootstrap.Popover.getInstance(el)?.dispose());
});

// Re-initialise after every Turbo navigation (Drive visits + Frame loads)
document.addEventListener("turbo:before-render", enforceAdminLightTheme);
document.addEventListener("turbo:load", () => {
    enforceAdminLightTheme();
    reinitBootstrap();
});
document.addEventListener("turbo:frame-load", () => {
    enforceAdminLightTheme();
    reinitBootstrap();
});

// Also run once on first load
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
        enforceAdminLightTheme();
        reinitBootstrap();
    });
} else {
    enforceAdminLightTheme();
    reinitBootstrap();
}

export { enforceAdminLightTheme, reinitBootstrap };
