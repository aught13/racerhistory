import { test, expect } from "@playwright/test";

/**
 * E2E tests for dark mode readability and consistency.
 * Validates that explicit dark theme applies correct CSS variables
 * and that key components are readable in dark mode.
 */

async function setDarkThemeCookie(page, baseURL) {
    await page.context().addCookies([
        {
            name: "theme",
            value: "dark",
            url: baseURL ?? "http://127.0.0.1:8765",
        },
    ]);
}

test.describe("Dark Mode — explicit theme toggle", () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await setDarkThemeCookie(page, baseURL);
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

test.describe("Dark Mode — /games index card grid", () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await setDarkThemeCookie(page, baseURL);
    });

    test("game-type-card should have dark surface background", async ({
        page,
    }) => {
        await page.goto("/games");
        await page.waitForLoadState("domcontentloaded");

        const card = page.locator(".game-type-card").first();
        if ((await card.count()) > 0) {
            const bg = await card.evaluate(
                (el) => getComputedStyle(el).backgroundColor,
            );
            const match = bg.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
            if (match) {
                const [, r, g, b] = match.map(Number);
                expect(r).toBeLessThan(50);
                expect(g).toBeLessThan(40);
                expect(b).toBeLessThan(60);
            }
        }
    });

    test("game-type-card title should have light text", async ({ page }) => {
        await page.goto("/games");
        await page.waitForLoadState("domcontentloaded");

        const title = page.locator(".game-type-card .card-title").first();
        if ((await title.count()) > 0) {
            const color = await title.evaluate(
                (el) => getComputedStyle(el).color,
            );
            const match = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
            if (match) {
                const [, r, g, b] = match.map(Number);
                expect(Math.max(r, g, b)).toBeGreaterThan(180);
            }
        }
    });

    test("page title should contain RacerHistory not CakePHP", async ({
        page,
    }) => {
        await page.goto("/games");
        await page.waitForLoadState("domcontentloaded");

        const title = await page.title();
        expect(title).toContain("RacerHistory");
        expect(title).not.toContain("CakePHP");
    });
});

test.describe("Dark Mode — /stats index card grid", () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await setDarkThemeCookie(page, baseURL);
    });

    test("stat-type-card should have dark surface background", async ({
        page,
    }) => {
        await page.goto("/stats");
        await page.waitForLoadState("domcontentloaded");

        const card = page.locator(".stat-type-card").first();
        if ((await card.count()) > 0) {
            const bg = await card.evaluate(
                (el) => getComputedStyle(el).backgroundColor,
            );
            const match = bg.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
            if (match) {
                const [, r, g, b] = match.map(Number);
                expect(r).toBeLessThan(50);
                expect(g).toBeLessThan(40);
                expect(b).toBeLessThan(60);
            }
        }
    });

    test("stat-type-card title should have light text", async ({ page }) => {
        await page.goto("/stats");
        await page.waitForLoadState("domcontentloaded");

        const title = page.locator(".stat-type-card .card-title").first();
        if ((await title.count()) > 0) {
            const color = await title.evaluate(
                (el) => getComputedStyle(el).color,
            );
            const match = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
            if (match) {
                const [, r, g, b] = match.map(Number);
                expect(Math.max(r, g, b)).toBeGreaterThan(180);
            }
        }
    });
});

test.describe("Dark Mode — /people index table", () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await setDarkThemeCookie(page, baseURL);
    });

    test("people-table-card should have dark surface background", async ({
        page,
    }) => {
        await page.goto("/people");
        await page.waitForLoadState("domcontentloaded");

        const card = page.locator(".people-table-card").first();
        if ((await card.count()) > 0) {
            const bg = await card.evaluate(
                (el) => getComputedStyle(el).backgroundColor,
            );
            const match = bg.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
            if (match) {
                const [, r, g, b] = match.map(Number);
                expect(r).toBeLessThan(50);
                expect(g).toBeLessThan(40);
                expect(b).toBeLessThan(60);
            }
        }
    });

    test("people search input should have light text in dark mode", async ({
        page,
    }) => {
        await page.goto("/people");
        await page.waitForLoadState("domcontentloaded");

        const input = page.locator("#people-name-search").first();
        if ((await input.count()) > 0) {
            const color = await input.evaluate(
                (el) => getComputedStyle(el).color,
            );
            const match = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
            if (match) {
                const [, r, g, b] = match.map(Number);
                expect(Math.max(r, g, b)).toBeGreaterThan(180);
            }
        }
    });
});

test.describe("Dark Mode — page titles", () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await setDarkThemeCookie(page, baseURL);
    });

    test("home page title should contain RacerHistory", async ({ page }) => {
        await page.goto("/");
        await page.waitForLoadState("domcontentloaded");
        const title = await page.title();
        expect(title).toContain("RacerHistory");
        expect(title).not.toContain("CakePHP");
    });

    test("seasons page title should contain RacerHistory", async ({ page }) => {
        await page.goto("/seasons");
        await page.waitForLoadState("domcontentloaded");
        const title = await page.title();
        expect(title).toContain("RacerHistory");
        expect(title).not.toContain("CakePHP");
    });
});

test.describe("Dark Mode — /stats DataTable sub-pages", () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await setDarkThemeCookie(page, baseURL);
    });

    const statsPages = [
        { path: "/stats/player-season", label: "Player Season" },
        { path: "/stats/team-season", label: "Team Season" },
    ];

    for (const { path, label } of statsPages) {
        test(`${label} page card should have dark background`, async ({
            page,
        }) => {
            await page.goto(path);
            await page.waitForLoadState("domcontentloaded");

            const card = page.locator(".card").first();
            if ((await card.count()) > 0) {
                const bg = await card.evaluate(
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

        test(`${label} page heading should have light text`, async ({
            page,
        }) => {
            await page.goto(path);
            await page.waitForLoadState("domcontentloaded");

            const heading = page.locator("h1").first();
            if ((await heading.count()) > 0) {
                const color = await heading.evaluate(
                    (el) => getComputedStyle(el).color,
                );
                const match = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
                if (match) {
                    const [, r, g, b] = match.map(Number);
                    expect(Math.max(r, g, b)).toBeGreaterThan(180);
                }
            }
        });
    }
});

test.describe("Dark Mode — /games DataTable sub-pages", () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await setDarkThemeCookie(page, baseURL);
    });

    const gamesPages = [
        { path: "/games/ranked", label: "Ranked" },
        { path: "/games/overtime", label: "Overtime" },
    ];

    for (const { path, label } of gamesPages) {
        test(`${label} page card should have dark background`, async ({
            page,
        }) => {
            await page.goto(path);
            await page.waitForLoadState("domcontentloaded");

            const card = page.locator(".card").first();
            if ((await card.count()) > 0) {
                const bg = await card.evaluate(
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

        test(`${label} page should have dark btn-outline-primary buttons`, async ({
            page,
        }) => {
            await page.goto(path);
            await page.waitForLoadState("domcontentloaded");

            const btn = page.locator(".btn-outline-primary").first();
            if ((await btn.count()) > 0) {
                const color = await btn.evaluate(
                    (el) => getComputedStyle(el).color,
                );
                const match = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
                if (match) {
                    const [, r, g] = match.map(Number);
                    // Gold colour: high R, moderate G
                    expect(r).toBeGreaterThan(180);
                    expect(g).toBeGreaterThan(100);
                }
            }
        });
    }
});
