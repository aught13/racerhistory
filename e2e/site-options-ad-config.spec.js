import { expect, test } from "@playwright/test";

import { loginToAdmin } from "./support/auth.js";

test.describe("Site Options ad configuration", () => {
    test("saves dummy GPT and HTML settings and applies them publicly", async ({
        page,
    }) => {
        const loggedIn = await loginToAdmin(page, { timeout: 15000 });
        test.skip(!loggedIn, "Could not authenticate as the e2e admin user");

        await page.goto("/admin/site-options/edit", {
            waitUntil: "domcontentloaded",
        });

        await page
            .locator('input[type="checkbox"][name="ad_below_nav_active"]')
            .check();
        await page
            .locator('select[name="ad_below_nav_mode"]')
            .selectOption("gpt");
        await page
            .locator('textarea[name="ad_below_nav_html"]')
            .fill('<div class="dummy-gpt-fallback">Dummy GPT fallback</div>');
        await page
            .locator('input[name="ad_below_nav_sizes_desktop"]')
            .fill("970x250, 728x90");
        await page
            .locator('input[name="ad_below_nav_sizes_mobile"]')
            .fill("320x50");
        await page
            .locator('input[name="ad_below_nav_gpt_unit_path"]')
            .fill("/999999/racerhistory/e2e-dummy-gpt");

        await page
            .locator('input[type="checkbox"][name="ad_footer_active"]')
            .check();
        await page
            .locator('select[name="ad_footer_mode"]')
            .selectOption("custom");
        await page
            .locator('textarea[name="ad_footer_html"]')
            .fill(
                '<div class="dummy-footer-house-ad">Dummy footer house ad</div>',
            );

        await page.getByRole("button", { name: "Save Settings" }).click();
        await page.waitForURL("**/admin/site-options/edit");

        await expect(
            page.locator('select[name="ad_below_nav_mode"]'),
        ).toHaveValue("gpt");
        await expect(
            page.locator('input[name="ad_below_nav_gpt_unit_path"]'),
        ).toHaveValue("/999999/racerhistory/e2e-dummy-gpt");
        await expect(
            page.locator('input[name="ad_below_nav_sizes_desktop"]'),
        ).toHaveValue("970x250, 728x90");
        await expect(
            page.locator('textarea[name="ad_footer_html"]'),
        ).toHaveValue(
            '<div class="dummy-footer-house-ad">Dummy footer house ad</div>',
        );

        await page.goto("/", { waitUntil: "domcontentloaded" });

        const belowNav = page.locator("main .rh-ad-slot--below-nav");
        await expect(belowNav).toHaveAttribute(
            "data-ad-delivery-mode-value",
            "gpt",
        );
        await expect(belowNav).toHaveAttribute(
            "data-ad-delivery-gpt-unit-path-value",
            "/999999/racerhistory/e2e-dummy-gpt",
        );

        const payload = await belowNav.evaluate((element) => ({
            desktop: JSON.parse(
                element.getAttribute("data-ad-delivery-sizes-desktop-value"),
            ),
            mobile: JSON.parse(
                element.getAttribute("data-ad-delivery-sizes-mobile-value"),
            ),
            order: Array.from(
                document.querySelectorAll("main .rh-ad-slot"),
            ).map((item) => item.className),
        }));
        expect(payload.desktop).toEqual([
            [970, 250],
            [728, 90],
        ]);
        expect(payload.mobile).toEqual([[320, 50]]);
        expect(payload.order[0]).toContain("rh-ad-slot--below-nav");

        await expect(
            page.locator("footer .rh-ad-slot--footer .dummy-footer-house-ad"),
        ).toHaveText("Dummy footer house ad");

        const lightBackground = await page.evaluate(() => {
            document.documentElement.dataset.theme = "light";
            return getComputedStyle(
                document.querySelector(".rh-ad-slot--below-nav"),
            ).backgroundColor;
        });
        expect(lightBackground).toBe("rgb(245, 241, 237)");

        const darkBackground = await page.evaluate(() => {
            document.documentElement.dataset.theme = "dark";
            return getComputedStyle(
                document.querySelector(".rh-ad-slot--below-nav"),
            ).backgroundColor;
        });
        expect(darkBackground).toBe("rgb(15, 26, 46)");
    });
});
