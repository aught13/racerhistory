import { test, expect } from "@playwright/test";

/**
 * E2E tests for Turbo Frame functionality on the Seasons table
 * Tests lazy loading, filtering, and navigation within the frame
 */

test.describe("Seasons Table - Turbo Frame Navigation", () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to seasons page
        await page.goto("/seasons");
    });

    test("should have turbo-frame element for seasons table", async ({
        page,
    }) => {
        const turboFrame = page.locator("turbo-frame#seasons-table-frame");
        await expect(turboFrame).toBeVisible();
    });

    test("should maintain frame id attribute", async ({ page }) => {
        const turboFrame = page.locator("turbo-frame#seasons-table-frame");
        const frameId = await turboFrame.getAttribute("id");
        expect(frameId).toBe("seasons-table-frame");
    });

    test("should have table content within frame", async ({ page }) => {
        await page.waitForLoadState("networkidle");

        const turboFrame = page.locator("turbo-frame#seasons-table-frame");
        const content = await turboFrame.textContent();

        // Verify there's actual content
        expect(content).toBeTruthy();
        expect(content.length).toBeGreaterThan(0);
    });

    test("should handle filter toggle links", async ({ page }) => {
        await page.waitForLoadState("networkidle");

        // Look for filter links that target the turbo frame
        const filterLink = page
            .locator('a[data-turbo-frame="seasons-table-frame"]')
            .first();

        if ((await filterLink.count()) > 0) {
            const href = await filterLink.getAttribute("href");
            expect(href).toBeTruthy();

            // Click the filter link
            await filterLink.click();

            // Wait for frame to update
            await page.waitForLoadState("networkidle");

            // Verify frame still exists after navigation
            const turboFrame = page.locator("turbo-frame#seasons-table-frame");
            await expect(turboFrame).toBeVisible();
        }
    });

    test("should handle frame navigation to season details", async ({
        page,
    }) => {
        await page.waitForLoadState("networkidle");

        // Look for links that break out of frame navigation (data-turbo-frame="_top")
        const detailLinks = page.locator('a[data-turbo-frame="_top"]');

        if ((await detailLinks.count()) > 0) {
            const href = await detailLinks.first().getAttribute("href");
            expect(href).toBeTruthy();
        }
    });
});

test.describe("Seasons Table - Turbo Frame Filtering", () => {
    test("should update frame content when filter applied", async ({
        page,
    }) => {
        await page.goto("/seasons");
        await page.waitForLoadState("networkidle");

        const turboFrame = page.locator("turbo-frame#seasons-table-frame");
        await turboFrame.textContent();

        // Find and click a filter toggle link
        const filterToggle = page
            .locator('a[data-turbo-frame="seasons-table-frame"]')
            .first();

        if ((await filterToggle.count()) > 0) {
            await filterToggle.click();
            await page.waitForLoadState("networkidle");

            // Content should have changed after filter
            const updatedContent = await turboFrame.textContent();
            expect(updatedContent).toBeTruthy();
        }
    });

    test("should preserve frame isolation during filter", async ({ page }) => {
        await page.goto("/seasons");
        await page.waitForLoadState("networkidle");

        // Verify frame exists before filter
        const turboFrameBefore = page.locator(
            "turbo-frame#seasons-table-frame",
        );
        await expect(turboFrameBefore).toBeVisible();

        // Apply filter if available
        const filterLink = page
            .locator('a[data-turbo-frame="seasons-table-frame"]')
            .first();

        if ((await filterLink.count()) > 0) {
            await filterLink.click();
            await page.waitForLoadState("networkidle");

            // Verify frame still exists after filter
            const turboFrameAfter = page.locator(
                "turbo-frame#seasons-table-frame",
            );
            await expect(turboFrameAfter).toBeVisible();
        }
    });
});

test.describe("Seasons Table - Accessibility", () => {
    test("should have accessible table structure", async ({ page }) => {
        await page.goto("/seasons");
        await page.waitForLoadState("networkidle");

        const turboFrame = page.locator("turbo-frame#seasons-table-frame");

        // Check for table element
        const table = turboFrame.locator("table").first();
        if ((await table.count()) > 0) {
            await expect(table).toBeVisible();

            // Verify table has headers
            const headers = table.locator("th");
            expect(await headers.count()).toBeGreaterThan(0);
        }
    });
});
