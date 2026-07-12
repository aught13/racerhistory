import { Controller } from "@hotwired/stimulus";

const DEFAULT_CONFIRM_MESSAGE = "Clear all CakePHP cache engines?";

export default class extends Controller {
    static targets = ["button"];
    static values = {
        confirmMessage: String,
        loadingLabel: String,
    };

    confirmAndSubmit(event) {
        const message = this.hasConfirmMessageValue
            ? this.confirmMessageValue
            : DEFAULT_CONFIRM_MESSAGE;

        if (!window.confirm(message)) {
            event.preventDefault();
            return;
        }

        if (!this.hasButtonTarget) {
            return;
        }

        this.buttonTarget.disabled = true;
        const loadingText = this.hasLoadingLabelValue
            ? this.loadingLabelValue
            : "Clearing...";
        this.buttonTarget.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
            loadingText;
    }
}
