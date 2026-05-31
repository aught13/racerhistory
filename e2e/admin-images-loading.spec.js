import { test, expect } from "@playwright/test";
import { loginToAdmin } from "./support/auth.js";

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
        const loggedIn = await loginToAdmin(page, { timeout: 15000 });
        let blockedFromAdmin = !loggedIn;

        if (loggedIn) {
            try {
                await page.goto("/admin/", {
                    waitUntil: "domcontentloaded",
                    timeout: 10000,
                });
            } catch {
                blockedFromAdmin = true;
            }
        }

        if (!blockedFromAdmin) {
            const loginNotice = page
                .locator("text=You must be logged in to access the admin area.")
                .first();
            blockedFromAdmin =
                (await loginNotice.count()) > 0 || page.url().includes("/login");
        }

        test.skip(blockedFromAdmin, "Could not authenticate as the e2e admin user");
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

        // Verify Stimulus initialization populated crop inputs.
        await expect(page.locator("#crop_width")).not.toHaveValue("0", {
            timeout: 5000,
        });
        await expect(page.locator("#crop_height")).not.toHaveValue("0", {
            timeout: 5000,
        });

        // Verify the reset action remains wired through Stimulus.
        await page.click('button:has-text("Reset")');
        await expect(page.locator("#crop_width")).not.toHaveValue("0", {
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

        // Verify manipulation controls are wired via Stimulus actions.
        await expect(page.locator("#rotate")).toHaveValue("0");
        await page.click('button[data-admin-image-manipulate-degrees-param="90"]');
        await expect(page.locator("#rotate")).toHaveValue("90");
        await page.fill("#rotate", "12");
        await page.dispatchEvent("#rotate", "input");
        await expect(page.locator("#rotate-range")).toHaveValue("12");
    });
});
