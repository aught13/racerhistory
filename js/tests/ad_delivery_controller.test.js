/* global afterEach, beforeEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import AdDeliveryController from "../controllers/ad_delivery_controller.js";

describe("ad-delivery controller", () => {
    let application;
    let checkAdBlocker;

    const flush = async () => {
        await Promise.resolve();
        await Promise.resolve();
    };

    beforeEach(() => {
        document.body.innerHTML = "";
        delete window.adsbygoogle;
        delete window.googletag;
        checkAdBlocker = AdDeliveryController.prototype.checkAdBlocker;
        AdDeliveryController.prototype.checkAdBlocker = jest
            .fn()
            .mockResolvedValue(false);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        delete window.adsbygoogle;
        delete window.googletag;
        AdDeliveryController.prototype.checkAdBlocker = checkAdBlocker;
    });

    test("renders custom template content on connect", async () => {
        document.body.innerHTML = `
            <section data-controller="ad-delivery" data-ad-delivery-mode-value="custom">
                <div data-ad-delivery-target="container"></div>
                <template data-ad-delivery-target="template"><div class="custom-ad">Ad HTML</div></template>
            </section>
        `;

        application = Application.start();
        application.register("ad-delivery", AdDeliveryController);
        await flush();

        const section = document.querySelector("section");
        const container = section.querySelector(
            "[data-ad-delivery-target='container']",
        );

        expect(container.innerHTML).toContain("custom-ad");
        expect(section.getAttribute("data-rh-ad-initialized")).toBe("1");
    });

    test("clears custom content on disconnect", async () => {
        document.body.innerHTML = `
            <section data-controller="ad-delivery" data-ad-delivery-mode-value="custom">
                <div data-ad-delivery-target="container"></div>
                <template data-ad-delivery-target="template"><div class="custom-ad">Ad HTML</div></template>
            </section>
        `;

        application = Application.start();
        application.register("ad-delivery", AdDeliveryController);
        await flush();

        const controller = application.controllers.find(
            (item) => item.identifier === "ad-delivery",
        );
        const section = document.querySelector("section");
        const container = section.querySelector(
            "[data-ad-delivery-target='container']",
        );

        expect(container.innerHTML).toContain("custom-ad");

        controller.disconnect();

        expect(container.innerHTML).toBe("");
    });

    test("renders a google ins element and pushes ads queue once", async () => {
        document.body.innerHTML = `
            <section
                class="rh-ad-slot rh-ad-slot--google"
                data-controller="ad-delivery"
                data-ad-delivery-mode-value="google"
                data-ad-delivery-google-slot-id-value="1234567890"
                data-ad-delivery-google-client-value="ca-pub-4154"
                data-ad-delivery-google-format-value="auto"
            >
                <div data-ad-delivery-target="container"></div>
            </section>
        `;

        application = Application.start();
        application.register("ad-delivery", AdDeliveryController);
        await flush();

        const section = document.querySelector("section");
        const ad = section.querySelector("ins.adsbygoogle");

        expect(ad).not.toBeNull();
        expect(ad.getAttribute("data-ad-slot")).toBe("1234567890");
        expect(ad.getAttribute("data-ad-client")).toBe("ca-pub-4154");
        expect(ad.getAttribute("data-ad-format")).toBe("auto");
        expect(ad.getAttribute("data-full-width-responsive")).toBe("true");

        expect(window.adsbygoogle).toBeDefined();
        expect(window.adsbygoogle).toHaveLength(1);
        expect(section.getAttribute("data-rh-ad-initialized")).toBe("1");
    });

    test("tears down google section state on disconnect", async () => {
        document.body.innerHTML = `
            <section
                class="rh-ad-slot rh-ad-slot--google"
                data-controller="ad-delivery"
                data-ad-delivery-mode-value="google"
                data-ad-delivery-google-slot-id-value="1234567890"
            >
                <div data-ad-delivery-target="container"></div>
            </section>
        `;

        application = Application.start();
        application.register("ad-delivery", AdDeliveryController);
        await flush();

        const controller = application.controllers.find(
            (item) => item.identifier === "ad-delivery",
        );
        const section = document.querySelector("section");
        const container = section.querySelector(
            "[data-ad-delivery-target='container']",
        );

        controller.disconnect();

        expect(container.innerHTML).toBe("");
        expect(section.getAttribute("data-rh-ad-initialized")).toBeNull();
        expect(section.classList.contains("rh-ad-slot--empty")).toBe(false);
    });

    test("handles missing container target without throwing", async () => {
        document.body.innerHTML = `
            <section data-controller="ad-delivery" data-ad-delivery-mode-value="custom"></section>
        `;

        expect(() => {
            application = Application.start();
            application.register("ad-delivery", AdDeliveryController);
        }).not.toThrow();

        await flush();
    });

    test("renders a fallback and skips provider markup when blocked", async () => {
        AdDeliveryController.prototype.checkAdBlocker = jest
            .fn()
            .mockResolvedValue(true);
        document.body.innerHTML = `
            <section data-controller="ad-delivery" data-ad-delivery-mode-value="google">
                <div data-ad-delivery-target="container"></div>
            </section>
        `;

        application = Application.start();
        application.register("ad-delivery", AdDeliveryController);
        await flush();

        const section = document.querySelector("section");
        expect(section.classList.contains("rh-ad-slot--blocked")).toBe(true);
        expect(section.querySelector("ins.adsbygoogle")).toBeNull();
        expect(section.textContent).toContain("Advertising is unavailable");
    });

    test("defers provider rendering until a slot intersects within 200px", async () => {
        const OriginalIntersectionObserver = window.IntersectionObserver;
        let observerCallback;
        let observerOptions;

        class TestIntersectionObserver {
            constructor(callback, options) {
                observerCallback = callback;
                observerOptions = options;
            }

            observe() {}

            unobserve() {}

            disconnect() {}
        }

        window.IntersectionObserver = TestIntersectionObserver;
        document.body.innerHTML = `
            <section data-controller="ad-delivery" data-ad-delivery-mode-value="google">
                <div data-ad-delivery-target="container"></div>
            </section>
        `;

        application = Application.start();
        application.register("ad-delivery", AdDeliveryController);
        await flush();

        const section = document.querySelector("section");
        expect(observerOptions.rootMargin).toBe("200px");
        expect(section.querySelector("ins.adsbygoogle")).toBeNull();

        observerCallback([{ isIntersecting: true }]);
        expect(section.querySelector("ins.adsbygoogle")).not.toBeNull();

        window.IntersectionObserver = OriginalIntersectionObserver;
    });

    test("applies non-personalized GPT privacy when consent is missing", async () => {
        const privacySettings = jest.fn();
        const slot = { addService: jest.fn() };
        const push = jest.fn((callback) => callback());
        window.googletag = {
            cmd: { push },
            defineSlot: jest.fn(() => slot),
            display: jest.fn(),
            pubads: jest.fn(() => ({ setPrivacySettings: privacySettings })),
        };
        document.body.innerHTML = `
            <section
                data-controller="ad-delivery"
                data-ad-delivery-mode-value="gpt"
                data-ad-delivery-slot-value="below_nav"
                data-ad-delivery-gpt-unit-path-value="/1234/racerhistory/home"
                data-ad-delivery-sizes-desktop-value="[[970,250]]"
                data-ad-delivery-sizes-mobile-value="[[300,250]]"
            >
                <div data-ad-delivery-target="container"></div>
            </section>
        `;

        application = Application.start();
        application.register("ad-delivery", AdDeliveryController);
        await flush();

        expect(privacySettings).toHaveBeenCalledWith({
            nonPersonalizedAds: true,
        });
        expect(window.googletag.defineSlot).toHaveBeenCalledWith(
            "/1234/racerhistory/home",
            [[970, 250]],
            expect.stringMatching(/^rh-gpt-below_nav-/),
        );
    });
});
