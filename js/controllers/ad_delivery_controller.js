import { Controller } from "@hotwired/stimulus";

import {
    destroyGoogleAdSlotSection,
    installGoogleAdScriptCleanup,
    initGoogleAdSlotSection,
} from "../lib/google_ads.js";

installGoogleAdScriptCleanup();

export default class extends Controller {
    static targets = ["container", "template"];

    static values = {
        mode: String,
        slot: String,
        googleSlotId: String,
        googleClient: String,
        googleFormat: String,
        googleLayout: String,
        googleLayoutKey: String,
        googleFullWidthResponsive: String,
    };

    connect() {
        if (!this.hasContainerTarget) {
            return;
        }

        this.clearContainer();

        if (this.isGoogleMode()) {
            this.renderGoogleAd();
            return;
        }

        this.renderCustomAd();
    }

    disconnect() {
        if (this.isGoogleMode()) {
            destroyGoogleAdSlotSection(this.element);
        }

        this.clearContainer();
    }

    isGoogleMode() {
        return this.modeValue === "google";
    }

    clearContainer() {
        if (!this.hasContainerTarget) {
            return;
        }

        this.containerTarget.innerHTML = "";
    }

    renderCustomAd() {
        if (!this.hasTemplateTarget) {
            return;
        }

        const fragment = this.templateTarget.content.cloneNode(true);
        this.containerTarget.appendChild(fragment);
        this.element.setAttribute("data-rh-ad-initialized", "1");
    }

    renderGoogleAd() {
        const adElement = document.createElement("ins");
        adElement.className = "adsbygoogle";
        adElement.style.display = "block";
        this.applyGoogleAttributes(adElement);

        this.containerTarget.appendChild(adElement);
        initGoogleAdSlotSection(this.element);
    }

    applyGoogleAttributes(adElement) {
        this.assignDataAttribute(
            adElement,
            "data-ad-slot",
            this.googleSlotIdValue,
        );
        this.assignDataAttribute(
            adElement,
            "data-ad-client",
            this.googleClientValue,
        );
        this.assignDataAttribute(
            adElement,
            "data-ad-format",
            this.googleFormatValue,
        );
        this.assignDataAttribute(
            adElement,
            "data-ad-layout",
            this.googleLayoutValue,
        );
        this.assignDataAttribute(
            adElement,
            "data-ad-layout-key",
            this.googleLayoutKeyValue,
        );

        const fullWidthResponsive = this.googleFullWidthResponsiveValue.trim();
        if (fullWidthResponsive !== "") {
            adElement.setAttribute(
                "data-full-width-responsive",
                fullWidthResponsive,
            );
        } else {
            adElement.setAttribute("data-full-width-responsive", "true");
        }
    }

    assignDataAttribute(element, attributeName, value) {
        const normalizedValue = String(value || "").trim();
        if (normalizedValue === "") {
            return;
        }

        element.setAttribute(attributeName, normalizedValue);
    }
}
