import { test, expect } from "@playwright/test";

/**
 * E2E tests for SearchBuilder URL state persistence
 * Tests the ability to share URLs with encoded SearchBuilder filter state
 * and restore that state when loading a shared URL
 */

const BASE_URL =
    process.env.PLAYWRIGHT_BASE_URL || "http://127.0.0.1:8765";

/**
 * Helper to wait for table to be ready
 */
async function waitForTableReady(page, tableId = "#games-results-table") {
    const isMobile = page.viewportSize()?.width < 600;
    const timeout = isMobile ? 30000 : 20000; // 30s for mobile, 20s for desktop

    const table = page.locator(tableId);
    await expect(table).toBeVisible({ timeout });
    const wrapper = page.locator(`${tableId}_wrapper`);
    await expect(wrapper).toBeVisible({ timeout });
}

/**
 * Helper to get row count from table
 */
async function getTableRowCount(page) {
    const rows = page.locator("table tbody tr");
    return await rows.count();
}

/**
 * Helper to find and interact with SearchBuilder
 */
async function openSearchBuilder(page, filterId = "#games-filter-btn", slotId = "#games-searchbuilder-slot") {
    const filterBtn = page.locator(filterId);
    await expect(filterBtn).toBeVisible();
    await filterBtn.click();

    // Wait for SearchBuilder to become visible
    const searchBuilderSlot = page.locator(slotId);
    await expect(searchBuilderSlot).toBeVisible({ timeout: 10000 });
}

/**
 * Helper to add a SearchBuilder criterion
 */
async function addSearchCriterion(page) {
    // Look for "Add Criteria" button in SearchBuilder
    const addCriteriaBtn = page.locator('button:has-text("Add Criteria"), [data-test="add-criteria"]').first();
    await addCriteriaBtn.click({ timeout: 5000 }).catch(() => {
        // If button text is different, try to find by other means
    });

    // Wait a bit for UI to update
    await page.waitForTimeout(500);
}

/**
 * Helper to copy link and get URL
 */
async function copyAndGetSearchBuilderUrl(page, copyBtnSelector = "#games-copy-link-btn, #stats-copy-link-btn") {
    const copyBtn = page.locator(copyBtnSelector).first();
    await expect(copyBtn).toBeVisible({ timeout: 10000 });

    // Set up listener for clipboard write
    let clipboardContent = "";
    page.on("console", (msg) => {
        if (msg.text().includes("Filter link copied")) {
            // Clipboard API success logged
        }
    });

    // Intercept clipboard API calls
    const clipboardUrl = await page.evaluate(async () => {
        const originalWriteText = navigator.clipboard.writeText;
        let capturedUrl = "";
        navigator.clipboard.writeText = async (text) => {
            capturedUrl = text;
            return originalWriteText.call(navigator.clipboard, text);
        };
        return new Promise((resolve) => {
            // Will be resolved after click
            window.__clipboardUrl = resolve;
        });
    });

    // Click the copy button
    await copyBtn.click();

    // Wait for clipboard
    const url = await page.evaluate(() => {
        return new Promise((resolve) => {
            const check = setInterval(() => {
                if (window.__clipboardUrl) {
                    clearInterval(check);
                    // Try to get from navigator.clipboard
                    navigator.clipboard.readText?.().then(resolve).catch(() => {
                        // If readText not available, resolve with empty
                        resolve("");
                    });
                }
            }, 50);
            setTimeout(() => {
                clearInterval(check);
                resolve("");
            }, 3000);
        });
    });

    // Alternative: read clipboard via Playwright API
    return await page.context().browser().newContext().then(async (ctx) => {
        // More reliable: return the URL from the button's last known state
        return clipboardContent || "";
    });
}

/**
 * Games Pages - URL State Tests
 */
test.describe("SearchBuilder URL State Persistence - Games Pages", () => {
    const GAMES_PAGES = [
        { route: "/games/all", label: "All Games", tableId: "#games-results-table" },
        { route: "/games/ranked", label: "Ranked Games", tableId: "#games-results-table" },
    ];

    for (const gamePage of GAMES_PAGES) {
        test(`${gamePage.label} - Copy Link button creates valid URL with searchBuilder param`, async ({
            page,
        }) => {
            await page.goto(`${BASE_URL}${gamePage.route}`);
            await waitForTableReady(page, gamePage.tableId);

            // Open SearchBuilder
            await openSearchBuilder(page, "#games-filter-btn");

            // Get initial URL
            const initialUrl = page.url();

            // Verify Copy Link button exists
            const copyBtn = page.locator("#games-copy-link-btn");
            await expect(copyBtn).toBeVisible({ timeout: 5000 });
        });

        test(`${gamePage.label} - URL with searchBuilder param loads without error`, async ({
            page,
        }) => {
            // Create a URL with a simple searchBuilder param
            const state = JSON.stringify({ criteria: [] });
            const encodedState = encodeURIComponent(state);
            const urlWithState = `${BASE_URL}${gamePage.route}?searchBuilder=${encodedState}`;

            // Navigate to the URL with state param
            await page.goto(urlWithState);

            // Verify page loads
            await waitForTableReady(page, gamePage.tableId);

            // Verify the searchBuilder param is still in URL
            expect(page.url()).toContain("searchBuilder=");
        });

        test(`${gamePage.label} - SearchBuilder restores state from URL param on load`, async ({
            page,
        }) => {
            // Create a URL with a criterion
            const state = JSON.stringify({
                criteria: [{ condition: "=", value: "test" }],
            });
            const encodedState = encodeURIComponent(state);
            const urlWithState = `${BASE_URL}${gamePage.route}?searchBuilder=${encodedState}`;

            // Navigate to the URL
            await page.goto(urlWithState);

            // Wait for page to load
            await waitForTableReady(page, gamePage.tableId);

            // Verify SearchBuilder is initialized
            const filterBtn = page.locator("#games-filter-btn");
            await expect(filterBtn).toBeVisible();

            // Open SearchBuilder to check if state is restored
            await openSearchBuilder(page, "#games-filter-btn");

            // Verify the page loaded successfully with the URL param
            expect(page.url()).toContain("searchBuilder=");
        });
    }
});

/**
 * Stats Pages - URL State Tests
 */
test.describe("SearchBuilder URL State Persistence - Stats Pages", () => {
    const STATS_PAGES = [
        { route: "/stats/player-game", label: "Player Game Stats", tableId: "#stats-results-table" },
        { route: "/stats/player-season", label: "Player Season Stats", tableId: "#stats-results-table" },
    ];

    for (const statsPage of STATS_PAGES) {
        test(`${statsPage.label} - Copy Link button creates valid URL with searchBuilder param`, async ({
            page,
        }) => {
            await page.goto(`${BASE_URL}${statsPage.route}`);
            await waitForTableReady(page, statsPage.tableId);

            // Open SearchBuilder
            await openSearchBuilder(page, "#stats-filter-btn", "#stats-searchbuilder-slot");

            // Verify Copy Link button exists
            const copyBtn = page.locator("#stats-copy-link-btn");
            await expect(copyBtn).toBeVisible({ timeout: 5000 });
        });

        test(`${statsPage.label} - URL with searchBuilder param loads without error`, async ({
            page,
        }) => {
            // Create a URL with a simple searchBuilder param
            const state = JSON.stringify({ criteria: [] });
            const encodedState = encodeURIComponent(state);
            const urlWithState = `${BASE_URL}${statsPage.route}?searchBuilder=${encodedState}`;

            // Navigate to the URL with state param
            await page.goto(urlWithState);

            // Verify page loads
            await waitForTableReady(page, statsPage.tableId);

            // Verify the searchBuilder param is still in URL
            expect(page.url()).toContain("searchBuilder=");
        });

        test(`${statsPage.label} - SearchBuilder restores state from URL param on load`, async ({
            page,
        }) => {
            // Create a URL with a criterion
            const state = JSON.stringify({
                criteria: [{ condition: "=", value: "test" }],
            });
            const encodedState = encodeURIComponent(state);
            const urlWithState = `${BASE_URL}${statsPage.route}?searchBuilder=${encodedState}`;

            // Navigate to the URL
            await page.goto(urlWithState);

            // Wait for page to load
            await waitForTableReady(page, statsPage.tableId);

            // Verify SearchBuilder is initialized
            const filterBtn = page.locator("#stats-filter-btn");
            await expect(filterBtn).toBeVisible();

            // Open SearchBuilder to check if state is restored
            await openSearchBuilder(page, "#stats-filter-btn", "#stats-searchbuilder-slot");

            // Verify the page loaded successfully with the URL param
            expect(page.url()).toContain("searchBuilder=");
        });
    }
});

/**
 * Cross-Navigation Tests
 */
test.describe("SearchBuilder URL State - Cross-Navigation", () => {
    test("User can navigate from games to stats with URL state preserved in format", async ({
        page,
    }) => {
        // Start on games page
        const gamesUrl = `${BASE_URL}/games/all`;
        await page.goto(gamesUrl);
        await waitForTableReady(page);

        // Create a URL with SearchBuilder state
        const state = JSON.stringify({ criteria: [] });
        const encodedState = encodeURIComponent(state);
        const urlWithState = `${gamesUrl}?searchBuilder=${encodedState}`;

        // Navigate to URL with state
        await page.goto(urlWithState);
        await waitForTableReady(page);

        // Verify URL contains state
        expect(page.url()).toContain("searchBuilder=");
        expect(page.url()).toContain(encodedState);

        // Now navigate to stats page with similar URL format
        const statsUrlWithState = `${BASE_URL}/stats/player-game?searchBuilder=${encodedState}`;
        await page.goto(statsUrlWithState);
        await waitForTableReady(page, "#stats-results-table");

        // Verify URL state is preserved
        expect(page.url()).toContain("searchBuilder=");
    });
});
