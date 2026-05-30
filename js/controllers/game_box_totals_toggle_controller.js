import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["checkbox", "optionsPanel"];

    connect() {
        this.toggle();
    }

    toggle() {
        if (!this.hasCheckboxTarget || !this.hasOptionsPanelTarget) {
            return;
        }

        this.optionsPanelTarget.style.display = this.checkboxTarget.checked
            ? "block"
            : "none";
    }
}
