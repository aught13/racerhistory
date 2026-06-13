import { Controller } from "@hotwired/stimulus";

const DESKTOP_BREAKPOINT = 992;
const SIDEBAR_COLLAPSE_STORAGE_KEY = "rh_admin_sidebar_collapsed";

export default class extends Controller {
    static targets = ["toggle", "panel"];

    connect() {
        this.syncToLocation();
    }

    toggle(event) {
        event.preventDefault();

        const button = event.currentTarget;
        const panel = this.findPanel(button);

        if (!panel) {
            return;
        }

        const desktopCollapsed =
            window.innerWidth >= DESKTOP_BREAKPOINT &&
            document.body.classList.contains("sidebar-collapse");

        // In AdminLTE mini mode, first click should expand sidebar and reveal
        // the clicked group instead of "toggling" it invisibly in a narrow rail.
        if (desktopCollapsed) {
            document.body.classList.remove("sidebar-collapse");

            try {
                localStorage.setItem(SIDEBAR_COLLAPSE_STORAGE_KEY, "0");
            } catch {
                // Ignore storage failures.
            }

            this.setExpanded(button, panel, true);
            this.toggleTargets.forEach((candidate) => {
                if (candidate === button) {
                    return;
                }

                this.setExpanded(candidate, this.findPanel(candidate), false);
            });

            return;
        }

        const nextState = button.getAttribute("aria-expanded") !== "true";
        this.setExpanded(button, panel, nextState);

        // Keep accordion interaction predictable: opening one group closes siblings.
        if (nextState) {
            this.toggleTargets.forEach((candidate) => {
                if (candidate === button) {
                    return;
                }

                this.setExpanded(candidate, this.findPanel(candidate), false);
            });
        }
    }

    syncToLocation() {
        const currentTarget = `${window.location.pathname}${window.location.search}`;

        this.toggleTargets.forEach((button) => {
            const panel = this.findPanel(button);
            const prefixes = this.getPrefixes(button);
            const isOpen = prefixes.some((prefix) =>
                currentTarget.startsWith(prefix),
            );

            this.setExpanded(button, panel, isOpen);
        });
    }

    getPrefixes(button) {
        const prefixText = button.dataset.navAccordionPrefix || "";

        return prefixText
            .split("|")
            .map((prefix) => prefix.trim())
            .filter(Boolean);
    }

    findPanel(button) {
        const panel = button.nextElementSibling;

        if (!panel || !panel.matches('[data-nav-accordion-target="panel"]')) {
            return null;
        }

        return panel;
    }

    setExpanded(button, panel, expanded) {
        button.setAttribute("aria-expanded", expanded ? "true" : "false");

        if (!panel) {
            return;
        }

        const parentItem = button.closest(".nav-item");
        if (parentItem) {
            parentItem.classList.toggle("menu-open", expanded);
        }

        panel.hidden = !expanded;
        panel.dataset.navOpen = expanded ? "true" : "false";
    }
}
