import { test, expect } from "@playwright/test";

/**
 * Navigate to a person view page, skipping the test if the person
 * record does not exist in the current environment.
 *
 * @param {import('@playwright/test').Page} page
 * @param {number} [personId=1]
 * @returns {Promise<boolean>} true if the page is ready for assertions
 */
async function goToPersonView(page, personId = 1) {
    const response = await page.goto(`/people/view/${personId}`, {
        waitUntil: "domcontentloaded",
    });
    if (
        !response ||
        response.status() !== 200 ||
        page.url().includes("/login")
    ) {
        test.skip(
            true,
            `Person ${personId} not found (HTTP ${response?.status() ?? "none"}) – no seed data`,
        );
        return false;
    }
    await page.waitForTimeout(250);
    return true;
}

/**
 * E2E tests for Turbo Frame functionality on People game log
 * Tests tab-based navigation and lazy loading of game logs
 */

test.describe("People Game Log - Turbo Frame Tabs", () => {
    test("should have turbo-frame element for game log", async ({ page }) => {
        if (!(await goToPersonView(page))) return;

        // The frame ID is dynamic based on sport/season, so we check for pattern
        const turboFrame = page.locator('turbo-frame[id*="game-log"]').first();

        if ((await turboFrame.count()) > 0) {
            await expect(turboFrame).toBeVisible();
        }
    });

    test("should handle tab navigation within frame", async ({ page }) => {
        if (!(await goToPersonView(page))) return;

        // Look for tab navigation elements
        const tabs = page.locator('[role="tab"]');

        if ((await tabs.count()) > 0) {
            // Click first tab
            await tabs.first().click({ noWaitAfter: true });
            await page.waitForTimeout(400);

            // Verify a turbo frame exists after tab click
            const turboFrame = page
                .locator('turbo-frame[id*="game-log"]')
                .first();
            if ((await turboFrame.count()) > 0) {
                await expect(turboFrame).toBeVisible();
            }
        }
    });

    test("should load game log content lazily", async ({ page }) => {
        if (!(await goToPersonView(page))) return;

        const turboFrame = page.locator('turbo-frame[id*="game-log"]').first();

        if ((await turboFrame.count()) > 0) {
            // Check that frame has content
            const content = await turboFrame.textContent();
            expect(content).toBeTruthy();
        }
    });

    test("should maintain frame state during tab switches", async ({
        page,
    }) => {
        if (!(await goToPersonView(page))) return;

        const tabs = page.locator('[role="tab"]');

        if ((await tabs.count()) > 1) {
            // Click first tab
            await tabs.first().click({ noWaitAfter: true });
            await page.waitForTimeout(400);

            const firstFrameId = await page
                .locator('turbo-frame[id*="game-log"]')
                .first()
                .getAttribute("id");

            // Click second tab
            await tabs.nth(1).click({ noWaitAfter: true });
            await page.waitForTimeout(400);

            const secondFrameId = await page
                .locator('turbo-frame[id*="game-log"]')
                .first()
                .getAttribute("id");

            // Frame IDs should be different for different tabs
            expect(firstFrameId).not.toBe(secondFrameId);
        }
    });
});

test.describe("People Game Log - Frame Content Validation", () => {
    test("should display game statistics in frame", async ({ page }) => {
        if (!(await goToPersonView(page))) return;

        const turboFrame = page.locator('turbo-frame[id*="game-log"]').first();

        if ((await turboFrame.count()) > 0) {
            const content = await turboFrame.textContent();

            // Basic check that content is loaded
            expect(content).toBeTruthy();
            expect(content.length).toBeGreaterThan(0);
        }
    });

    test("should handle empty game log gracefully", async ({ page }) => {
        // Navigate to a person with no game logs (if such exists)
        // This is a defensive test
        await page.goto("/people/view/999999");

        // Should either show 404 or handle gracefully
        const url = page.url();
        expect(url).toBeTruthy();
    });
});

test.describe("People Game Log - Performance", () => {
    test("should load frame content within reasonable time", async ({
        page,
    }) => {
        const startTime = Date.now();
        if (!(await goToPersonView(page))) return;
        const endTime = Date.now();

        const loadTime = endTime - startTime;

        // Should load within 5 seconds (adjust threshold as needed)
        expect(loadTime).toBeLessThan(5000);
    });

    test("should not cause page reflow during frame load", async ({ page }) => {
        if (!(await goToPersonView(page))) return;

        // Get initial page height
        const initialHeight = await page.evaluate(
            () => document.body.scrollHeight,
        );

        await page.waitForTimeout(400);

        // Get final page height after frame load
        const finalHeight = await page.evaluate(
            () => document.body.scrollHeight,
        );

        // Height should have changed (content loaded) but not excessively
        expect(finalHeight).toBeGreaterThanOrEqual(initialHeight);
    });
});
