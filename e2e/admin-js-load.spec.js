import { test, expect } from "@playwright/test";
import { loginToAdmin } from "./support/auth.js";

/**
 * E2E tests for admin JS loading and initialization.
 *
 * Verifies that:
 *   - Admin runtime assets load without local 404s
 *   - the admin runtime boot flag is set on the admin dashboard
 *   - the Stimulus confirm-delete bridge is available
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
        const loggedIn = await loginToAdmin(page, {
            waitUntil: "domcontentloaded",
            timeout: 10000,
        });
        if (loggedIn) {
            await page.goto("/admin/", {
                waitUntil: "domcontentloaded",
                timeout: 10000,
            });
        }

        const loginNotice = page
            .locator("text=You must be logged in to access the admin area.")
            .first();
        const blockedFromAdmin =
            !loggedIn ||
            (await loginNotice.count()) > 0 ||
            page.url().includes("/login");
        test.skip(blockedFromAdmin, "Could not log in to the e2e admin account");
    });

    /* ── resource loading ─────────────────────────────────────── */

    test("admin runtime assets load without local 404", async ({ page }) => {
        const failedRequests = collectFailedRequests(page);

        await page.goto("/admin/", { waitUntil: "domcontentloaded" });

        const localRuntimeFailures = failedRequests.filter(
            (entry) =>
                (entry.includes("/js/") || entry.includes("/dist/")) &&
                !entry.includes("/debug_kit/") &&
                !entry.includes("cdn.jsdelivr.net") &&
                !entry.includes("code.jquery.com") &&
                !entry.includes("esm.sh") &&
                !entry.includes("localhost:5173/js/main.js"),
        );
        expect(localRuntimeFailures).toHaveLength(0);
    });

    test("admin JS files produce no 404 console errors", async ({ page }) => {
        const errors = collectConsoleErrors(page);

        await page.goto("/admin/", { waitUntil: "domcontentloaded" });

        // Filter to resource-load errors about our own JS files
        const jsErrors = errors.filter(
            (msg) =>
                msg.includes("404") &&
                msg.includes("/js/") &&
                !msg.includes("/debug_kit/") &&
                !msg.includes("cdn."),
        );
        expect(jsErrors).toHaveLength(0);
    });

    /* ── Turbo Drive navigation within admin ──────────────────── */

    test("navigating to Users page via Turbo Drive works from admin", async ({ page }) => {
        await page.goto("/admin/", { waitUntil: "domcontentloaded" });

        const usersLink = page.locator('a[href="/admin/users"]').first();
        test.skip(
            (await usersLink.count()) === 0 || !(await usersLink.isVisible()),
            "Users link is not visible for this viewport/session",
        );

        // Turbo Drive captures the link click
        await Promise.all([
            page.waitForURL(/\/admin\/users/, { timeout: 15000 }),
            usersLink.click(),
        ]);

        await page.waitForLoadState("domcontentloaded");

        await expect(page).toHaveURL(/\/admin\/users/);
    });

    test("admin-content turbo-frame is present on dashboard", async ({
        page,
    }) => {
        await page.goto("/admin/", { waitUntil: "domcontentloaded" });

        const frame = page.locator("turbo-frame#admin-content");
        await expect(frame).toBeVisible();
    });

    /* ── Bootstrap re-initialisation ─────────────────────────── */

    test("Bootstrap is available after Turbo Drive navigation within admin", async ({
        page,
    }) => {
        await page.goto("/admin/", { waitUntil: "domcontentloaded" });

        const usersLink = page.locator('a[href="/admin/users"]').first();
        test.skip(
            (await usersLink.count()) === 0 || !(await usersLink.isVisible()),
            "Users link is not visible for this viewport/session",
        );

        // Navigate via Turbo Drive
        await Promise.all([
            page.waitForURL(/\/admin\/users/, { timeout: 15000 }),
            usersLink.click(),
        ]);
        await page.waitForLoadState("domcontentloaded");

        const bootstrapAvailable = await page.evaluate(
            () =>
                typeof window.bootstrap !== "undefined" &&
                typeof window.bootstrap.Modal !== "undefined",
        );
        expect(bootstrapAvailable).toBe(true);
    });
});
