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

    test("saves selected period stats for both teams", async ({ page }) => {
        await page.goto("/admin/basketball-box-score-import/index/1", {
            waitUntil: "domcontentloaded",
        });

        await page.locator("summary").getByText("Use pasted text instead").click();
        await page.locator("#raw-text").fill([
            "Official Basketball Box Score -- Game Totals",
            "VISITORS: Test Racers",
            "Totals.............. 1-2 0-0 0-0 0 0 0 0 2 0 0 0 0 200",
            "HOME TEAM: Test Opponent",
            "Totals.............. 2-4 0-0 0-0 0 0 0 0 4 0 0 0 0 200",
            "Score by Periods 1st 2nd 3rd Total",
            "Test Racers........... 1 1 1 - 3",
            "Test Opponent......... 2 2 2 - 6",
            "PERIOD_DETAIL|0|ORB|1=2",
            "PERIOD_DETAIL|0|RB|1=6",
            "PERIOD_DETAIL|1|ORB|1=1",
            "PERIOD_DETAIL|1|RB|1=5",
            "PERIOD_DETAIL|0|ORB|3=1",
            "PERIOD_DETAIL|0|RB|3=4",
            "PERIOD_DETAIL|1|ORB|3=1",
            "PERIOD_DETAIL|1|RB|3=5",
        ].join("\n"));
        await page.getByRole("button", { name: /Preview Import/ }).click();

        await expect(page.getByText("Period-by-period team totals")).toBeVisible();
        await expect(
            page.locator('input[name="period_boxes_selected[team_1][ORB]"]'),
        ).toBeChecked();
        await expect(
            page.locator('input[name="period_boxes_selected[opponent_1][ORB]"]'),
        ).toBeChecked();
        await expect(
            page.locator('input[name="period_boxes_selected[team_OT][ORB]"]'),
        ).toBeChecked();
        await expect(
            page.locator('input[name="period_boxes_selected[opponent_OT][ORB]"]'),
        ).toBeChecked();
        await expect(
            page.locator('input[name="game_results_selected[overtime_1_team]"]'),
        ).toBeChecked();
        await expect(
            page.locator('input[name="game_results_selected[overtime_1_opponent]"]'),
        ).toBeChecked();
        await page.locator("#add-to-totals").check();

        const saveResponse = await page.locator("form").evaluate(async (form) => {
            const formData = new FormData(form);
            formData.set("intent", "save");

            const response = await fetch(form.action, {
                method: "POST",
                body: formData,
                credentials: "same-origin",
            });

            return {
                body: await response.text(),
                redirected: response.redirected,
                status: response.status,
            };
        });

        expect(saveResponse.status).toBe(200);
        expect(saveResponse.redirected).toBe(true);
        expect(saveResponse.body).toContain("The selected box-score data was imported.");
    });
});