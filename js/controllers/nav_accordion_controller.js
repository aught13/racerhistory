import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["toggle", "panel"];

    connect() {
        this.syncToLocation();
    }

    toggle(event) {
        const button = event.currentTarget;
        const panel = this.findPanel(button);

        if (!panel) {
            return;
        }

        const nextState = button.getAttribute("aria-expanded") !== "true";
        this.setExpanded(button, panel, nextState);
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

        panel.hidden = !expanded;
        panel.classList.toggle("d-none", !expanded);
        panel.dataset.navOpen = expanded ? "true" : "false";
    }
}
