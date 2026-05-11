import { test, expect } from "@playwright/test";

async function loginAsAdmin(page) {
    try {
        await page.goto("/login", { waitUntil: "networkidle", timeout: 15000 });
        await page.fill('input[name="username"]', "admin");
        await page.fill('input[name="password"]', "admin");
        await page.click('button[type="submit"]');
        await page.waitForURL((url) => !url.pathname.includes("login"), {
            timeout: 15000,
        });

        return true;
    } catch {
        return false;
    }
}

/**
 * Navigate to the admin images index and return the ID of the first image.
 * Returns null if no images are found.
 */
async function getFirstImageId(page) {
    // Use domcontentloaded — the images index fires AJAX for DataTables and never
    // reaches networkidle, so waiting for networkidle would always time out.
    await page.goto("/admin/images", { waitUntil: "domcontentloaded" });

    // Wait for at least one Edit link to appear (DataTable may need to render)
    const editLink = page.locator('a[href*="/admin/images/edit/"]').first();
    try {
        await editLink.waitFor({ state: "visible", timeout: 10000 });
    } catch {
        return null;
    }

    const href = await editLink.getAttribute("href");
    const match = href && href.match(/\/admin\/images\/edit\/(\d+)/);

    return match ? parseInt(match[1], 10) : null;
}

test.describe("Admin image pages first-load behavior", () => {
    test.beforeEach(async ({ page }) => {
        const loggedIn = await loginAsAdmin(page);
        test.skip(!loggedIn, "Could not authenticate as admin in test environment");
    });

    test("bulk upload file selector initializes on first load", async ({ page }) => {
        await page.goto("/admin/images/bulk-upload-form", {
            waitUntil: "networkidle",
        });

        await expect(page.locator("#bulkUploadForm")).toBeVisible();

        await page.setInputFiles("#uploads", [
            {
                name: "first.png",
                mimeType: "image/png",
                buffer: Buffer.from(
                    "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=",
                    "base64",
                ),
            },
            {
                name: "second.png",
                mimeType: "image/png",
                buffer: Buffer.from(
                    "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=",
                    "base64",
                ),
            },
        ]);

        await expect(page.locator("#uploadAll")).toBeEnabled();
        await expect(page.locator("#fileList")).toContainText("first.png");
        await expect(page.locator("#fileList")).toContainText("second.png");
    });

    test("crop thumb tools initialize on first load", async ({ page }) => {
        test.setTimeout(60000);
        const imageId = await getFirstImageId(page);
        test.skip(imageId === null, "No images available in this environment");

        await page.goto(`/admin/images/crop-thumb/${imageId}`, {
            waitUntil: "domcontentloaded",
        });

        // Verify we landed on the crop-thumb page (not redirected)
        await expect(page).toHaveURL(/\/admin\/images\/crop-thumb\/\d+/);

        await expect(page.locator("#crop-container")).toBeVisible();
        await expect(page.locator("#crop-image")).toBeVisible();
        await expect(page.locator("#preview-canvas")).toBeVisible();

        // Verify JS initializer ran and registered the resetCrop handler
        await page.waitForFunction(() => typeof window.resetCrop === "function", {
            timeout: 5000,
        });
    });

    test("manipulate tools initialize on first load", async ({ page }) => {
        test.setTimeout(60000);
        const imageId = await getFirstImageId(page);
        test.skip(imageId === null, "No images available in this environment");

        await page.goto(`/admin/images/manipulate/${imageId}`, {
            waitUntil: "domcontentloaded",
        });

        // Verify we landed on the manipulate page (not redirected)
        await expect(page).toHaveURL(/\/admin\/images\/manipulate\/\d+/);

        await expect(page.locator("#previewCanvas")).toBeVisible();
        await expect(page.locator("#sourceImage")).toBeAttached();

        // Verify JS initializer ran and registered the manipulation handlers
        await page.waitForFunction(
            () =>
                typeof window.setRotation === "function" &&
                typeof window.setAspectRatio === "function" &&
                typeof window.resetAll === "function",
            { timeout: 8000 },
        );
    });
});
