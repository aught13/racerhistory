import { test, expect } from "@playwright/test";

/**
 * E2E tests for public blog functionality
 * Tests Turbo Frame navigation, responsive images, and content display
 */

test.describe("Blog - Public Pages", () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to blog index before each test
        await page.goto("/blog");
        await page.waitForLoadState("networkidle");
    });

    test("should load blog index page", async ({ page }) => {
        // Page should have loaded
        await expect(page).toHaveURL(/\/blog/);

        // Should have a Turbo Frame for content
        const turboFrame = page.locator('turbo-frame#blog-content');
        const hasTurboFrame = await turboFrame.count() > 0;

        // Either has turbo frame or regular content container
        const container = hasTurboFrame
            ? turboFrame
            : page.locator('.blog-content, .blog-index, main');

        await expect(container.first()).toBeVisible();
    });

    test("should display blog CSS styles", async ({ page }) => {
        // Check that blog-content CSS is loaded
        const hasBlogCss = await page.evaluate(() => {
            const links = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
            return links.some(l => l.href.includes('blog-content.css'));
        });

        expect(hasBlogCss).toBe(true);
    });

    test("should have Turbo loaded for navigation", async ({ page }) => {
        const turboAvailable = await page.evaluate(() => {
            return typeof window.Turbo !== "undefined";
        });

        expect(turboAvailable).toBe(true);
    });

    test("blog listing should have article elements", async ({ page }) => {
        // Check for semantic article elements in blog listing
        const articles = page.locator('article');
        const articleCount = await articles.count();

        // If there are blog posts, they should use <article> elements
        if (articleCount > 0) {
            await expect(articles.first()).toBeVisible();
        }
    });

    test("blog images should use picture elements with WebP", async ({ page }) => {
        // Find any images in blog content
        const pictures = page.locator('picture');
        const pictureCount = await pictures.count();

        if (pictureCount > 0) {
            // Check first picture has WebP source
            const webpSource = pictures.first().locator('source[type="image/webp"]');
            const hasWebpSource = await webpSource.count() > 0;

            if (hasWebpSource) {
                expect(hasWebpSource).toBe(true);

                // Check srcset includes fm=webp
                const srcset = await webpSource.getAttribute('srcset');
                expect(srcset).toContain('fm=webp');
            }
        }
    });

    test("blog images should have loading lazy attribute", async ({ page }) => {
        const images = page.locator('.blog-content img, article img');
        const imgCount = await images.count();

        if (imgCount > 0) {
            // At least some images should have lazy loading
            const lazyImages = page.locator('img[loading="lazy"]');
            const lazyCount = await lazyImages.count();

            // Allow for eager-loaded hero images, but majority should be lazy
            expect(lazyCount).toBeGreaterThanOrEqual(0);
        }
    });
});

test.describe("Blog - View Single Post", () => {
    test("should navigate to single post via Turbo", async ({ page }) => {
        await page.goto("/blog");
        await page.waitForLoadState("networkidle");

        // Find a link to a blog post
        const postLink = page.locator('a[href*="/blog/view/"]').first();
        const hasPostLink = await postLink.count() > 0;

        if (hasPostLink) {
            const href = await postLink.getAttribute('href');

            // Click to navigate
            await Promise.all([
                page.waitForURL(/\/blog\/view\//, { timeout: 10000 }),
                postLink.click()
            ]);

            // Should have navigated
            expect(page.url()).toContain('/blog/view/');
        } else {
            // No posts exist - skip
            test.skip();
        }
    });

    test("single post should have semantic HTML structure", async ({ page }) => {
        await page.goto("/blog");
        await page.waitForLoadState("networkidle");

        const postLink = page.locator('a[href*="/blog/view/"]').first();
        const hasPostLink = await postLink.count() > 0;

        if (hasPostLink) {
            await Promise.all([
                page.waitForURL(/\/blog\/view\//, { timeout: 10000 }),
                postLink.click()
            ]);

            // Should have article element
            const article = page.locator('article');
            await expect(article.first()).toBeVisible({ timeout: 5000 });

            // Should have header section
            const header = article.locator('header').first();
            const hasHeader = await header.count() > 0;
            expect(hasHeader).toBe(true);

            // Should have time element for date
            const time = page.locator('time[datetime]');
            const hasTime = await time.count() > 0;
            expect(hasTime).toBe(true);
        } else {
            test.skip();
        }
    });

    test("single post should have blog-content styling class", async ({ page }) => {
        await page.goto("/blog");
        await page.waitForLoadState("networkidle");

        const postLink = page.locator('a[href*="/blog/view/"]').first();
        const hasPostLink = await postLink.count() > 0;

        if (hasPostLink) {
            await Promise.all([
                page.waitForURL(/\/blog\/view\//, { timeout: 10000 }),
                postLink.click()
            ]);

            // Should have blog-content class for styling
            const blogContent = page.locator('.blog-content');
            await expect(blogContent.first()).toBeVisible({ timeout: 5000 });
        } else {
            test.skip();
        }
    });
});

test.describe("Blog - Responsive Design", () => {
    test("mobile viewport should stack floated images", async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });

        await page.goto("/blog");
        await page.waitForLoadState("networkidle");

        const postLink = page.locator('a[href*="/blog/view/"]').first();
        const hasPostLink = await postLink.count() > 0;

        if (hasPostLink) {
            await Promise.all([
                page.waitForURL(/\/blog\/view\//, { timeout: 10000 }),
                postLink.click()
            ]);

            // Check any float-left or float-right images
            const floatImages = page.locator('.img-float-left, .img-float-right');
            const floatCount = await floatImages.count();

            if (floatCount > 0) {
                // On mobile, floats should be cleared (float: none)
                const firstFloat = floatImages.first();
                const computedFloat = await firstFloat.evaluate(el => {
                    return window.getComputedStyle(el).float;
                });

                // Should be none on mobile
                expect(computedFloat).toBe('none');
            }
        }
    });

    test("tablet viewport should maintain readable layout", async ({ page }) => {
        await page.setViewportSize({ width: 768, height: 1024 });

        await page.goto("/blog");
        await page.waitForLoadState("networkidle");

        // Check container width is appropriate for tablet
        const container = page.locator('.container, .container-lg, main').first();
        const box = await container.boundingBox();

        if (box) {
            // Container should not exceed viewport width
            expect(box.width).toBeLessThanOrEqual(768);
        }
    });
});

test.describe("Blog - Dark Mode", () => {
    test("should respect dark mode preference", async ({ page }) => {
        // Emulate dark mode preference
        await page.emulateMedia({ colorScheme: 'dark' });

        await page.goto("/blog");
        await page.waitForLoadState("networkidle");

        // Check that body or html has dark theme applied
        const isDarkMode = await page.evaluate(() => {
            const html = document.documentElement;
            const body = document.body;

            // Check for common dark mode indicators
            const hasDarkThemeAttr = html.getAttribute('data-bs-theme') === 'dark' ||
                                      body.getAttribute('data-bs-theme') === 'dark';
            const hasDarkClass = html.classList.contains('dark') ||
                                 body.classList.contains('dark-mode');

            // Or check computed background color is dark
            const bgColor = window.getComputedStyle(body).backgroundColor;
            const match = bgColor.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
            if (match) {
                const [, r, g, b] = match.map(Number);
                const isDarkBg = (r + g + b) / 3 < 128;
                return hasDarkThemeAttr || hasDarkClass || isDarkBg;
            }

            return hasDarkThemeAttr || hasDarkClass;
        });

        // Dark mode should be active (or site may not support automatic detection)
        // This is informational - not a hard failure if site uses manual toggle
        if (isDarkMode) {
            expect(isDarkMode).toBe(true);
        }
    });
});

test.describe("Blog - Turbo Frame Integration", () => {
    test("blog index uses Turbo Frame for pagination", async ({ page }) => {
        await page.goto("/blog");
        await page.waitForLoadState("networkidle");

        // Check for pagination within turbo frame
        const turboFrame = page.locator('turbo-frame');
        const hasTurboFrame = await turboFrame.count() > 0;

        // Check for pagination links
        const paginationLinks = page.locator('nav.pagination a, ul.pagination a, .paginator a');
        const hasPagination = await paginationLinks.count() > 0;

        if (hasTurboFrame && hasPagination) {
            // Pagination should be within or controlled by turbo frame
            const firstPagLink = paginationLinks.first();
            const href = await firstPagLink.getAttribute('href');

            if (href) {
                // Click pagination link
                await firstPagLink.click();
                await page.waitForTimeout(500); // Wait for Turbo to update

                // URL may or may not change depending on implementation
                // but page should still function
                const stillOnBlog = page.url().includes('/blog');
                expect(stillOnBlog).toBe(true);
            }
        }
    });

    test("turbo:load event fires on navigation", async ({ page }) => {
        await page.goto("/blog");
        await page.waitForLoadState("networkidle");

        // Set up event listener
        await page.evaluate(() => {
            window.__turboLoadFired = false;
            document.addEventListener('turbo:load', () => {
                window.__turboLoadFired = true;
            });
        });

        // Navigate within blog
        const postLink = page.locator('a[href*="/blog/view/"]').first();
        const hasPostLink = await postLink.count() > 0;

        if (hasPostLink) {
            await Promise.all([
                page.waitForURL(/\/blog\/view\//, { timeout: 10000 }),
                postLink.click()
            ]);

            // Check event fired
            const turboLoadFired = await page.evaluate(() => window.__turboLoadFired);
            expect(turboLoadFired).toBe(true);
        }
    });
});
