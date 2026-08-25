import { expect, test } from "@playwright/test";

function customAdMarkup() {
    return `
        <section class="rh-ad-slot" data-controller="ad-delivery" data-ad-delivery-mode-value="custom">
            <div class="rh-ad-slot__inner" data-ad-delivery-target="container"></div>
            <template data-ad-delivery-target="template"><div class="custom-ad">Custom Ad</div></template>
        </section>
    `;
}

function googleAdMarkup() {
    return `
        <section
            class="rh-ad-slot rh-ad-slot--google"
            data-controller="ad-delivery"
            data-ad-delivery-mode-value="google"
            data-ad-delivery-google-slot-id-value="1234567890"
            data-ad-delivery-google-client-value="ca-pub-4154"
            data-ad-delivery-google-format-value="auto"
        >
            <div class="rh-ad-slot__inner" data-ad-delivery-target="container"></div>
        </section>
    `;
}

test.describe("Ad lifecycle integration", () => {
    test("re-renders custom ad content on turbo-frame replacement", async ({
        page,
    }) => {
        await page.goto("/", { waitUntil: "domcontentloaded" });

        await page.evaluate((markup) => {
            const existing = document.getElementById("ad-test-frame");
            if (existing) {
                existing.remove();
            }

            const frame = document.createElement("turbo-frame");
            frame.id = "ad-test-frame";
            frame.innerHTML = markup;
            document.body.appendChild(frame);
        }, customAdMarkup());

        await page.waitForSelector("#ad-test-frame .custom-ad", {
            timeout: 10000,
            state: "attached",
        });

        let customAdCount = await page.evaluate(() => {
            return document.querySelectorAll("#ad-test-frame .custom-ad").length;
        });
        expect(customAdCount).toBe(1);

        await page.evaluate((markup) => {
            const frame = document.getElementById("ad-test-frame");
            if (frame) {
                frame.innerHTML = markup;
            }
        }, customAdMarkup());

        await page.waitForSelector("#ad-test-frame .custom-ad", {
            timeout: 10000,
            state: "attached",
        });

        customAdCount = await page.evaluate(() => {
            return document.querySelectorAll("#ad-test-frame .custom-ad").length;
        });
        expect(customAdCount).toBe(1);
    });

    test("re-initializes google ad slots on turbo-frame replacement without duplicate nodes", async ({
        page,
    }) => {
        await page.goto("/", { waitUntil: "domcontentloaded" });

        await page.evaluate(() => {
            window.adsbygoogle = [];
        });

        await page.evaluate((markup) => {
            const existing = document.getElementById("ad-test-frame");
            if (existing) {
                existing.remove();
            }

            const frame = document.createElement("turbo-frame");
            frame.id = "ad-test-frame";
            frame.innerHTML = markup;
            document.body.appendChild(frame);
        }, googleAdMarkup());

        await page.waitForFunction(() => {
            const section = document.querySelector("#ad-test-frame .rh-ad-slot");
            const ad = section?.querySelector("ins.adsbygoogle");

            return !!section && !!ad && section.getAttribute("data-rh-ad-initialized") === "1";
        });

        const firstPass = await page.evaluate(() => {
            return {
                queueLength: Array.isArray(window.adsbygoogle)
                    ? window.adsbygoogle.length
                    : 0,
                insCount: document.querySelectorAll(
                    "#ad-test-frame ins.adsbygoogle",
                ).length,
            };
        });
        expect(firstPass.queueLength).toBe(1);
        expect(firstPass.insCount).toBe(1);

        await page.evaluate((markup) => {
            const frame = document.getElementById("ad-test-frame");
            if (frame) {
                frame.innerHTML = markup;
            }
        }, googleAdMarkup());

        await page.waitForFunction(() => {
            const section = document.querySelector("#ad-test-frame .rh-ad-slot");
            return section?.getAttribute("data-rh-ad-initialized") === "1";
        });

        const secondPass = await page.evaluate(() => {
            return {
                queueLength: Array.isArray(window.adsbygoogle)
                    ? window.adsbygoogle.length
                    : 0,
                insCount: document.querySelectorAll(
                    "#ad-test-frame ins.adsbygoogle",
                ).length,
            };
        });
        expect(secondPass.queueLength).toBe(2);
        expect(secondPass.insCount).toBe(1);
    });
});
