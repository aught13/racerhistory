import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["backButton"];

    static values = {
        indexUrl: String,
        indexPath: String,
        viewPath: String,
    };

    connect() {
        this.updateBackButtonVisibility();
    }

    goBack(event) {
        event.preventDefault();

        const referrer = document.referrer || "";
        if (this.isFromPersonsIndex(referrer)) {
            this.navigateToIndex();
            return;
        }

        if (window.history.length > 1) {
            window.history.back();
            return;
        }

        this.navigateToIndex();
    }

    updateBackButtonVisibility() {
        if (!this.hasBackButtonTarget) {
            return;
        }

        const referrer = document.referrer || "";
        this.backButtonTarget.style.display = this.isFromPersonsIndex(referrer)
            ? "none"
            : "";
    }

    isFromPersonsIndex(referrer) {
        const indexPath = this.indexPathValue || "/admin/persons";
        const viewPath = this.viewPathValue || "/admin/persons/view";

        return (
            Boolean(referrer) &&
            referrer.includes(indexPath) &&
            !referrer.includes(viewPath)
        );
    }

    navigateToIndex() {
        if (!this.hasIndexUrlValue || !this.indexUrlValue) {
            return;
        }

        if (typeof window.__RH_NAVIGATE__ === "function") {
            window.__RH_NAVIGATE__(this.indexUrlValue);
            return;
        }

        window.location.href = this.indexUrlValue;
    }
}
