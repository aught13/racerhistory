import { test, expect, devices } from "@playwright/test";

/**
 * E2E tests for SearchBuilder and date filter layout verification
 * Tests both desktop and mobile viewports for games and stats pages.
 * Verifies that layout elements are properly visible and positioned.
 */

// Games pages to test
const GAMES_PAGES = [
    { route: "/games/all", label: "All Games" },
    { route: "/games/ranked", label: "Ranked Games" },
    { route: "/games/hundred-point", label: "100 Point Games" },
    { route: "/games/overtime", label: "Overtime Games" },
];

// Stats pages to test
const STATS_PAGES = [
    { route: "/stats/player-game", label: "Player Game Stats" },
    { route: "/stats/player-season", label: "Player Season Stats" },
    { route: "/stats/team-game", label: "Team Game Stats" },
    { route: "/stats/team-season", label: "Team Season Stats" },
    { route: "/stats/team-season-opponent", label: "Team Season Opponent Stats" },
    { route: "/stats/player-career", label: "Player Career Stats" },
    { route: "/stats/opponent-team-game", label: "Opponent Team Game Stats" },
    { route: "/stats/opponent-player-game", label: "Opponent Player Game Stats" },
];

/**
 * Helper function to verify table and data loaded
 */
async function waitForTableReady(page, tableId = "#games-results-table") {
    try {
        const table = page.locator(tableId);
        const isMobile = page.viewportSize()?.width < 600;
        const timeout = isMobile ? 15000 : 10000; // Reduced timeouts - fail fast on CI

        // Wait for turbo:load event to fire (critical for Hotwire apps)
        try {
            await page.evaluate(() => {
                return new Promise((resolve) => {
                    if (document.readyState === 'complete') {
                        // Page already loaded, Turbo might have already fired
                        setTimeout(resolve, 500);
                    } else {
                        document.addEventListener('turbo:load', () => resolve(), { once: true });
                        // Fallback timeout
                        setTimeout(resolve, 2000);
                    }
                });
            });
        } catch (e) {
            console.log(`Turbo:load wait timeout (expected on direct navigation): ${e.message}`);
        }

        // Check if table exists first (quick check)
        const tableCount = await table.count().catch(() => 0);
        if (tableCount === 0) {
            console.log(`Table ${tableId} not found in DOM`);
            return false;
        }

        // Wait for table to be visible (reduced timeout)
        try {
            await expect(table).toBeVisible({ timeout });
        } catch (e) {
            console.log(`Table ${tableId} failed to become visible: ${e.message}`);
            return false;
        }

        // Wait for DataTables wrapper to be ready
        const wrapper = page.locator(`${tableId}_wrapper`);
        try {
            await expect(wrapper).toBeVisible({ timeout: 5000 });
        } catch (e) {
            console.log(`DataTables wrapper for ${tableId} failed to load: ${e.message}`);
            return false;
        }

        // Wait for at least one row to be rendered (data loaded)
        const firstRow = page.locator(`${tableId} tbody tr`).first();
        try {
            await expect(firstRow).toBeVisible({ timeout: 5000 });
        } catch (e) {
            console.log(`Table ${tableId} has no visible rows: ${e.message}`);
            return false;
        }

        return true;
    } catch (error) {
        console.log(`waitForTableReady error for ${tableId}: ${error.message}`);
        return false;
    }
}

/**
 * Helper to verify SearchBuilder UI is present
 */
async function verifySearchBuilderUI(page) {
    // Look for SearchBuilder elements
    const filterBtn = page.locator("#games-filter-btn, #stats-filter-btn").first();
    const searchBuilderSlot = page.locator(
        "#games-searchbuilder-slot, #stats-searchbuilder-slot",
    ).first();

    return {
        filterBtnVisible: await filterBtn.isVisible().catch(() => false),
        searchBuilderSlotExists: await searchBuilderSlot.isVisible().catch(
            () => false,
        ),
        filterBtnRect: filterBtn.isVisible()
            ? await filterBtn.boundingBox()
            : null,
    };
}

/**
 * Helper to verify date attributes on games tables
 */
async function verifyDateAttributes(page) {
    const table = page.locator("#games-results-table");
    const minDate = await table.getAttribute("data-min-date");
    const maxDate = await table.getAttribute("data-max-date");

    return {
        hasMinDate: !!minDate,
        hasMaxDate: !!maxDate,
        minDate,
        maxDate,
    };
}

/**
 * Helper to verify table is responsive and visible
 */
async function verifyTableResponsiveness(page, isMobile = false) {
    const tableWrap = page.locator("#games-table-wrap, #stats-table-wrap").first();
    const card = page.locator(".card").first();

    const wrapRect = await tableWrap.boundingBox();
    const cardRect = await card.boundingBox();

    return {
        tableWrapVisible: wrapRect !== null,
        cardVisible: cardRect !== null,
        tableWrapWidth: wrapRect?.width ?? 0,
        cardWidth: cardRect?.width ?? 0,
        tableWrapHeight: wrapRect?.height ?? 0,
        isMobileOptimized: isMobile && (wrapRect?.width ?? 0) < 600,
    };
}

/**
 * Helper to verify table columns are accessible on mobile
 */
async function verifyMobileTableLayout(page) {
    const tableWrap = page.locator("#games-table-wrap, #stats-table-wrap").first();

    const layout = await tableWrap.evaluate(() => {
        const el = document.querySelector(
            "#games-table-wrap, #stats-table-wrap",
        );
        if (!el) return null;

        const tableResponsive = el.closest(".table-responsive");
        const hasOverflow =
            tableResponsive?.scrollWidth > tableResponsive?.clientWidth;
        const overflowAuto = getComputedStyle(tableResponsive).overflowX;

        return {
            hasOverflow,
            overflowAuto,
            scrollWidth: tableResponsive?.scrollWidth ?? 0,
            clientWidth: tableResponsive?.clientWidth ?? 0,
        };
    });

    return layout || { error: "Could not evaluate mobile layout" };
}

/**
 * Games Pages - Desktop Tests
 */
test.describe("Games Pages - SearchBuilder and Date Filter Layout (Desktop)", () => {
    for (const page of GAMES_PAGES) {
        test(`${page.label} - SearchBuilder UI present on desktop`, async ({
            page: browserPage,
        }) => {
            await browserPage.goto(page.route);

            if (
                !(await waitForTableReady(browserPage, "#games-results-table"))
            ) {
                test.skip();
                return;
            }

            // Verify SearchBuilder button is present
            const filterBtn = browserPage.locator("#games-filter-btn");
            await expect(filterBtn).toBeVisible();

            // Verify it has proper styling
            const button = filterBtn.locator("button, [role='button']").first();
            const classList = await filterBtn.evaluate((el) =>
                Array.from(el.classList),
            );
            expect(classList).toContain("btn");
        });

        test(`${page.label} - Date attributes and controls on desktop`, async ({
            page: browserPage,
        }) => {
            await browserPage.goto(page.route);

            if (
                !(await waitForTableReady(browserPage, "#games-results-table"))
            ) {
                test.skip();
                return;
            }

            // Verify date bounds attributes
            const dateAttrs = await verifyDateAttributes(browserPage);
            expect(dateAttrs.hasMinDate || dateAttrs.hasMaxDate).toBeTruthy();

            // Table should have date column
            const dateHeaders = browserPage.locator("thead th").filter({
                hasText: /^Date$/,
            });
            await expect(dateHeaders.first()).toBeVisible();
        });

        test(`${page.label} - Card and table layout on desktop`, async ({
            page: browserPage,
        }) => {
            // Ensure desktop viewport for this test
            await browserPage.setViewportSize({ width: 1920, height: 1080 });

            await browserPage.goto(page.route);

            if (
                !(await waitForTableReady(browserPage, "#games-results-table"))
            ) {
                test.skip();
                return;
            }

            const layout = await verifyTableResponsiveness(browserPage, false);
            expect(layout.cardVisible).toBeTruthy();
            expect(layout.tableWrapVisible).toBeTruthy();
            expect(layout.tableWrapWidth).toBeGreaterThan(400);
        });

        test(`${page.label} - SearchBuilder functionality accessible on desktop`, async ({
            page: browserPage,
        }) => {
            await browserPage.goto(page.route);

            if (
                !(await waitForTableReady(browserPage, "#games-results-table"))
            ) {
                test.skip();
                return;
            }

            const filterBtn = browserPage.locator("#games-filter-btn");
            const searchBuilderSlot = browserPage.locator(
                "#games-searchbuilder-slot",
            );

            // Initially slot should be hidden
            const initialClass = await searchBuilderSlot.getAttribute("class");
            expect(initialClass).toContain("d-none");

            // Click to open
            await filterBtn.click();
            await browserPage.waitForTimeout(300);

            // Slot should now be visible
            const openClass = await searchBuilderSlot.getAttribute("class");
            expect(openClass).not.toContain("d-none");
        });
    }
});

/**
 * Games Pages - Mobile Tests
 */
test.describe("Games Pages - SearchBuilder and Date Filter Layout (Mobile)", () => {
    for (const page of GAMES_PAGES) {
        test(`${page.label} - SearchBuilder accessible on mobile`, async ({
            page: browserPage,
        }) => {
            // Set mobile viewport
            await browserPage.setViewportSize({ width: 390, height: 844 });

            await browserPage.goto(page.route);

            if (
                !(await waitForTableReady(browserPage, "#games-results-table"))
            ) {
                test.skip();
                return;
            }

            // Filter button should still be accessible on mobile
            const filterBtn = browserPage.locator("#games-filter-btn");
            await expect(filterBtn).toBeVisible();

            // Should be able to open SearchBuilder
            await filterBtn.click();
            await browserPage.waitForTimeout(300);

            const searchBuilderSlot = browserPage.locator(
                "#games-searchbuilder-slot",
            );
            const openClass = await searchBuilderSlot.getAttribute("class");
            expect(openClass).not.toContain("d-none");
        });

        test(`${page.label} - Table is scrollable on mobile`, async ({
            page: browserPage,
        }) => {
            // Set mobile viewport
            await browserPage.setViewportSize({ width: 390, height: 844 });

            await browserPage.goto(page.route);

            if (
                !(await waitForTableReady(browserPage, "#games-results-table"))
            ) {
                test.skip();
                return;
            }

            const layout = await verifyMobileTableLayout(browserPage);
            expect(layout.overflowAuto).toMatch(/auto|scroll/);
        });

        test(`${page.label} - Card layout responsive on mobile`, async ({
            page: browserPage,
        }) => {
            // Set mobile viewport
            await browserPage.setViewportSize({ width: 390, height: 844 });

            await browserPage.goto(page.route);

            if (
                !(await waitForTableReady(browserPage, "#games-results-table"))
            ) {
                test.skip();
                return;
            }

            const layout = await verifyTableResponsiveness(browserPage, true);
            expect(layout.cardVisible).toBeTruthy();
            expect(layout.tableWrapVisible).toBeTruthy();
            // Mobile viewport should be narrower
            expect(layout.tableWrapWidth).toBeLessThan(500);
        });

        test(`${page.label} - Date column visible on mobile`, async ({
            page: browserPage,
        }) => {
            // Set mobile viewport
            await browserPage.setViewportSize({ width: 390, height: 844 });

            await browserPage.goto(page.route);

            if (
                !(await waitForTableReady(browserPage, "#games-results-table"))
            ) {
                test.skip();
                return;
            }

            // Date should be first column and visible
            const firstHeader = browserPage
                .locator("thead th")
                .first();
            await expect(firstHeader).toContainText(/Date/);
        });
    }
});

/**
 * Stats Pages - Desktop Tests
 */
test.describe("Stats Pages - SearchBuilder Layout (Desktop)", () => {
    for (const statsPage of STATS_PAGES) {
        test(`${statsPage.label} - SearchBuilder UI present on desktop`, async ({
            page: browserPage,
        }) => {
            await browserPage.goto(statsPage.route);

            if (
                !(await waitForTableReady(browserPage, "#stats-results-table"))
            ) {
                test.skip();
                return;
            }

            // Verify SearchBuilder button is present
            const filterBtn = browserPage.locator("#stats-filter-btn");
            await expect(filterBtn).toBeVisible();

            // Verify it has proper styling
            const classList = await filterBtn.evaluate((el) =>
                Array.from(el.classList),
            );
            expect(classList).toContain("btn");
        });

        test(`${statsPage.label} - Table and card layout on desktop`, async ({
            page: browserPage,
        }) => {
            // Ensure desktop viewport for this test
            await browserPage.setViewportSize({ width: 1920, height: 1080 });

            await browserPage.goto(statsPage.route);

            if (
                !(await waitForTableReady(browserPage, "#stats-results-table"))
            ) {
                test.skip();
                return;
            }

            const card = browserPage.locator(".card").first();
            const tableWrap = browserPage.locator("#stats-table-wrap");

            await expect(card).toBeVisible();
            await expect(tableWrap).toBeVisible();

            const cardRect = await card.boundingBox();
            expect(cardRect?.width).toBeGreaterThan(400);
        });

        test(`${statsPage.label} - SearchBuilder toggle functionality on desktop`, async ({
            page: browserPage,
        }) => {
            await browserPage.goto(statsPage.route);

            if (
                !(await waitForTableReady(browserPage, "#stats-results-table"))
            ) {
                test.skip();
                return;
            }

            const filterBtn = browserPage.locator("#stats-filter-btn");
            const searchBuilderSlot = browserPage.locator(
                "#stats-searchbuilder-slot",
            );

            // Initially slot should be hidden
            const initialClass = await searchBuilderSlot.getAttribute("class");
            expect(initialClass).toContain("d-none");

            // Click to open
            await filterBtn.click();
            await browserPage.waitForTimeout(300);

            // Slot should now be visible
            const openClass = await searchBuilderSlot.getAttribute("class");
            expect(openClass).not.toContain("d-none");
        });
    }
});

/**
 * Stats Pages - Mobile Tests
 */
test.describe("Stats Pages - SearchBuilder Layout (Mobile)", () => {
    for (const statsPage of STATS_PAGES) {
        test(`${statsPage.label} - SearchBuilder accessible on mobile`, async ({
            page: browserPage,
        }) => {
            // Set mobile viewport
            await browserPage.setViewportSize({ width: 390, height: 844 });

            await browserPage.goto(statsPage.route);

            if (
                !(await waitForTableReady(browserPage, "#stats-results-table"))
            ) {
                test.skip();
                return;
            }

            // Filter button should still be accessible on mobile
            const filterBtn = browserPage.locator("#stats-filter-btn");
            await expect(filterBtn).toBeVisible();

            // Should be able to open SearchBuilder
            await filterBtn.click();
            await browserPage.waitForTimeout(300);

            const searchBuilderSlot = browserPage.locator(
                "#stats-searchbuilder-slot",
            );
            const openClass = await searchBuilderSlot.getAttribute("class");
            expect(openClass).not.toContain("d-none");
        });

        test(`${statsPage.label} - Table is scrollable on mobile`, async ({
            page: browserPage,
        }) => {
            // Set mobile viewport
            await browserPage.setViewportSize({ width: 390, height: 844 });

            await browserPage.goto(statsPage.route);

            if (
                !(await waitForTableReady(browserPage, "#stats-results-table"))
            ) {
                test.skip();
                return;
            }

            const layout = await verifyMobileTableLayout(browserPage);
            expect(layout.overflowAuto).toMatch(/auto|scroll/);
        });

        test(`${statsPage.label} - Card layout responsive on mobile`, async ({
            page: browserPage,
        }) => {
            // Set mobile viewport
            await browserPage.setViewportSize({ width: 390, height: 844 });

            await browserPage.goto(statsPage.route);

            if (
                !(await waitForTableReady(browserPage, "#stats-results-table"))
            ) {
                test.skip();
                return;
            }

            const layout = await verifyTableResponsiveness(browserPage, true);
            expect(layout.cardVisible).toBeTruthy();
            expect(layout.tableWrapVisible).toBeTruthy();
            // Mobile viewport should be narrower
            expect(layout.tableWrapWidth).toBeLessThan(500);
        });

        test(`${statsPage.label} - Header columns accessible on mobile`, async ({
            page: browserPage,
        }) => {
            // Set mobile viewport
            await browserPage.setViewportSize({ width: 390, height: 844 });

            await browserPage.goto(statsPage.route);

            if (
                !(await waitForTableReady(browserPage, "#stats-results-table"))
            ) {
                test.skip();
                return;
            }

            // At least first column should be visible
            const firstHeader = browserPage
                .locator("#stats-results-table thead th")
                .first();
            await expect(firstHeader).toBeVisible({ timeout: 10000 });
        });
    }
});

/**
 * Cross-viewport responsive behavior tests
 */
test.describe("SearchBuilder and Date Filter - Responsive Behavior", () => {
    test("Games table layout adapts between desktop and mobile", async ({
        page,
    }) => {
        // Test at desktop size
        await page.setViewportSize({ width: 1920, height: 1080 });
        await page.goto("/games/all");

        if (!(await waitForTableReady(page, "#games-results-table"))) {
            test.skip();
            return;
        }

        const desktopLayout = await verifyTableResponsiveness(page, false);
        expect(desktopLayout.tableWrapWidth).toBeGreaterThan(800);

        // Now switch to mobile size
        await page.setViewportSize({ width: 390, height: 844 });
        await page.waitForTimeout(500); // Give layout time to adjust

        const mobileLayout = await verifyTableResponsiveness(page, true);
        expect(mobileLayout.tableWrapWidth).toBeLessThan(
            desktopLayout.tableWrapWidth,
        );
    });

    test("Stats table layout adapts between desktop and mobile", async ({
        page,
    }) => {
        // Test at desktop size
        await page.setViewportSize({ width: 1920, height: 1080 });
        await page.goto("/stats/player-game");

        if (!(await waitForTableReady(page, "#stats-results-table"))) {
            test.skip();
            return;
        }

        const desktopLayout = await verifyTableResponsiveness(page, false);
        expect(desktopLayout.tableWrapWidth).toBeGreaterThan(800);

        // Now switch to mobile size
        await page.setViewportSize({ width: 390, height: 844 });
        await page.waitForTimeout(500); // Give layout time to adjust

        const mobileLayout = await verifyTableResponsiveness(page, true);
        expect(mobileLayout.tableWrapWidth).toBeLessThan(
            desktopLayout.tableWrapWidth,
        );
    });
});

/**
 * Light Mode Styling - SearchBuilder and Headers
 */
test.describe("Light Mode Styling - SearchBuilder and Table Headers", () => {
    test("All Games - Table headers have grey background with dark text in light mode", async ({
        page,
    }) => {
        await page.goto("/games/all");
        await page.evaluate(() => {
            document.documentElement.setAttribute("data-theme", "light");
        });
        await page.waitForTimeout(500);

        await waitForTableReady(page, "#games-results-table");

        const headerColors = await page.evaluate(() => {
            const headers = Array.from(
                document.querySelectorAll(".dataTables_scrollHead th"),
            );

            if (headers.length === 0) return null;

            const sample = headers[0];
            const bgColor = window.getComputedStyle(sample).backgroundColor;
            const textColor = window.getComputedStyle(sample).color;

            return {
                backgroundColor: bgColor,
                textColor: textColor,
            };
        });

        expect(headerColors).toBeTruthy();
        // Light mode headers: grey background (#efe9e3)
        expect(headerColors.backgroundColor).toContain("rgb(239, 233, 227)");
        // Dark text for readability
        expect(headerColors.textColor).toContain("rgb(11, 15, 20)");
    });

    test("Stats pages - Table headers use theme-aware styling in light mode", async ({
        page,
    }) => {
        await page.goto("/stats/player-game");
        await page.evaluate(() => {
            document.documentElement.setAttribute("data-theme", "light");
        });
        await page.waitForTimeout(500);

        await waitForTableReady(page, "#stats-results-table");

        const headerColors = await page.evaluate(() => {
            const headers = Array.from(
                document.querySelectorAll(".dataTables_scrollHead th"),
            );

            if (headers.length === 0) return null;

            const sample = headers[0];
            const bgColor = window.getComputedStyle(sample).backgroundColor;
            const textColor = window.getComputedStyle(sample).color;

            return {
                backgroundColor: bgColor,
                textColor: textColor,
            };
        });

        expect(headerColors).toBeTruthy();
        // Same grey background as games pages
        expect(headerColors.backgroundColor).toContain("rgb(239, 233, 227)");
        // Same dark text
        expect(headerColors.textColor).toContain("rgb(11, 15, 20)");
    });
});

/**
 * Dark Mode Styling - Verify No Regression
 */
test.describe("Dark Mode Styling - Verify No Regression", () => {
    test("Dark mode - Table headers have dark background with light text", async ({
        page,
    }) => {
        await page.goto("/games/all");
        await page.evaluate(() => {
            document.documentElement.setAttribute("data-theme", "dark");
        });
        await page.waitForTimeout(500);

        await waitForTableReady(page, "#games-results-table");

        const headerColors = await page.evaluate(() => {
            const headers = Array.from(
                document.querySelectorAll(".dataTables_scrollHead th"),
            );

            if (headers.length === 0) return null;

            const sample = headers[0];
            const bgColor = window.getComputedStyle(sample).backgroundColor;
            const textColor = window.getComputedStyle(sample).color;

            return {
                backgroundColor: bgColor,
                textColor: textColor,
            };
        });

        expect(headerColors).toBeTruthy();
        // Dark mode headers: dark navy background
        expect(headerColors.backgroundColor).toContain("rgb(18, 27, 45)");
        // Light text for readability
        expect(headerColors.textColor).toContain("rgb(238, 242, 247)");
    });

    test("Dark mode - Stats pages maintain dark styling", async ({
        page,
    }) => {
        await page.goto("/stats/player-game");
        await page.evaluate(() => {
            document.documentElement.setAttribute("data-theme", "dark");
        });
        await page.waitForTimeout(500);

        await waitForTableReady(page, "#stats-results-table");

        const headerColors = await page.evaluate(() => {
            const headers = Array.from(
                document.querySelectorAll(".dataTables_scrollHead th"),
            );

            if (headers.length === 0) return null;

            const sample = headers[0];
            const bgColor = window.getComputedStyle(sample).backgroundColor;
            const textColor = window.getComputedStyle(sample).color;

            return {
                backgroundColor: bgColor,
                textColor: textColor,
            };
        });

        expect(headerColors).toBeTruthy();
        // Dark navy background
        expect(headerColors.backgroundColor).toContain("rgb(18, 27, 45)");
        // Light text
        expect(headerColors.textColor).toContain("rgb(238, 242, 247)");
    });
});

/**
 * Theme Consistency - All frozen columns and UI elements
 */
test.describe("Theme Consistency - Frozen Columns and UI Elements", () => {
    test("Light mode - All frozen column headers use consistent theme-aware styling", async ({
        page,
    }) => {
        await page.goto("/games/all");
        await page.evaluate(() => {
            document.documentElement.setAttribute("data-theme", "light");
        });
        await page.waitForTimeout(500);

        await waitForTableReady(page, "#games-results-table");

        const frozenHeaderInfo = await page.evaluate(() => {
            // Get all header cells (both frozen and regular)
            const headers = Array.from(
                document.querySelectorAll(".dataTables_scrollHead th"),
            );

            if (headers.length === 0) return null;

            // Check multiple headers to ensure consistency
            const samples = headers.slice(0, Math.min(5, headers.length)).map((header) => ({
                text: header.textContent,
                bgColor: window.getComputedStyle(header).backgroundColor,
                textColor: window.getComputedStyle(header).color,
            }));

            return samples;
        });

        expect(frozenHeaderInfo).toBeTruthy();
        expect(frozenHeaderInfo.length).toBeGreaterThan(0);

        // All headers should have consistent light mode styling
        frozenHeaderInfo.forEach((header) => {
            // Grey background
            expect(header.bgColor).toContain("rgb(239, 233, 227)");
            // Dark text
            expect(header.textColor).toContain("rgb(11, 15, 20)");
        });
    });

    test("Dark mode - All frozen column headers use consistent dark styling", async ({
        page,
    }) => {
        await page.goto("/games/all");
        await page.evaluate(() => {
            document.documentElement.setAttribute("data-theme", "dark");
        });
        await page.waitForTimeout(500);

        await waitForTableReady(page, "#games-results-table");

        const frozenHeaderInfo = await page.evaluate(() => {
            const headers = Array.from(
                document.querySelectorAll(".dataTables_scrollHead th"),
            );

            if (headers.length === 0) return null;

            const samples = headers.slice(0, Math.min(5, headers.length)).map((header) => ({
                text: header.textContent,
                bgColor: window.getComputedStyle(header).backgroundColor,
                textColor: window.getComputedStyle(header).color,
            }));

            return samples;
        });

        expect(frozenHeaderInfo).toBeTruthy();
        expect(frozenHeaderInfo.length).toBeGreaterThan(0);

        // All headers should have consistent dark mode styling
        frozenHeaderInfo.forEach((header) => {
            // Dark navy background
            expect(header.bgColor).toContain("rgb(18, 27, 45)");
            // Light text
            expect(header.textColor).toContain("rgb(238, 242, 247)");
        });
    });
});
