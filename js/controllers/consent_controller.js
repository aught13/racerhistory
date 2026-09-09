import { Controller } from "@hotwired/stimulus";
import {
    run as runCookieConsent,
    showPreferences as showCookiePreferences,
} from "vanilla-cookieconsent";
import "vanilla-cookieconsent/dist/cookieconsent.css";

function ensureGoogleConsentDefaults() {
    if (typeof window === "undefined") {
        return;
    }

    window.dataLayer = window.dataLayer || [];
    window.gtag =
        window.gtag ||
        function gtag() {
            window.dataLayer.push(arguments);
        };

    if (window.__RH_GOOGLE_CONSENT_DEFAULTED__) {
        return;
    }

    window.gtag("consent", "default", {
        ad_storage: "denied",
        analytics_storage: "denied",
        ad_user_data: "denied",
        ad_personalization: "denied",
        wait_for_update: 500,
    });
    window.__RH_GOOGLE_CONSENT_DEFAULTED__ = true;
    window.CookieConsentInstance = {
        acceptedCategories: [],
        rejectedCategories: ["ads", "analytics"],
    };
}

function getAcceptedCategories(preferences, cookie) {
    if (Array.isArray(preferences?.acceptedCategories)) {
        return preferences.acceptedCategories;
    }

    if (Array.isArray(cookie?.categories)) {
        return cookie.categories;
    }

    return [];
}

function refreshGoogleTag() {
    const pubads = window.googletag?.pubads?.();
    if (typeof pubads?.refresh !== "function") {
        return;
    }

    pubads.refresh();
}

export default class extends Controller {
    connect() {
        ensureGoogleConsentDefaults();

        if (typeof window === "undefined") {
            return;
        }

        const state = window.__RH_CONSENT_STATE__ || {
            started: false,
        };
        window.__RH_CONSENT_STATE__ = state;

        if (state.started) {
            this.syncPreferences(window.CookieConsentInstance);
            return;
        }

        state.started = true;
        void runCookieConsent({
            mode: "opt-in",
            autoShow: true,
            categories: {
                necessary: {
                    enabled: true,
                    readOnly: true,
                },
                analytics: {},
                ads: {},
            },
            language: {
                default: "en",
                translations: {
                    en: {
                        consentModal: {
                            title: "Privacy choices",
                            description:
                                "Choose whether optional analytics and advertising cookies may be used.",
                            acceptAllBtn: "Accept all",
                            acceptNecessaryBtn: "Reject optional",
                            showPreferencesBtn: "Manage choices",
                        },
                        preferencesModal: {
                            title: "Privacy preferences",
                            acceptAllBtn: "Accept all",
                            acceptNecessaryBtn: "Reject optional",
                            savePreferencesBtn: "Save choices",
                            closeIconLabel: "Close",
                            sections: [
                                {
                                    title: "Cookie use",
                                    description:
                                        "Necessary cookies keep the site working. Optional categories can be changed at any time.",
                                },
                                {
                                    title: "Analytics",
                                    description:
                                        "Helps us understand site usage.",
                                    linkedCategory: "analytics",
                                },
                                {
                                    title: "Advertising",
                                    description:
                                        "Allows personalized advertising and measurement when enabled.",
                                    linkedCategory: "ads",
                                },
                            ],
                        },
                    },
                },
            },
            onConsent: ({ cookie }) => {
                this.syncPreferences({ cookie });
            },
            onChange: ({ cookie, changedCategories }) => {
                this.syncPreferences({ cookie });
                if (changedCategories?.includes("ads")) {
                    refreshGoogleTag();
                }
            },
        }).catch(() => {
            state.failed = true;
        });
    }

    disconnect() {
        this.disconnected = true;
    }

    showPreferences(event) {
        event?.preventDefault();
        showCookiePreferences(true);
    }

    syncPreferences(preferences) {
        if (typeof window === "undefined") {
            return;
        }

        const cookie = preferences?.cookie || preferences?.cookieValue;
        const acceptedCategories = getAcceptedCategories(preferences, cookie);
        const rejectedCategories = Array.isArray(
            preferences?.rejectedCategories,
        )
            ? preferences.rejectedCategories
            : ["ads", "analytics"].filter(
                  (category) => !acceptedCategories.includes(category),
              );

        window.CookieConsentInstance = {
            ...preferences,
            cookie,
            acceptedCategories,
            rejectedCategories,
        };

        const adsGranted = acceptedCategories.includes("ads");
        const analyticsGranted = acceptedCategories.includes("analytics");
        window.gtag?.("consent", "update", {
            ad_storage: adsGranted ? "granted" : "denied",
            analytics_storage: analyticsGranted ? "granted" : "denied",
            ad_user_data: adsGranted ? "granted" : "denied",
            ad_personalization: adsGranted ? "granted" : "denied",
        });
    }
}
