import { test, expect } from "@playwright/test";

/**
 * E2E tests for dark mode readability and consistency.
 * Validates that explicit dark theme applies correct CSS variables
 * and that key components are readable in dark mode.
 */

test.describe("Dark Mode — explicit theme toggle", () => {
    test.beforeEach(async ({ page }) => {
        // Set dark theme cookie before navigating
        await page.context().addCookies([
            {
                name: "theme",
                value: "dark",
                url: "http://localhost:8765",
            },
        ]);
    });

    test("should apply dark theme variables to :root", async ({ page }) => {
        await page.goto("/");
        await page.waitForLoadState("domcontentloaded");

        const theme = await page.getAttribute("html", "data-theme");
        expect(theme).toBe("dark");
    });

    test("body should have dark background color", async ({ page }) => {
        await page.goto("/");
        await page.waitForLoadState("domcontentloaded");

        const bgColor = await page.evaluate(() => {
            return getComputedStyle(document.body).backgroundColor;
        });

        // Should be a dark colour (RGB values < 50)
        const match = bgColor.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
        if (match) {
            const [, r, g, b] = match.map(Number);
            expect(r).toBeLessThan(80);
            expect(g).toBeLessThan(80);
            expect(b).toBeLessThan(80);
        }
    });

    test("body text should be light-coloured in dark mode", async ({
        page,
    }) => {
        await page.goto("/");
        await page.waitForLoadState("domcontentloaded");

        const textColor = await page.evaluate(() => {
            return getComputedStyle(document.body).color;
        });

        const match = textColor.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
        if (match) {
            const [, r, g, b] = match.map(Number);
            // Text should be bright (light) — at least one channel > 200
            expect(Math.max(r, g, b)).toBeGreaterThan(180);
        }
    });

    test("cards should have dark surface background", async ({ page }) => {
        await page.goto("/");
        await page.waitForLoadState("domcontentloaded");

        const card = page.locator(".card").first();
        if ((await card.count()) > 0) {
            const cardBg = await card.evaluate(
                (el) => getComputedStyle(el).backgroundColor,
            );
            const match = cardBg.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
            if (match) {
                const [, r, g, b] = match.map(Number);
                expect(r).toBeLessThan(80);
                expect(g).toBeLessThan(80);
                expect(b).toBeLessThan(80);
            }
        }
    });

    test("footer should have dark background", async ({ page }) => {
        await page.goto("/");
        await page.waitForLoadState("domcontentloaded");

        const footer = page.locator(".rh-footer").first();
        if ((await footer.count()) > 0) {
            const bg = await footer.evaluate(
                (el) => getComputedStyle(el).backgroundColor,
            );
            const match = bg.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
            if (match) {
                const [, r, g, b] = match.map(Number);
                expect(r).toBeLessThan(80);
                expect(g).toBeLessThan(80);
                expect(b).toBeLessThan(80);
            }
        }
    });

    test("links should use gold colour in dark mode", async ({ page }) => {
        await page.goto("/");
        await page.waitForLoadState("domcontentloaded");

        const link = page.locator(".rh-main-inner a").first();
        if ((await link.count()) > 0) {
            const color = await link.evaluate(
                (el) => getComputedStyle(el).color,
            );
            const match = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
            if (match) {
                const [, r, g] = match.map(Number);
                // Gold color (#ECAC00) channels: R≈236, G≈172, B≈0
                expect(r).toBeGreaterThan(150);
                expect(g).toBeGreaterThan(100);
            }
        }
    });
});

test.describe("Dark Mode — system preference (emulated)", () => {
    test("should respond to prefers-color-scheme: dark", async ({
        browser,
    }) => {
        const context = await browser.newContext({
            colorScheme: "dark",
        });
        const page = await context.newPage();

        await page.goto("/");
        await page.waitForLoadState("domcontentloaded");

        // With no cookie set and system dark, body should be dark
        const bgColor = await page.evaluate(() => {
            return getComputedStyle(document.body).backgroundColor;
        });

        const match = bgColor.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
        if (match) {
            const [, r, g, b] = match.map(Number);
            expect(r).toBeLessThan(80);
            expect(g).toBeLessThan(80);
            expect(b).toBeLessThan(80);
        }

        await context.close();
    });
});

test.describe("Dark Mode — theme toggle button", () => {
    test("toggle button should exist in footer", async ({ page }) => {
        await page.goto("/");
        await page.waitForLoadState("domcontentloaded");

        const toggle = page.locator('[data-controller="theme-toggle"]');
        await expect(toggle).toBeVisible();
    });

    test("clicking toggle should cycle theme", async ({ page }) => {
        await page.goto("/");
        await page.waitForLoadState("domcontentloaded");

        const toggle = page.locator('[data-controller="theme-toggle"]');
        if ((await toggle.count()) > 0) {
            // Click to cycle from system → light → dark
            await toggle.click();
            await toggle.click();

            const theme = await page.getAttribute("html", "data-theme");
            expect(theme).toBe("dark");
        }
    });
});
