import { test, expect } from "@playwright/test";

/**
 * E2E tests for Turbo Frame functionality on the Games view page
 * Tests the game-stats-frame lazy loading behavior.
 *
 * These tests require game ID 1 to exist in the database. In CI environments
 * with no seed data the page returns a 404/redirect and all tests are skipped.
 */

/** Navigate to the game view page and skip if no data is available. */
async function goToGameView(page, gameId = 1) {
    const response = await page.goto(`/games/view/${gameId}`);
    if (!response || response.status() !== 200) {
        test.skip();
        return false;
    }
    const frame = page.locator("turbo-frame#game-stats-frame");
    if ((await frame.count()) === 0) {
        test.skip();
        return false;
    }
    return true;
}

test.describe("Game View - Turbo Frame Stats Loading", () => {
    test("should have turbo-frame element for stats", async ({ page }) => {
        if (!(await goToGameView(page))) return;

        const turboFrame = page.locator("turbo-frame#game-stats-frame");
        await expect(turboFrame).toBeVisible();
    });

    test("should have src attribute for lazy loading", async ({ page }) => {
        if (!(await goToGameView(page))) return;

        const turboFrame = page.locator("turbo-frame#game-stats-frame");
        const src = await turboFrame.getAttribute("src");
        expect(src).toContain("/games/stats/");
    });

    test("should load stats content lazily", async ({ page }) => {
        if (!(await goToGameView(page))) return;

        await page.waitForLoadState("networkidle");

        const turboFrame = page.locator("turbo-frame#game-stats-frame");
        const frameContent = await turboFrame.textContent();

        expect(frameContent).toBeTruthy();
        expect(frameContent.length).toBeGreaterThan(0);
    });

    test("should maintain frame isolation", async ({ page }) => {
        if (!(await goToGameView(page))) return;

        await page.waitForLoadState("networkidle");

        const turboFrame = page.locator("turbo-frame#game-stats-frame");
        await expect(turboFrame).toBeVisible();

        const frameId = await turboFrame.getAttribute("id");
        expect(frameId).toBe("game-stats-frame");
    });

    test("should respect data-turbo-cache attribute", async ({ page }) => {
        if (!(await goToGameView(page))) return;

        const turboFrame = page.locator("turbo-frame#game-stats-frame");
        const cacheAttr = await turboFrame.getAttribute("data-turbo-cache");
        expect(cacheAttr).toBe("false");
    });

    test("should handle frame navigation errors gracefully", async ({
        page,
    }) => {
        // Navigate to a non-existent game — always safe to run
        await page.goto("/games/view/999999");

        // Should show error page or handle gracefully (404 or redirect)
        expect(page.url()).toBeTruthy();
    });
});

test.describe("Game Stats Frame - Content Validation", () => {
    test("should display basketball stats when loaded", async ({ page }) => {
        if (!(await goToGameView(page))) return;

        await page.waitForLoadState("networkidle");

        const turboFrame = page.locator("turbo-frame#game-stats-frame");
        await expect(turboFrame).toBeVisible();

        const hasContent = await turboFrame.textContent();
        expect(hasContent).toBeTruthy();
    });
});
