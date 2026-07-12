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
        
        // Give the service worker registration some time to start
        await page.waitForTimeout(500);

        const hasServiceWorker = await page.evaluate(async () => {
            if (!("serviceWorker" in navigator)) {
                console.debug("Service Worker API not available");
                return false;
            }

            try {
                // Wait up to 10 seconds for the service worker to be ready
                await Promise.race([
                    navigator.serviceWorker.ready,
                    new Promise((_, reject) =>
                        setTimeout(() => reject(new Error("SW ready timeout")), 10000)
                    ),
                ]);
                console.debug("Service Worker is ready");
                return true;
            } catch (err) {
                console.debug("Service Worker ready failed:", err.message);
                
                // Check if there are any registrations at all
                const registrations = await navigator.serviceWorker.getRegistrations();
                console.debug(`Found ${registrations.length} SW registrations`);
                registrations.forEach((reg) => {
                    console.debug("Registration:", {
                        scope: reg.scope,
                        active: !!reg.active,
                        installing: !!reg.installing,
                        waiting: !!reg.waiting,
                    });
                });
                
                return false;
            }
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
        
        // Wait for service worker to be ready with proper timeout
        await page.waitForTimeout(500);
        const swReady = await page.evaluate(async () => {
            if (!("serviceWorker" in navigator)) {
                return false;
            }
            try {
                await Promise.race([
                    navigator.serviceWorker.ready,
                    new Promise((_, reject) =>
                        setTimeout(() => reject(new Error("timeout")), 5000)
                    ),
                ]);
                return true;
            } catch {
                return false;
            }
        });

        test.skip(!swReady, "Service worker must be ready for offline test");

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
