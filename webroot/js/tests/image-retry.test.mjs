import { jest } from "@jest/globals";

const loadModule = async () => import("../image-retry.mjs");

describe("image retry utility", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        delete window.__rhImageRetryInit;
    });

    afterEach(() => {
        document.body.innerHTML = "";
        delete window.__rhImageRetryInit;
        jest.restoreAllMocks();
    });

    test("retries a broken serve image and busts picture srcset candidates", async () => {
        jest.spyOn(Date, "now").mockReturnValue(1111);
        await loadModule();

        document.body.innerHTML = `
            <picture>
                <source
                    id="srcset-source"
                    srcset="/images/serve/7 1x, /assets/logo.png 2x"
                />
                <img id="serve-image" src="/images/serve/7" alt="broken" />
            </picture>
        `;

        const img = document.getElementById("serve-image");
        img.dispatchEvent(new Event("error"));

        expect(img.dataset.rhRetryAttempted).toBe("1");
        expect(img.getAttribute("src")).toBe("/images/serve/7?_ts=1111");
        expect(
            document.getElementById("srcset-source").getAttribute("srcset"),
        ).toBe("/images/serve/7?_ts=1111 1x, /assets/logo.png 2x");
    });

    test("does not retry non-serve urls and only retries each image once", async () => {
        jest.spyOn(Date, "now").mockReturnValue(2222);
        await loadModule();

        document.body.innerHTML = `
            <img id="public-image" src="/img/site/logo.png" alt="public" />
            <img id="serve-image" src="/images/serve/9" alt="serve" />
        `;

        const publicImage = document.getElementById("public-image");
        const serveImage = document.getElementById("serve-image");

        publicImage.dispatchEvent(new Event("error"));
        expect(publicImage.dataset.rhRetryAttempted).toBeUndefined();
        expect(publicImage.getAttribute("src")).toBe("/img/site/logo.png");

        serveImage.dispatchEvent(new Event("error"));
        expect(serveImage.getAttribute("src")).toBe("/images/serve/9?_ts=2222");

        jest.spyOn(Date, "now").mockReturnValue(3333);
        serveImage.dispatchEvent(new Event("error"));
        expect(serveImage.getAttribute("src")).toBe("/images/serve/9?_ts=2222");
    });

    test("retries already-broken images on DOMContentLoaded and turbo load", async () => {
        jest.spyOn(Date, "now").mockReturnValue(4444);
        await loadModule();

        const serveImage = document.createElement("img");
        serveImage.setAttribute("src", "/images/serve/10");
        Object.defineProperty(serveImage, "complete", {
            configurable: true,
            value: true,
        });
        Object.defineProperty(serveImage, "naturalWidth", {
            configurable: true,
            value: 0,
        });

        const healthyImage = document.createElement("img");
        healthyImage.setAttribute("src", "/images/serve/11");
        Object.defineProperty(healthyImage, "complete", {
            configurable: true,
            value: true,
        });
        Object.defineProperty(healthyImage, "naturalWidth", {
            configurable: true,
            value: 200,
        });

        document.body.appendChild(serveImage);
        document.body.appendChild(healthyImage);

        document.dispatchEvent(new Event("DOMContentLoaded"));

        expect(serveImage.getAttribute("src")).toBe(
            "/images/serve/10?_ts=4444",
        );
        expect(healthyImage.getAttribute("src")).toBe("/images/serve/11");

        jest.spyOn(Date, "now").mockReturnValue(5555);
        document.dispatchEvent(new Event("turbo:load"));
        expect(serveImage.getAttribute("src")).toBe(
            "/images/serve/10?_ts=4444",
        );
    });
});
