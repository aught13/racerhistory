import { test, expect } from "@playwright/test";
import { loginToAdmin } from "./support/auth.js";

test.describe("Admin sidebar groups", () => {
    test.beforeEach(async ({ page }) => {
        const loggedIn = await loginToAdmin(page, {
            waitUntil: "domcontentloaded",
            timeout: 10000,
        });

        if (loggedIn) {
            await page.goto("/admin/sites", {
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

    test("sports and content groups expand from mini-collapsed desktop sidebar", async ({
        page,
    }) => {
        // This test exercises desktop sidebar-collapse behavior which differs on mobile
        const viewport = page.viewportSize();
        if (viewport && viewport.width < 992) {
            test.skip();
            return;
        }

        const sidebarToggle = page
            .locator('a[data-action="click->admin-layout#toggle"]')
            .first();
        const body = page.locator("body");

        const sportsToggle = page
            .locator('button[data-nav-accordion-prefix*="/admin/sports"]')
            .first();
        const contentToggle = page
            .locator('button[data-nav-accordion-prefix*="/admin/blog"]')
            .first();

        await expect(sportsToggle).toBeVisible();
        await expect(contentToggle).toBeVisible();

        // 1) Collapse desktop sidebar to emulate the user-reported state.
        await sidebarToggle.click();
        await expect(body).toHaveClass(/sidebar-collapse/, { timeout: 5000 });

        // 2) Clicking Sports should auto-expand sidebar and open Sports group.
        await sportsToggle.click();
        await expect(body).not.toHaveClass(/sidebar-collapse/, { timeout: 5000 });
        await expect(sportsToggle).toHaveAttribute("aria-expanded", "true", {
            timeout: 5000,
        });

        const sportsPanel = sportsToggle.locator("xpath=following-sibling::ul[1]");
        await expect(sportsPanel).toBeVisible();
        await expect(
            sportsPanel.locator('a[href="/admin/sports"]'),
        ).toBeVisible();

        // 3) Collapse again, then verify Content follows the same behavior.
        await sidebarToggle.click();
        await expect(body).toHaveClass(/sidebar-collapse/, { timeout: 5000 });

        await contentToggle.click();
        await expect(body).not.toHaveClass(/sidebar-collapse/, { timeout: 5000 });
        await expect(contentToggle).toHaveAttribute("aria-expanded", "true", {
            timeout: 5000,
        });

        const contentPanel = contentToggle.locator("xpath=following-sibling::ul[1]");
        await expect(contentPanel).toBeVisible();
        await expect(
            contentPanel.locator('a[href="/admin/blog-posts"]'),
        ).toBeVisible();
    });

    // NOTE: The previous "sidebar labels use sports wording" assertion was
    // removed because the sidebar wording and nav structure have evolved and
    // this assertion became brittle and antiquated. Keep sidebar interaction
    // tests above which verify expand/collapse behavior.
});
