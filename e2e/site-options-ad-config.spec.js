import { expect, test } from "@playwright/test";

import { loginToAdmin } from "./support/auth.js";

test.describe("Advertising campaigns", () => {
    test("shows site sponsors globally and content sponsors only for matching sports", async ({
        page,
    }) => {
        const loggedIn = await loginToAdmin(page, { timeout: 15000 });
        test.skip(!loggedIn, "Could not authenticate as the e2e admin user");

        const suffix = Date.now();
        const siteTitle = `E2E global sponsor ${suffix}`;
        const contentTitle = `E2E basketball sponsor ${suffix}`;
        const siteClass = `e2e-site-sponsor-${suffix}`;
        const contentClass = `e2e-basketball-sponsor-${suffix}`;

        const createCampaign = async ({
            title,
            audience,
            html,
            sport = "",
        }) => {
            await page.goto("/admin/advertising/add", {
                waitUntil: "domcontentloaded",
            });
            await page.getByLabel("Title").fill(title);
            await page.getByLabel("Position").selectOption("below_nav");
            await page.getByLabel("Audience").selectOption(audience);
            if (sport !== "") {
                await page.getByLabel("Sport filter").selectOption(sport);
            }
            await page.getByLabel("Active").check();
            await page.getByLabel("Delivery mode").selectOption("custom");
            await page.getByLabel("HTML").fill(html);
            await page.getByLabel("Display order").fill("0");
            await page.getByRole("button", { name: "Save Advertisement" }).click();
            await page.waitForURL("**/admin/advertising");
        };

        const deleteCampaign = async (title) => {
            await page.goto("/admin/advertising", {
                waitUntil: "domcontentloaded",
            });
            const row = page.locator("tbody tr").filter({ hasText: title });
            if (await row.count()) {
                await row.getByRole("button", { name: "Delete" }).click();
                await expect(row).toHaveCount(0);
            }
        };

        try {
            await createCampaign({
                title: siteTitle,
                audience: "site",
                html: `<div class="${siteClass}" style="min-height:40px;line-height:1.2">Global sponsor</div>`,
            });
            await createCampaign({
                title: contentTitle,
                audience: "content",
                sport: "basketball",
                html: `<div class="${contentClass}" style="min-height:40px;line-height:1.2">Basketball coverage</div>`,
            });

            await page.goto("/games?sport=basketball&team=all", {
                waitUntil: "domcontentloaded",
            });
            await expect(
                page.locator(
                    `main .rh-ad-slot--below-nav .rh-ad-slot__inner .${siteClass}`,
                ),
            ).toBeVisible();
            await expect(
                page.locator(
                    `main .rh-ad-slot--below-nav .rh-ad-slot__inner .${contentClass}`,
                ),
            ).toBeVisible();

            await page.goto("/blog?sport=all&team=all", {
                waitUntil: "domcontentloaded",
            });
            await expect(
                page.locator(
                    `main .rh-ad-slot--below-nav .rh-ad-slot__inner .${siteClass}`,
                ),
            ).toBeVisible();
            await expect(
                page.locator(
                    `main .rh-ad-slot--below-nav .rh-ad-slot__inner .${contentClass}`,
                ),
            ).toHaveCount(0);
        } finally {
            await deleteCampaign(contentTitle);
            await deleteCampaign(siteTitle);
        }
    });
});
