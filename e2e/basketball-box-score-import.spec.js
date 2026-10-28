import { expect, test } from "@playwright/test";
import { loginToAdmin } from "./support/auth.js";

test.describe("Basketball box-score import", () => {
    test.beforeEach(async ({ page }) => {
        const loggedIn = await loginToAdmin(page, {
            waitUntil: "domcontentloaded",
            timeout: 15000,
        });
        test.skip(!loggedIn, "Could not authenticate as the e2e admin user");
    });

    test("posts the FormProtection token during preview", async ({ page }) => {
        await page.goto("/admin/basketball-box-score-import/index/1", {
            waitUntil: "domcontentloaded",
        });

        const tokenFields = await page.locator("form").evaluate((form) =>
            Array.from(new FormData(form).keys()).filter((name) =>
                name.startsWith("_Token"),
            ),
        );
        expect(tokenFields).toContain("_Token[fields]");

        await page.locator("summary").getByText("Use pasted text instead").click();
        await page.locator("#raw-text").fill("not a box score");
        await page.getByRole("button", { name: /Preview Import/ }).click();

        await expect(
            page.getByText(/does not look like an NCAA LiveStats box score/),
        ).toBeVisible();
    });
});