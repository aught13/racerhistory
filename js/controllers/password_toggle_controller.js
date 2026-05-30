import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["input", "button"];

    connect() {
        this.syncIcon();
    }

    toggle() {
        if (!this.hasInputTarget) {
            return;
        }

        this.inputTarget.type =
            this.inputTarget.type === "password" ? "text" : "password";
        this.syncIcon();
    }

    syncIcon() {
        if (!this.hasButtonTarget || !this.hasInputTarget) {
            return;
        }

        const hidden = this.inputTarget.type === "password";
        this.buttonTarget.innerHTML = hidden
            ? '<span class="bi bi-eye"></span>'
            : '<span class="bi bi-eye-slash"></span>';
    }
}
