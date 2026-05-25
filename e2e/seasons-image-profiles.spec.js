import { test, expect } from "@playwright/test";

test.describe("Season image profile URLs", () => {
    test("season billboard and roster avatar images use storage routes", async ({ page }) => {
        await page.goto("/seasons/1");
        await page.waitForLoadState("domcontentloaded");

        const seasonHero = page.locator(".season-hero-image").first();
        if ((await seasonHero.count()) > 0) {
            await expect(seasonHero).toHaveAttribute("src", /\/img\/storage\//);
            const heroSource = page
                .locator(".season-hero-media picture source[type='image/webp']")
                .first();
            if ((await heroSource.count()) > 0) {
                await expect(heroSource).toHaveAttribute("srcset", /\/img\/storage\//);
            }
        }

        const rosterAvatar = page
            .locator("img.season-roster-avatar-img")
            .first();
        if ((await rosterAvatar.count()) > 0) {
            const avatarSrc = (await rosterAvatar.getAttribute("src")) ?? "";
            const avatarThumbSrc =
                (await rosterAvatar.getAttribute("data-thumb-src")) ?? "";
            expect(`${avatarSrc} ${avatarThumbSrc}`).toMatch(/\/img\/storage\//);

            const rosterSource = page
                .locator(".season-roster-avatar picture source[type='image/webp']")
                .first();
            if ((await rosterSource.count()) > 0) {
                await expect(rosterSource).toHaveAttribute("srcset", /\/img\/storage\//);
            }
        }
    });
});
