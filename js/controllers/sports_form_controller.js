import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["form"];

    connect() {
        if (!this.hasFormTarget) {
            return;
        }

        this.boundSubmit = (event) => this.handleSubmit(event);
        this.formTarget.addEventListener("submit", this.boundSubmit);
    }

    disconnect() {
        if (!this.hasFormTarget || !this.boundSubmit) {
            return;
        }

        this.formTarget.removeEventListener("submit", this.boundSubmit);
    }

    handleSubmit(event) {
        if (!this.formTarget.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        this.formTarget.classList.add("was-validated");
    }
}
