import { Application } from "@hotwired/stimulus";

import ConsentController from "../controllers/consent_controller.js";

describe("consent controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = "";
        window.dataLayer = [];
        delete window.gtag;
        delete window.__RH_GOOGLE_CONSENT_DEFAULTED__;
        delete window.__RH_CONSENT_STATE__;
        delete window.CookieConsentInstance;
    });

    afterEach(() => {
        application?.stop();
        application = null;
        document.body.innerHTML = "";
    });

    test("pushes denied Google Consent Mode v2 defaults on connect", async () => {
        document.body.innerHTML = `<div data-controller="consent"></div>`;

        application = Application.start();
        application.register("consent", ConsentController);
        await Promise.resolve();
        await Promise.resolve();

        const defaultConsent = window.dataLayer.find(
            (entry) => entry[0] === "consent" && entry[1] === "default",
        );

        expect(defaultConsent).toBeDefined();
        expect(defaultConsent[2]).toMatchObject({
            ad_storage: "denied",
            analytics_storage: "denied",
            ad_user_data: "denied",
            ad_personalization: "denied",
        });
        expect(window.CookieConsentInstance.acceptedCategories).toEqual([]);
    });

    test("maps accepted advertising consent to a granted gtag update", () => {
        const gtag = jest.fn();
        window.gtag = gtag;
        const controller = Object.create(ConsentController.prototype);

        controller.syncPreferences({
            cookie: { categories: ["necessary", "ads"] },
        });

        expect(gtag).toHaveBeenCalledWith("consent", "update", {
            ad_storage: "granted",
            analytics_storage: "denied",
            ad_user_data: "granted",
            ad_personalization: "granted",
        });
        expect(window.CookieConsentInstance.acceptedCategories).toEqual([
            "necessary",
            "ads",
        ]);
    });

    test("handles the privacy choices action without throwing", () => {
        const controller = Object.create(ConsentController.prototype);
        const event = { preventDefault: jest.fn() };

        expect(() => controller.showPreferences(event)).not.toThrow();
        expect(event.preventDefault).toHaveBeenCalledTimes(1);
    });
});
