import { test, expect } from "@playwright/test";

test.describe("Series History", () => {
    test("selected opponent hides the picker until requested", async ({
        page,
    }) => {
        await page.goto("/games/series?opponent_id=2", {
            waitUntil: "domcontentloaded",
        });

        await expect(
            page.getByRole("heading", { name: "Series History" }),
        ).toBeVisible();
        await expect(page.locator("#series-opponents-picker-toggle")).toBeVisible();
        await expect(page.locator("#series-opponents-picker-panel")).toBeHidden();
        await expect(page.locator("#series-opponents-table_wrapper")).toHaveCount(0);

        await page.locator("#series-opponents-picker-toggle").click();

        await expect(page.locator("#series-opponents-picker-panel")).toBeVisible();
        await expect(page.locator("#series-opponents-search")).toBeFocused();
        await expect(page.locator("#series-opponents-table_wrapper")).toBeVisible();
    });

    test("series page without a selected opponent shows the picker", async ({
        page,
    }) => {
        await page.goto("/games/series", { waitUntil: "domcontentloaded" });

        await expect(page.locator("#series-opponents-picker-panel")).toBeVisible();
        await expect(page.locator("#series-opponents-search")).toBeVisible();
    });
});