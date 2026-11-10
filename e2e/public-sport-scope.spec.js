import { expect, test } from "@playwright/test";

test.describe("Public sport and team scope", () => {
    test("normalizes default scope and includes it in public navigation", async ({
        page,
    }) => {
        await page.goto("/games");

        await expect
            .poll(() => {
                const url = new URL(page.url());
                return [
                    url.searchParams.get("sport"),
                    url.searchParams.get("team"),
                ];
            })
            .toEqual(["all", "all"]);

        const seasonsLink = page.getByRole("link", {
            name: "Seasons",
            exact: true,
        });
        const href = await seasonsLink.getAttribute("href");
        const query = new URL(href, page.url()).searchParams;

        expect(query.get("sport")).toBe("all");
        expect(query.get("team")).toBe("all");
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
