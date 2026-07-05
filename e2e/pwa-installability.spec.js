import { expect, test } from "@playwright/test";

test.describe("PWA installability and offline behavior", () => {
    test("public home links a valid manifest and essential icon sizes", async ({
        page,
        request,
    }) => {
        await page.goto("/");

        const manifestHref = await page
            .locator('link[rel="manifest"]')
            .first()
            .getAttribute("href");
        expect(manifestHref).toBeTruthy();

        const manifestUrl = new URL(manifestHref, page.url()).toString();
        const response = await request.get(manifestUrl);
        expect(response.ok()).toBeTruthy();

        const manifest = await response.json();
        expect(manifest.name).toBeTruthy();
        expect(manifest.display).toBe("standalone");

        const iconSizes = new Set(
            (manifest.icons || []).map((icon) => String(icon.sizes || "")),
        );

        expect(iconSizes.has("192x192")).toBeTruthy();
        expect(iconSizes.has("384x384")).toBeTruthy();
        expect(iconSizes.has("512x512")).toBeTruthy();
    });

    test("registers service worker on public pages", async ({
        page,
        browserName,
    }) => {
        test.skip(browserName !== "chromium", "Service worker check is Chromium-focused");

        await page.goto("/");

        const hasServiceWorker = await page.evaluate(async () => {
            if (!("serviceWorker" in navigator)) {
                return false;
            }
            await navigator.serviceWorker.ready;
            return true;
        });

        expect(hasServiceWorker).toBeTruthy();
    });

    test("shows offline fallback page when disconnected", async ({
        page,
        context,
        browserName,
    }) => {
        test.skip(browserName !== "chromium", "Offline SW behavior is Chromium-focused");

        await page.goto("/");
        await page.evaluate(async () => {
            if ("serviceWorker" in navigator) {
                await navigator.serviceWorker.ready;
            }
        });

        // Reload once so the current tab is controlled by the active service worker.
        await page.reload();

        try {
            await context.setOffline(true);
            await page.goto(`/offline-probe-${Date.now()}`);
            await expect(page.locator("h1")).toHaveText("You are offline");
        } finally {
            await context.setOffline(false);
        }
    });
});
