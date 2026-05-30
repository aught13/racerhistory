export const E2E_USERNAME = process.env.PLAYWRIGHT_E2E_USERNAME || "e2e";
export const E2E_PASSWORD =
    process.env.PLAYWRIGHT_E2E_PASSWORD || "Racersbb1952!";

/**
 * Attempt to authenticate into the admin area via the CakeDC/Users login form.
 * Returns true on success and false when the server or credentials are unavailable.
 */
export async function loginToAdmin(page, options = {}) {
    const { waitUntil = "networkidle", timeout = 5000 } = options;

    try {
        await page.goto("/login", { waitUntil, timeout });
        await page.fill('input[name="username"]', E2E_USERNAME);
        await page.fill('input[name="password"]', E2E_PASSWORD);
        await page.click('button[type="submit"]');
        await page.waitForURL((url) => !url.pathname.includes("login"), {
            timeout,
        });

        return true;
    } catch {
        return false;
    }
}
