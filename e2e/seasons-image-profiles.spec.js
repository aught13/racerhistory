import { test, expect } from "@playwright/test";

test.describe("Season image profile URLs", () => {
    test("season billboard and roster avatar images use profile-based routes", async ({ page }) => {
        await page.goto("/seasons/1");
        await page.waitForLoadState("networkidle");

        const seasonHero = page.locator(".season-hero-image").first();
        if ((await seasonHero.count()) > 0) {
            await expect(seasonHero).toHaveAttribute("src", /profile=season_billboard/);
            const heroSource = page
                .locator(".season-hero-media picture source[type='image/webp']")
                .first();
            if ((await heroSource.count()) > 0) {
                await expect(heroSource).toHaveAttribute(
                    "srcset",
                    /profile=season_billboard/,
                );
            }
        }

        const rosterAvatar = page
            .locator("img.season-roster-avatar-img")
            .first();
        if ((await rosterAvatar.count()) > 0) {
            await expect(rosterAvatar).toHaveAttribute("src", /profile=roster_avatar/);
            const rosterSource = page
                .locator(".season-roster-avatar picture source[type='image/webp']")
                .first();
            if ((await rosterSource.count()) > 0) {
                await expect(rosterSource).toHaveAttribute(
                    "srcset",
                    /profile=roster_avatar/,
                );
            }
        }
    });
});
