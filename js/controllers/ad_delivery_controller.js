import { Controller } from "@hotwired/stimulus";

import {
    destroyGoogleAdSlotSection,
    installGoogleAdScriptCleanup,
    initGptAdSlotSection,
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
        sizesDesktop: Array,
        sizesMobile: Array,
        gptUnitPath: String,
    };

    connect() {
        this.cancelled = false;
        this.observer = null;

        if (!this.hasContainerTarget) {
            return;
        }

        this.clearContainer();

        if (this.isCustomMode()) {
            this.renderCustomAd();
            return;
        }

        void this.initializeProgrammaticAd();
    }

    disconnect() {
        this.cancelled = true;
        this.observer?.disconnect();
        this.observer = null;

        if (this.isGoogleMode() || this.isGptMode()) {
            destroyGoogleAdSlotSection(this.element);
        }

        this.clearContainer();
    }

    isCustomMode() {
        return !this.isGoogleMode() && !this.isGptMode();
    }

    isGoogleMode() {
        return this.modeValue === "google";
    }

    isGptMode() {
        return this.modeValue === "gpt";
    }

    async initializeProgrammaticAd() {
        if (await this.checkAdBlocker()) {
            if (!this.cancelled) {
                this.renderAdBlockerFallback();
            }
            return;
        }

        if (this.cancelled) {
            return;
        }

        this.observeSlot();
    }

    async checkAdBlocker() {
        if (typeof document === "undefined" || !document.body) {
            return false;
        }

        const bait = document.createElement("div");
        bait.className = "pub_300x250 pub_300x250m pub_ad_box ad-advertisement";
        bait.setAttribute(
            "style",
            "position:absolute;top:0;left:0;width:1px;height:1px;opacity:0;",
        );
        document.body.appendChild(bait);

        await new Promise((resolve) => {
            if (typeof window.requestAnimationFrame === "function") {
                window.requestAnimationFrame(resolve);
                return;
            }

            window.setTimeout(resolve, 16);
        });

        const blocked = bait.offsetParent === null || bait.clientHeight === 0;
        bait.remove();

        return blocked;
    }

    observeSlot() {
        if (typeof window.IntersectionObserver !== "function") {
            this.renderProgrammaticAd();
            return;
        }

        this.observer = new window.IntersectionObserver(
            (entries) => {
                const entry = entries.find((item) => item.isIntersecting);
                if (!entry || this.cancelled) {
                    return;
                }

                this.observer?.unobserve(this.element);
                this.observer?.disconnect();
                this.observer = null;
                this.renderProgrammaticAd();
            },
            { rootMargin: "200px" },
        );
        this.observer.observe(this.element);
    }

    renderProgrammaticAd() {
        if (this.isGoogleMode()) {
            this.renderGoogleAd();
            return;
        }

        this.renderGptAd();
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

    renderAdBlockerFallback() {
        this.clearContainer();

        if (this.hasTemplateTarget) {
            this.containerTarget.appendChild(
                this.templateTarget.content.cloneNode(true),
            );
        } else {
            const fallback = document.createElement("p");
            fallback.className = "rh-ad-slot__fallback";
            fallback.textContent = "Advertising is unavailable for this visit.";
            this.containerTarget.appendChild(fallback);
        }

        this.element.classList.add("rh-ad-slot--blocked");
        this.element.setAttribute("data-rh-ad-blocked", "1");
    }

    renderGoogleAd() {
        const adElement = document.createElement("ins");
        adElement.className = "adsbygoogle";
        adElement.style.display = "block";
        this.applyGoogleAttributes(adElement);

        this.containerTarget.appendChild(adElement);
        initGoogleAdSlotSection(this.element);
    }

    renderGptAd() {
        const pubads = window.googletag?.pubads?.();
        if (
            !this.hasTrackingConsent() &&
            typeof pubads?.setPrivacySettings === "function"
        ) {
            pubads.setPrivacySettings({ nonPersonalizedAds: true });
        }

        const elementId = this.createGptElementId();
        const adElement = document.createElement("div");
        adElement.id = elementId;
        adElement.setAttribute("aria-label", "Advertisement");
        this.containerTarget.appendChild(adElement);
        this.element.dataset.googleTagSlotId = elementId;

        initGptAdSlotSection(this.element, {
            unitPath: this.gptUnitPathValue,
            sizesDesktop: this.sizesDesktopValue,
            sizesMobile: this.sizesMobileValue,
            elementId,
        });
    }

    createGptElementId() {
        const slotName = this.slotValue.trim().replace(/[^a-z0-9_-]/gi, "-");
        const sequence = (window.__RH_GPT_ELEMENT_SEQUENCE__ || 0) + 1;
        window.__RH_GPT_ELEMENT_SEQUENCE__ = sequence;

        return `rh-gpt-${slotName || "slot"}-${sequence}`;
    }

    hasTrackingConsent() {
        const payload = window.CookieConsentInstance;
        const acceptedCategories = payload?.acceptedCategories;
        const cookieCategories = payload?.cookie?.categories;

        return (
            (Array.isArray(acceptedCategories) &&
                acceptedCategories.includes("ads")) ||
            (Array.isArray(cookieCategories) &&
                cookieCategories.includes("ads"))
        );
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
