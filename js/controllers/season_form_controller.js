import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["startYear", "endYear"];

    connect() {
        if (!this.hasStartYearTarget) {
            return;
        }

        this.boundBlur = () => this.populateEndYear();
        this.startYearTarget.addEventListener("blur", this.boundBlur);
    }

    disconnect() {
        if (this.hasStartYearTarget && this.boundBlur) {
            this.startYearTarget.removeEventListener("blur", this.boundBlur);
        }
    }

    populateEndYear() {
        if (!this.hasStartYearTarget || !this.hasEndYearTarget) {
            return;
        }

        const startYear = parseInt(this.startYearTarget.value, 10);
        if (!Number.isFinite(startYear)) {
            return;
        }

        if (this.endYearTarget.value.trim() !== "") {
            return;
        }

        this.endYearTarget.value = String(startYear + 1);
    }
}
