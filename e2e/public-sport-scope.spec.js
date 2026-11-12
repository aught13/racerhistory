import { expect, test } from "@playwright/test";

test.describe("Public sport and team scope", () => {
    test("selects the only team with results in public navigation", async ({
        page,
    }) => {
        await page.goto("/games");

        await expect(page.locator("#public-scope-team")).toHaveValue("1");

        const seasonsLink = page.getByRole("link", {
            name: "Seasons",
            exact: true,
        });
        const href = await seasonsLink.getAttribute("href");
        const query = new URL(href, page.url()).searchParams;

        expect(query.get("sport")).toBe("basketball");
        expect(query.get("team")).toBe("1");
    });

    test("preserves explicit sport and team scope in public navigation", async ({
        page,
    }) => {
        await page.goto("/games?sport=basketball&team=1");

        const seasonsLink = page.getByRole("link", {
            name: "Seasons",
            exact: true,
        });
        const href = await seasonsLink.getAttribute("href");
        const query = new URL(href, page.url()).searchParams;

        expect(query.get("sport")).toBe("basketball");
        expect(query.get("team")).toBe("1");
    });
});
