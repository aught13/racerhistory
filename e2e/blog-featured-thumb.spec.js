import { test, expect } from "@playwright/test";

test.describe("Blog featured thumb behavior", () => {
    test("featured hero collapses to a thumbnail with an image when another post expands", async ({ page }) => {
        await page.goto("/blog");
        await page.waitForLoadState("networkidle");

        const featuredFrame = page.locator("turbo-frame.blog-featured-frame");
        test.skip((await featuredFrame.count()) === 0, "No featured blog frame is available.");

        const listItems = page.locator("#blog-list .blog-list-item");
        test.skip((await listItems.count()) === 0, "No secondary blog posts are available to expand.");

        const featuredHero = featuredFrame.locator(".blog-featured");
        const featuredThumb = featuredFrame.locator(".blog-featured-as-list");

        await expect(featuredHero).toBeVisible();
        await expect(featuredThumb).toBeHidden();

        await listItems.first().click();

        await expect(page.locator(".blog-post-view").first()).toBeVisible({ timeout: 10000 });
        await expect(featuredHero).toBeHidden({ timeout: 10000 });
        await expect(featuredThumb).toBeVisible({ timeout: 10000 });

        const thumbState = await page.evaluate(() => {
            const thumb = document.querySelector(".blog-featured-as-list");

            return {
                hidden: thumb?.classList.contains("d-none") ?? true,
                hasImg: Boolean(thumb?.querySelector("img")),
            };
        });

        expect(thumbState.hidden).toBe(false);
        expect(thumbState.hasImg).toBe(true);
    });

    test("featured image markup uses the public image serve route", async ({ page }) => {
        await page.goto("/blog");
        await page.waitForLoadState("networkidle");

        const featuredPicture = page.locator("turbo-frame.blog-featured-frame picture").first();
        test.skip((await featuredPicture.count()) === 0, "No featured image is rendered on the blog page.");

        const source = featuredPicture.locator('source[type="image/webp"]').first();
        const img = featuredPicture.locator("img").first();

        await expect(source).toHaveAttribute("srcset", /\/images\/serve\/\d+/);
        await expect(source).toHaveAttribute("srcset", /profile=blog_featured/);
        await expect(img).toHaveAttribute("src", /\/images\/serve\/\d+/);
        await expect(img).toHaveAttribute("src", /profile=blog_featured/);

        const featuredListThumb = page
            .locator(".blog-featured-as-list picture img")
            .first();
        if ((await featuredListThumb.count()) > 0) {
            await expect(featuredListThumb).toHaveAttribute(
                "src",
                /profile=blog_index_card/,
            );
        }
    });
});
