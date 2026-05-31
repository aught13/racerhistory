/**
 * admin-dashboard.js
 *
 * Handles admin dashboard interactions:
 * - Confirm before clearing cache
 * - Spinner/disable on submit
 *
 * Initialises on both DOMContentLoaded and turbo:load.
 */
(function () {
    "use strict";

    function initDashboard() {
        const form = document.getElementById("clear-cache-form");
        if (!form) return;

        form.addEventListener("submit", function (e) {
            const btn = form.querySelector("#btn-clear-cache");
            if (!btn) return;

            // Show confirmation
            if (!window.confirm("Clear all CakePHP cache engines?")) {
                e.preventDefault();
                return;
            }

            // Disable button and show spinner
            btn.disabled = true;
            btn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Clearing…';
        });
    }

    // Initialise on DOMContentLoaded and turbo:load
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initDashboard);
    } else {
        initDashboard();
    }
    document.addEventListener("turbo:load", initDashboard);

    // Export for testing
    if (typeof module !== "undefined" && module.exports) {
        module.exports = { initDashboard };
    }
})();
