import { expect, test } from "@playwright/test";

test.describe("Public sport and team scope", () => {
    test("keeps all/all Games scope unselected and uses static Stats/Games links", async ({
        page,
    }) => {
        await page.goto("/games?sport=all&team=all");

        await expect(
            page.locator(".rh-public-scope-current"),
        ).toHaveText("All teams");
        await expect(
            page.locator(".rh-public-scope-current"),
        ).toHaveJSProperty("tagName", "BUTTON");
        await expect(page.locator(".rh-public-scope-bar")).toBeVisible();
        await expect(page.getByText("Select a team", { exact: false })).toBeVisible();

        await page
            .locator(".rh-public-scope-bar-inner")
            .click({ position: { x: 300, y: 16 } });
        await expect(
            page.locator(".rh-public-scope-menu").getByText("All teams", {
                exact: true,
            }),
        ).toBeVisible();

        const innerBox = await page
            .locator(".rh-public-scope-bar-inner")
            .boundingBox();
        const menuBox = await page
            .locator(".rh-public-scope-menu")
            .boundingBox();
        const allTeamsBox = await page
            .locator(".rh-public-scope-menu-all")
            .boundingBox();
        expect(menuBox).not.toBeNull();
        expect(innerBox).not.toBeNull();
        expect(allTeamsBox).not.toBeNull();
        expect(menuBox.width).toBeGreaterThanOrEqual(innerBox.width);
        expect(allTeamsBox.x).toBeGreaterThanOrEqual(innerBox.x);

        const statsLink = page.getByRole("link", { name: "Stats", exact: true });
        const gamesLink = page.getByRole("link", { name: "Games", exact: true });
        await expect(statsLink).toHaveAttribute("href", "/stats");
        await expect(gamesLink).toHaveAttribute("href", "/games");
    });

    test("preserves explicit sport and team scope in public navigation", async ({
        page,
    }) => {
        await page.goto("/games?sport=basketball&team=1");

        await expect(
            page.locator(".rh-nav-item--games > button"),
        ).toHaveCount(1);
        await expect(
            page.locator(".rh-nav-item--games > a"),
        ).toHaveCount(0);

        const seasonsLink = page.getByRole("link", {
            name: "Seasons",
            exact: true,
        });
        const href = await seasonsLink.getAttribute("href");
        const query = new URL(href, page.url()).searchParams;

        expect(query.get("sport")).toBe("basketball");
        expect(query.get("team")).toBe("1");
    });

    test("keeps sport-with-all-team scope static in top navigation", async ({
        page,
    }) => {
        await page.goto("/games?sport=basketball&team=all");

        const statsLink = page.getByRole("link", {
            name: "Stats",
            exact: true,
        });
        const gamesLink = page.getByRole("link", {
            name: "Games",
            exact: true,
        });
        const statsHref = await statsLink.getAttribute("href");
        const gamesHref = await gamesLink.getAttribute("href");
        const statsUrl = new URL(statsHref, page.url());
        const gamesUrl = new URL(gamesHref, page.url());
        expect(statsUrl.pathname).toBe("/stats");
        expect(statsUrl.searchParams.get("sport")).toBe("basketball");
        expect(statsUrl.searchParams.get("team")).toBe("all");
        expect(gamesUrl.pathname).toBe("/games");
        expect(gamesUrl.searchParams.get("sport")).toBe("basketball");
        expect(gamesUrl.searchParams.get("team")).toBe("all");
        await expect(
            page.getByText("Select a team", { exact: false }),
        ).toBeVisible();
        await expect(
            page.getByRole("button", { name: "Games", exact: true }),
        ).toHaveCount(0);
    });

});
