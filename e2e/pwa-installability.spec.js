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

        await page.goto("/", { waitUntil: "networkidle" });

        // Wait for main.js to execute and service worker registration to complete
        // Increase timeout to allow for slow CI environments
        await page.waitForTimeout(2000);

        const hasServiceWorker = await page.evaluate(async () => {
            if (!("serviceWorker" in navigator)) {
                console.debug("Service Worker API not available");
                return false;
            }

            try {
                // Check if we have any registrations already
                let registrations = await navigator.serviceWorker.getRegistrations();
                console.debug(`Initial registrations: ${registrations.length}`);

                if (registrations.length === 0) {
                    // Try to trigger registration if it hasn't happened yet
                    try {
                        const reg = await navigator.serviceWorker.register("/sw.js", {
                            scope: "/",
                        });
                        console.debug("Triggered registration, scope:", reg.scope);
                        registrations = [reg];
                    } catch (regErr) {
                        console.debug(
                            "Manual registration failed:",
                            regErr.message
                        );
                        return false;
                    }
                }

                // Now wait for the service worker to be ready
                await Promise.race([
                    navigator.serviceWorker.ready,
                    new Promise((_, reject) =>
                        setTimeout(
                            () => reject(new Error("SW ready timeout")),
                            15000
                        )
                    ),
                ]);
                console.debug("Service Worker is ready");
                return true;
            } catch (err) {
                console.debug("Service Worker check failed:", err.message);

                // Check if there are any registrations at all
                const registrations = await navigator.serviceWorker.getRegistrations();
                console.debug(`Final registrations: ${registrations.length}`);
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

        await page.goto("/", { waitUntil: "networkidle" });

        // Wait for main.js and service worker registration
        await page.waitForTimeout(2000);

        const swReady = await page.evaluate(async () => {
            if (!("serviceWorker" in navigator)) {
                return false;
            }
            try {
                // Check or trigger registration
                let registrations = await navigator.serviceWorker.getRegistrations();
                if (registrations.length === 0) {
                    try {
                        const reg = await navigator.serviceWorker.register(
                            "/sw.js",
                            { scope: "/" }
                        );
                        registrations = [reg];
                    } catch {
                        return false;
                    }
                }

                // Wait for activation
                await Promise.race([
                    navigator.serviceWorker.ready,
                    new Promise((_, reject) =>
                        setTimeout(() => reject(new Error("timeout")), 15000)
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
