import { test, expect } from "@playwright/test";

/**
 * E2E tests for admin JS loading and initialization.
 *
 * Verifies that:
 *   - admin-turbo.mjs loads without a 404 (was previously broken by CakePHP
 *     HtmlHelper appending an extra `.js` extension, generating
 *     `admin-turbo.mjs.js` instead of `admin-turbo.mjs`)
 *   - Hotwire Turbo is available as `window.Turbo` on the admin dashboard
 *   - admin.js initialises `window.showConfirmDelete`
 *   - No console errors from missing admin JS resources
 *   - Admin navigation (Turbo Drive) works without a hard refresh
 *   - Bootstrap re-initialises after Turbo Drive navigations within admin
 *
 * NOTE: These tests require admin authentication. They use a login helper
 * that fills the CakeDC/Users login form. If the server is not running or
 * login fails, tests are skipped gracefully.
 */

/* ────────── helpers ────────── */

/**
 * Attempt to log in as admin. Returns true on success.
 * Uses the app's CakeDC/Users login form.
 */
async function loginAsAdmin(page) {
    try {
        await page.goto("/login", { waitUntil: "networkidle", timeout: 10000 });

        await page.fill('input[name="username"]', "admin");
        await page.fill('input[name="password"]', "admin");
        await page.click('button[type="submit"]');

        // Wait for redirect away from login
        await page.waitForURL((url) => !url.pathname.includes("login"), {
            timeout: 10000,
        });
        return true;
    } catch {
        return false;
    }
}

/**
 * Collect all console errors on the page.
 * Returns an array of message strings.
 */
function collectConsoleErrors(page) {
    const errors = [];
    page.on("console", (msg) => {
        if (msg.type() === "error") {
            errors.push(msg.text());
        }
    });
    return errors;
}

/**
 * Collect all failed network requests (non-2xx/3xx responses excluding CDN).
 */
function collectFailedRequests(page) {
    const failed = [];
    page.on("requestfailed", (req) => {
        failed.push(req.url());
    });
    page.on("response", (res) => {
        const url = res.url();
        // Only check own-origin JS files
        if (
            url.includes("/js/") &&
            !url.includes("cdn.jsdelivr.net") &&
            !url.includes("code.jquery.com") &&
            !url.includes("esm.sh") &&
            res.status() >= 400
        ) {
            failed.push(`${res.status()} ${url}`);
        }
    });
    return failed;
}

/* ────────── authentication ────────── */

test.describe("Admin JS Loading", () => {
    test.beforeEach(async ({ page }) => {
        const loggedIn = await loginAsAdmin(page);
        // test.skip(!loggedIn, "Could not log in — server may not be running");
    });

    /* ── resource loading ─────────────────────────────────────── */

    test("admin-turbo.mjs loads without 404", async ({ page }) => {
        const failedRequests = collectFailedRequests(page);

        await page.goto("/admin/", { waitUntil: "networkidle" });

        // No local JS file should 404
        const adminJsFailures = failedRequests.filter((url) =>
            url.includes("admin-turbo"),
        );
        expect(adminJsFailures).toHaveLength(0);
    });

    test("admin JS files produce no 404 console errors", async ({ page }) => {
        const errors = collectConsoleErrors(page);

        await page.goto("/admin/", { waitUntil: "networkidle" });

        // Filter to resource-load errors about our own JS files
        const jsErrors = errors.filter(
            (msg) =>
                msg.includes("404") &&
                msg.includes("/js/") &&
                !msg.includes("cdn."),
        );
        expect(jsErrors).toHaveLength(0);
    });

    /* ── JS globals ───────────────────────────────────────────── */

    test("window.Turbo is defined on the admin dashboard", async ({ page }) => {
        await page.goto("/admin/", { waitUntil: "networkidle" });

        const turboAvailable = await page.evaluate(
            () => typeof window.Turbo !== "undefined",
        );
        expect(turboAvailable).toBe(true);
    });

    test("window.showConfirmDelete is defined on the admin dashboard", async ({
        page,
    }) => {
        await page.goto("/admin/", { waitUntil: "networkidle" });

        const helperAvailable = await page.evaluate(
            () => typeof window.showConfirmDelete === "function",
        );
        expect(helperAvailable).toBe(true);
    });

    /* ── Turbo Drive navigation within admin ──────────────────── */

    test("navigating to Users page via Turbo Drive keeps Turbo available", async ({
        page,
    }) => {
        await page.goto("/admin/", { waitUntil: "networkidle" });

        // Turbo Drive captures the link click
        await Promise.all([
            page.waitForURL(/\/admin\/users/, { timeout: 15000 }),
            page.click('a[href="/admin/users"]'),
        ]);

        await page.waitForLoadState("networkidle");

        // Turbo and admin helpers should still be available after navigation
        const turboAvailable = await page.evaluate(
            () => typeof window.Turbo !== "undefined",
        );
        expect(turboAvailable).toBe(true);

        const helperAvailable = await page.evaluate(
            () => typeof window.showConfirmDelete === "function",
        );
        expect(helperAvailable).toBe(true);
    });

    test("admin-content turbo-frame is present on dashboard", async ({
        page,
    }) => {
        await page.goto("/admin/", { waitUntil: "networkidle" });

        const frame = page.locator("turbo-frame#admin-content");
        await expect(frame).toBeVisible();
    });

    /* ── Bootstrap re-initialisation ─────────────────────────── */

    test("Bootstrap is available after Turbo Drive navigation within admin", async ({
        page,
    }) => {
        await page.goto("/admin/", { waitUntil: "networkidle" });

        // Navigate via Turbo Drive
        await Promise.all([
            page.waitForURL(/\/admin\/users/, { timeout: 15000 }),
            page.click('a[href="/admin/users"]'),
        ]);
        await page.waitForLoadState("networkidle");

        const bootstrapAvailable = await page.evaluate(
            () =>
                typeof window.bootstrap !== "undefined" &&
                typeof window.bootstrap.Modal !== "undefined",
        );
        expect(bootstrapAvailable).toBe(true);
    });
});
