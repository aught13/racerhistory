/* global afterEach, beforeEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import AdDeliveryController from "../controllers/ad_delivery_controller.js";

describe("ad-delivery controller", () => {
    let application;

    const flush = async () => {
        await Promise.resolve();
        await Promise.resolve();
    };

    beforeEach(() => {
        document.body.innerHTML = "";
        delete window.adsbygoogle;
        delete window.googletag;
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        delete window.adsbygoogle;
        delete window.googletag;
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
});
