import { Controller } from "@hotwired/stimulus";

const STORAGE_KEY = "rh_admin_sidebar_collapsed";
const DESKTOP_BREAKPOINT = 992;
const ULTRAWIDE_BREAKPOINT = 1600;

/**
 * AdminLTE 4 layout controller.
 *
 * Manages the AdminLTE sidebar state across Turbo page visits.
 *
 * Desktop (>= lg): toggles `sidebar-collapse` for mini mode.
 * Mobile (< lg): toggles `sidebar-open` for off-canvas mode.
 *
 * State is persisted to `localStorage` under `rh_admin_sidebar_collapsed` so the
 * desktop sidebar remembers its collapsed/expanded state across full page loads
 * and Turbo Drive navigations.
 *
 * Usage:
 *   <div data-controller="admin-layout">
 *     …
 *     <button data-action="click->admin-layout#toggle">☰</button>
 *   </div>
 */
export default class extends Controller {
    /**
     * Called by Stimulus after the element is connected to the DOM.
     * Runs on every Turbo Drive page visit because the <body> is replaced,
     * so the controller re-connects and must re-apply the persisted state.
     */
    connect() {
        this.restoreState();
        this.applyLayoutVariant();
        document.addEventListener("turbo:load", this._handleTurboLoad, {
            once: false,
        });
        window.addEventListener("resize", this._handleResize, { once: false });
    }

    disconnect() {
        document.removeEventListener("turbo:load", this._handleTurboLoad);
        window.removeEventListener("resize", this._handleResize);
    }

    /**
     * Toggle sidebar between expanded and mini-collapsed.
     * Persists the new state to localStorage.
     *
     * @param {Event} event - click event from the toggle button
     */
    toggle(event) {
        event.preventDefault();

        if (this._isMobile()) {
            document.body.classList.toggle("sidebar-open");
            return;
        }

        const isCollapsed = document.body.classList.toggle("sidebar-collapse");
        this._persist(isCollapsed);
    }

    /**
     * Close mobile off-canvas sidebar.
     * Safe to call on desktop (it is a no-op visual change there).
     */
    closeMobile() {
        document.body.classList.remove("sidebar-open");
    }

    /**
     * Read localStorage and apply the correct body class.
     * Called from `connect()` so it runs on every Turbo visit.
     */
    restoreState() {
        try {
            const collapsed = localStorage.getItem(STORAGE_KEY) === "1";
            document.body.classList.toggle("sidebar-collapse", collapsed);
        } catch {
            // Ignore errors in restrictive environments (private browsing, etc.)
        }

        // Turbo/initial render should always start with closed mobile drawer.
        this.closeMobile();
    }

    /**
     * Persist collapsed state.
     *
     * @param {boolean} collapsed
     */
    _persist(collapsed) {
        try {
            localStorage.setItem(STORAGE_KEY, collapsed ? "1" : "0");
        } catch {
            // Ignore
        }
    }

    /**
     * Bound turbo:load handler — restores state after each Turbo Drive visit
     * because Turbo replaces <body>, resetting any dynamically set classes.
     */
    _handleTurboLoad = () => {
        this.restoreState();
        this.applyLayoutVariant();
    };

    _handleResize = () => {
        // Ensure we never carry an open mobile drawer into desktop layout.
        if (!this._isMobile()) {
            this.closeMobile();
        }

        this.applyLayoutVariant();
    };

    applyLayoutVariant() {
        const width = window.innerWidth;
        const variant =
            width >= ULTRAWIDE_BREAKPOINT
                ? "ultrawide"
                : this._isMobile()
                  ? "mobile"
                  : "desktop";

        document.body.dataset.layoutVariant = variant;
        document.body.classList.toggle("rh-layout--mobile", variant === "mobile");
        document.body.classList.toggle("rh-layout--desktop", variant === "desktop");
        document.body.classList.toggle(
            "rh-layout--ultrawide",
            variant === "ultrawide",
        );
    }

    _isMobile() {
        return window.innerWidth < DESKTOP_BREAKPOINT;
    }
}
