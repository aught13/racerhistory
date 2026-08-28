export const E2E_USERNAME = process.env.PLAYWRIGHT_E2E_USERNAME || "e2e";
export const E2E_PASSWORD =
    process.env.PLAYWRIGHT_E2E_PASSWORD || "Racersbb1952!";

const FALLBACK_USERNAME =
    process.env.PLAYWRIGHT_E2E_FALLBACK_USERNAME || "admin";
const FALLBACK_PASSWORD =
    process.env.PLAYWRIGHT_E2E_FALLBACK_PASSWORD || "administrator";

function isLoginPath(pathname) {
    const path = String(pathname || "").toLowerCase();

    return (
        path === "/login" ||
        path === "/users/login" ||
        path === "/admin/users/login"
    );
}

/**
 * Attempt to authenticate into the admin area via the CakeDC/Users login form.
 * Returns true on success and false when the server or credentials are unavailable.
 */
async function verifyAdminSession(page, waitUntil, timeout) {
    try {
        await page.goto("/admin/", { waitUntil, timeout });
    } catch {
        // If the app redirects back to login, the admin session is not valid.
    }

    const loginNotice = page
        .locator("text=You must be logged in to access the admin area.")
        .first();
    const currentPath = new URL(page.url()).pathname;
    const blockedFromAdmin =
        isLoginPath(currentPath) || (await loginNotice.count()) > 0;

    return !blockedFromAdmin;
}

export async function loginToAdmin(page, options = {}) {
    const { waitUntil = "networkidle", timeout = 5000 } = options;
    const loginPaths = [
        "/login",
        "/users/login",
        "/admin/users/login",
    ];
    const credentialAttempts = [
        [E2E_USERNAME, E2E_PASSWORD],
        [FALLBACK_USERNAME, FALLBACK_PASSWORD],
    ];

    try {
        for (const loginPath of loginPaths) {
            for (const [username, password] of credentialAttempts) {
                await page.goto(loginPath, { waitUntil, timeout });
                const hasUsernameField =
                    (await page.locator('input[name="username"]').count()) > 0;
                if (!hasUsernameField) {
                    continue;
                }

                const submitButton = page.locator('button[type="submit"]').first();
                if ((await submitButton.count()) === 0) {
                    continue;
                }

                await page.fill('input[name="username"]', "");
                await page.fill('input[name="password"]', "");
                await page.fill('input[name="username"]', username);
                await page.fill('input[name="password"]', password);

                await submitButton.click();

                try {
                    await page.waitForURL(
                        (url) => !isLoginPath(url.pathname),
                        { timeout },
                    );
                } catch {
                    // Fall through to the explicit admin-session verification.
                }

                if (await verifyAdminSession(page, waitUntil, timeout)) {
                    return true;
                }

                const invalidCredsMessage = page
                    .locator(
                        "text=Invalid username or password, text=Username or password is incorrect",
                    )
                    .first();
                if ((await invalidCredsMessage.count()) > 0) {
                    continue;
                }

                // Give delayed redirects a short grace period.
                await page.waitForTimeout(300);
                if (await verifyAdminSession(page, waitUntil, timeout)) {
                    return true;
                }
            }
        }

        return false;
    } catch {
        return false;
    }
}
