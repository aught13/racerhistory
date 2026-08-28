import { test, expect } from "@playwright/test";
import { loginToAdmin } from "./support/auth.js";

async function ensureAdminSession(page) {
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

    return (
        !loggedIn ||
        (await loginNotice.count()) > 0 ||
        page.url().includes("/login")
    );
}

async function findDeleteTrigger(page, selector, timeoutMs = 12000) {
    const deadline = Date.now() + timeoutMs;
    const locator = page.locator(selector);

    while (Date.now() < deadline) {
        if ((await locator.count()) > 0) {
            return locator.first();
        }
        await page.waitForTimeout(250);
    }

    return null;
}

test.describe("Admin confirm-delete routing", () => {
    test.beforeEach(async ({ page }) => {
        const blockedFromAdmin = await ensureAdminSession(page);
        expect(
            blockedFromAdmin,
            "E2E auth/session failed: expected an authenticated admin session",
        ).toBe(false);
    });

    test("persons delete modal posts to /admin/persons/delete/:id", async ({
        page,
    }) => {
        await page.route("**/admin/persons/delete/*", async (route) => {
            await route.fulfill({
                status: 200,
                contentType: "text/html",
                body: "<html><body>intercepted</body></html>",
            });
        });

        await page.goto("/admin/persons", { waitUntil: "domcontentloaded" });

        const deleteTrigger = await findDeleteTrigger(
            page,
            'button[data-bs-target="#confirm-delete-modal"][data-delete-url*="/admin/persons/delete/"]',
        );
        expect(
            deleteTrigger,
            "Expected at least one persons delete trigger for authenticated admin user",
        ).not.toBeNull();

        await deleteTrigger.click();
        const confirmButton = page.locator("#confirm-delete-modal-delete-btn");
        await expect(confirmButton).toBeVisible();

        const deleteRequestPromise = page.waitForRequest((request) => {
            if (request.method() !== "POST") {
                return false;
            }
            const path = new URL(request.url()).pathname;

            return /^\/admin\/persons\/delete\/\d+$/.test(path);
        });

        await confirmButton.click();
        const deleteRequest = await deleteRequestPromise;
        expect(new URL(deleteRequest.url()).pathname).toMatch(
            /^\/admin\/persons\/delete\/\d+$/,
        );
        expect(deleteRequest.postData() || "").not.toContain("_Token%5B");
    });

    test("team-seasons delete modal posts to /admin/team-seasons/delete/:id", async ({
        page,
    }) => {
        await page.route("**/admin/team-seasons/delete/*", async (route) => {
            await route.fulfill({
                status: 200,
                contentType: "text/html",
                body: "<html><body>intercepted</body></html>",
            });
        });

        await page.goto("/admin/team-seasons", {
            waitUntil: "domcontentloaded",
        });

        const deleteTrigger = await findDeleteTrigger(
            page,
            'button[data-bs-target="#confirm-delete-modal"][data-delete-url*="/admin/team-seasons/delete/"]',
        );
        expect(
            deleteTrigger,
            "Expected at least one team-seasons delete trigger for authenticated admin user",
        ).not.toBeNull();

        await deleteTrigger.click();
        const confirmButton = page.locator("#confirm-delete-modal-delete-btn");
        await expect(confirmButton).toBeVisible();

        const deleteRequestPromise = page.waitForRequest((request) => {
            if (request.method() !== "POST") {
                return false;
            }
            const path = new URL(request.url()).pathname;

            return /^\/admin\/team-seasons\/delete\/\d+$/.test(path);
        });

        await confirmButton.click();
        const deleteRequest = await deleteRequestPromise;
        expect(new URL(deleteRequest.url()).pathname).toMatch(
            /^\/admin\/team-seasons\/delete\/\d+$/,
        );
        expect(deleteRequest.postData() || "").not.toContain("_Token%5B");
    });
});
