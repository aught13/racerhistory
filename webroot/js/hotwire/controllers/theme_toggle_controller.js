import { Controller } from "@hotwired/stimulus";

import { getThemePreference, setThemePreference } from "../theme.js";

export default class extends Controller {
    static targets = ["label"];

    connect() {
        this.sync();
    }

    toggle() {
        const current = getThemePreference();
        const next =
            current === "system"
                ? "light"
                : current === "light"
                  ? "dark"
                  : "system";
        setThemePreference(next);
        this.sync();
    }

    sync() {
        const mode = getThemePreference();

        this.element.setAttribute(
            "aria-pressed",
            mode === "dark" ? "true" : "false",
        );
        this.element.dataset.themeMode = mode;

        if (this.hasLabelTarget) {
            const text =
                mode === "system"
                    ? "System"
                    : mode === "light"
                      ? "Light"
                      : "Dark";
            this.labelTarget.textContent = text;
        }

        const title =
            mode === "system"
                ? "Theme: system (toggle to light)"
                : mode === "light"
                  ? "Theme: light (toggle to dark)"
                  : "Theme: dark (toggle to system)";

        this.element.setAttribute("title", title);
        this.element.setAttribute("aria-label", title);
    }
}
