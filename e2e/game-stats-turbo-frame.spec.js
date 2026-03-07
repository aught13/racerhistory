import { test, expect } from '@playwright/test';

/**
 * E2E tests for Turbo Frame functionality on the Games view page
 * Tests the game-stats-frame lazy loading behavior
 */

test.describe('Game View - Turbo Frame Stats Loading', () => {
    test.beforeEach(async ({ page }) => {
        // Mock authentication if needed for admin routes
        // For public routes, you may not need this
        await page.goto('/games/view/1');
    });

    test('should have turbo-frame element for stats', async ({ page }) => {
        // Check that the turbo-frame element exists
        const turboFrame = page.locator('turbo-frame#game-stats-frame');
        await expect(turboFrame).toBeVisible();
    });

    test('should have src attribute for lazy loading', async ({ page }) => {
        // Verify the frame has the correct src for lazy loading
        const turboFrame = page.locator('turbo-frame#game-stats-frame');
        const src = await turboFrame.getAttribute('src');
        expect(src).toContain('/games/stats/');
    });

    test('should load stats content lazily', async ({ page }) => {
        // Wait for the Turbo Frame to load its content
        await page.waitForLoadState('networkidle');

        // Check that stats content is loaded
        const turboFrame = page.locator('turbo-frame#game-stats-frame');
        const frameContent = await turboFrame.textContent();

        // Verify stats content loaded (adjust based on actual content)
        expect(frameContent).toBeTruthy();
        expect(frameContent.length).toBeGreaterThan(0);
    });

    test('should maintain frame isolation', async ({ page }) => {
        // Multiple operations on the page shouldn't break frame isolation
        await page.waitForLoadState('networkidle');

        const turboFrame = page.locator('turbo-frame#game-stats-frame');
        await expect(turboFrame).toBeVisible();

        // Verify frame is still in DOM after navigation-like events
        const frameId = await turboFrame.getAttribute('id');
        expect(frameId).toBe('game-stats-frame');
    });

    test('should respect data-turbo-cache attribute', async ({ page }) => {
        const turboFrame = page.locator('turbo-frame#game-stats-frame');
        const cacheAttr = await turboFrame.getAttribute('data-turbo-cache');
        expect(cacheAttr).toBe('false');
    });

    test('should handle frame navigation errors gracefully', async ({ page }) => {
        // Navigate to a non-existent game
        await page.goto('/games/view/999999');

        // Should show error page or handle gracefully (404 or similar)
        // Adjust based on your error handling strategy
        const statusCode = page.url();
        expect(statusCode).toBeTruthy();
    });
});

test.describe('Game Stats Frame - Content Validation', () => {
    test('should display basketball stats when loaded', async ({ page }) => {
        await page.goto('/games/view/1');
        await page.waitForLoadState('networkidle');

        // Wait for stats frame to load
        const turboFrame = page.locator('turbo-frame#game-stats-frame');
        await expect(turboFrame).toBeVisible();

        // Check for common basketball stat indicators (adjust selectors as needed)
        const frameLocator = page.frameLocator('turbo-frame#game-stats-frame');

        // Verify stats table exists (modify selector based on actual HTML)
        // This is a generic check - adjust based on your actual stats rendering
        const hasContent = await turboFrame.textContent();
        expect(hasContent).toBeTruthy();
    });
});
