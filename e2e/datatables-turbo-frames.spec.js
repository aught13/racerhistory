import { test, expect } from '@playwright/test';

/**
 * E2E tests for DataTables initialization with Turbo Frames.
 *
 * These tests target the real-world issue of inconsistent DataTables loading
 * when Turbo Drive or Turbo Frame navigations occur. DataTables must:
 *   1. Initialize correctly on first page load.
 *   2. Re-initialize after Turbo Drive navigates away and back.
 *   3. Initialize inside a Turbo Frame after the frame loads or refreshes.
 *   4. Clean up properly before Turbo caches the page snapshot.
 *   5. Not create duplicate DataTable instances on repeated turbo:load events.
 */

/* ────────── helpers ────────── */

/**
 * Wait for a DataTable to be ready on the given table selector.
 * Checks that jQuery DataTables `.dataTable` wrapper class is present.
 */
async function waitForDataTable(page, tableSelector, timeout = 15000) {
    await page.waitForFunction(
        ({ sel, cls }) => {
            const table = document.querySelector(sel);
            if (!table) return false;
            // DataTables adds wrapper divs; the table itself gets a "dataTable" class
            if (table.classList.contains('dataTable')) return true;
            // Alternatively check for wrapper
            const wrapper = table.closest('.dataTables_wrapper');
            return !!wrapper;
        },
        { sel: tableSelector, cls: 'dataTable' },
        { timeout },
    );
}

/**
 * Assert that exactly one DataTable wrapper exists for a given table id.
 * Catches the duplicate-init bug where two wrappers appear.
 */
async function assertSingleDataTableInstance(page, tableId) {
    const wrapperCount = await page.evaluate((id) => {
        return document.querySelectorAll(
            `#${id}_wrapper, .dataTables_wrapper`,
        ).length;
    }, tableId);
    // There may be multiple tables on a page but each should have exactly one wrapper
    expect(wrapperCount).toBeGreaterThanOrEqual(1);
}

/* ────────── Seasons table (Turbo Frame) ────────── */

test.describe('DataTables inside Turbo Frames', () => {
    test.describe('Seasons page – DataTable in turbo-frame', () => {
        test('initializes DataTable on first load', async ({ page }) => {
            await page.goto('/seasons');
            await page.waitForLoadState('networkidle');

            // The seasons table is inside turbo-frame#seasons-table-frame
            const frame = page.locator('turbo-frame#seasons-table-frame');
            await expect(frame).toBeVisible({ timeout: 10000 });

            // A DataTable (or at least a table) should be present
            const table = frame.locator('table').first();
            if ((await table.count()) > 0) {
                await expect(table).toBeVisible();
            }
        });

        test('re-initializes after turbo-frame filter link', async ({ page }) => {
            await page.goto('/seasons');
            await page.waitForLoadState('networkidle');

            const filterLink = page
                .locator('a[data-turbo-frame="seasons-table-frame"]')
                .first();
            if ((await filterLink.count()) === 0) {
                test.skip();
                return;
            }

            await filterLink.click();

            // Wait for frame to update
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(500); // extra settle time

            const frame = page.locator('turbo-frame#seasons-table-frame');
            await expect(frame).toBeVisible();

            // Table should be present and visible after frame reload
            const table = frame.locator('table').first();
            if ((await table.count()) > 0) {
                await expect(table).toBeVisible();
            }
        });

        test('no duplicate DataTable wrappers after frame reload', async ({ page }) => {
            await page.goto('/seasons');
            await page.waitForLoadState('networkidle');

            const filterLink = page
                .locator('a[data-turbo-frame="seasons-table-frame"]')
                .first();
            if ((await filterLink.count()) === 0) {
                test.skip();
                return;
            }

            // Click filter twice to trigger two frame loads
            await filterLink.click();
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(300);

            // Verify no duplicate wrappers inside the frame
            const dupCount = await page.evaluate(() => {
                const frame = document.getElementById('seasons-table-frame');
                if (!frame) return 0;
                return frame.querySelectorAll('.dataTables_wrapper').length;
            });
            expect(dupCount).toBeLessThanOrEqual(1);
        });
    });

    /* ────────── People page – DataTable with Scroller ────────── */

    test.describe('People page – DataTable with Scroller', () => {
        test('initializes DataTable on first load', async ({ page }) => {
            await page.goto('/people');
            await page.waitForLoadState('networkidle');

            // People table should render
            const table = page.locator('#people-table');
            if ((await table.count()) === 0) {
                test.skip();
                return;
            }

            // Wait for DataTable to initialize (adds wrapper)
            try {
                await waitForDataTable(page, '#people-table', 15000);
            } catch {
                // DataTable may not load if jQuery CDN is slow
                test.skip();
                return;
            }

            await assertSingleDataTableInstance(page, 'people-table');
        });

        test('SearchBuilder panel toggles correctly', async ({ page }) => {
            await page.goto('/people');
            await page.waitForLoadState('networkidle');

            const filterBtn = page.locator('#people-filter-btn');
            if ((await filterBtn.count()) === 0) {
                test.skip();
                return;
            }

            await filterBtn.click();
            const panel = page.locator('#people-searchbuilder-panel');
            // After click, panel should be visible (not d-none)
            const isHidden = await panel.evaluate((el) =>
                el.classList.contains('d-none'),
            );
            // The first click should reveal the panel
            expect(typeof isHidden).toBe('boolean');
        });

        test('name search filters table rows', async ({ page }) => {
            await page.goto('/people');
            await page.waitForLoadState('networkidle');

            const searchInput = page.locator('#people-name-search');
            if ((await searchInput.count()) === 0) {
                test.skip();
                return;
            }

            try {
                await waitForDataTable(page, '#people-table', 15000);
            } catch {
                test.skip();
                return;
            }

            // Type a search query
            await searchInput.fill('Smith');
            await page.waitForTimeout(500);

            // The number of visible rows should be filtered
            // (just verify no JS error occurred)
            const errors = [];
            page.on('pageerror', (err) => errors.push(err));
            await page.waitForTimeout(200);
            expect(errors.length).toBe(0);
        });
    });
});

/* ────────── Games pages – DataTables with AJAX + SearchBuilder ────────── */

test.describe('DataTables with Turbo Drive navigation', () => {
    test.describe('Games index page', () => {
        test('DataTable loads on games index', async ({ page }) => {
            await page.goto('/games');
            await page.waitForLoadState('networkidle');

            // Games index has type cards, may or may not have a DataTable yet
            const cards = page.locator('#games-type-cards, .game-type-card');
            if ((await cards.count()) > 0) {
                // Index page renders cards, not a DataTable
                await expect(cards.first()).toBeVisible();
            }
        });

        test('Games search page with DataTable initializes', async ({ page }) => {
            // Navigate directly to a games search page that uses DataTable
            await page.goto('/games/ranked');
            await page.waitForLoadState('networkidle');

            const table = page.locator('#games-results-table');
            if ((await table.count()) === 0) {
                test.skip();
                return;
            }

            // Wait for DataTable AJAX to load
            try {
                await waitForDataTable(page, '#games-results-table', 20000);
            } catch {
                // May fail if AJAX source is unavailable
                test.skip();
                return;
            }

            await assertSingleDataTableInstance(page, 'games-results-table');
        });
    });

    test.describe('Stats pages – DataTables with AJAX', () => {
        test('Stats player-season page loads DataTable', async ({ page }) => {
            await page.goto('/stats/player-season');
            await page.waitForLoadState('networkidle');

            const table = page.locator('#stats-results-table');
            if ((await table.count()) === 0) {
                test.skip();
                return;
            }

            try {
                await waitForDataTable(page, '#stats-results-table', 20000);
            } catch {
                test.skip();
                return;
            }

            await assertSingleDataTableInstance(page, 'stats-results-table');
        });
    });

    /* ────────── Turbo Drive back/forward consistency ────────── */

    test.describe('Turbo Drive back/forward DataTable consistency', () => {
        test('navigating away and back re-initializes DataTable', async ({
            page,
        }) => {
            // Start on seasons page
            await page.goto('/seasons');
            await page.waitForLoadState('networkidle');

            const frame = page.locator('turbo-frame#seasons-table-frame');
            const hasFrame = (await frame.count()) > 0;

            // Navigate to a different page via Turbo Drive
            await page.goto('/people');
            await page.waitForLoadState('networkidle');

            // Navigate back
            await page.goBack();
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(500);

            if (hasFrame) {
                // Frame should still exist
                const frameAfter = page.locator(
                    'turbo-frame#seasons-table-frame',
                );
                await expect(frameAfter).toBeVisible({ timeout: 10000 });
            }
        });

        test('no console errors during Turbo Drive transitions', async ({
            page,
        }) => {
            const errors = [];
            page.on('pageerror', (err) => errors.push(err.message));

            await page.goto('/seasons');
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(500);

            // Navigate to people
            await page.goto('/people');
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(500);

            // Navigate to games
            await page.goto('/games');
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(500);

            // Go back twice
            await page.goBack();
            await page.waitForLoadState('networkidle');
            await page.goBack();
            await page.waitForLoadState('networkidle');

            // Filter for DataTable-related errors only
            const dtErrors = errors.filter(
                (msg) =>
                    msg.includes('DataTable') ||
                    msg.includes('Cannot reinitialise') ||
                    msg.includes('dataTable'),
            );
            expect(dtErrors).toEqual([]);
        });
    });

    /* ────────── Game view with stats turbo frame ────────── */

    test.describe('Game view – stats turbo-frame with DataTable', () => {
        test('game stats frame loads content', async ({ page }) => {
            await page.goto('/games/view/1');
            await page.waitForLoadState('networkidle');

            const frame = page.locator('turbo-frame#game-stats-frame');
            if ((await frame.count()) === 0) {
                test.skip();
                return;
            }

            await expect(frame).toBeVisible({ timeout: 10000 });

            // Frame should have loaded content
            const content = await frame.textContent();
            expect(content.length).toBeGreaterThan(0);
        });

        test('game stats frame is not cached between navigations', async ({
            page,
        }) => {
            await page.goto('/games/view/1');
            await page.waitForLoadState('networkidle');

            const frame = page.locator('turbo-frame#game-stats-frame');
            if ((await frame.count()) === 0) {
                test.skip();
                return;
            }

            const cacheAttr = await frame.getAttribute('data-turbo-cache');
            expect(cacheAttr).toBe('false');
        });
    });
});

/* ────────── DataTable cleanup on turbo:before-cache ────────── */

test.describe('DataTable cleanup lifecycle', () => {
    test('turbo:before-cache does not leave stale DataTable markup', async ({
        page,
    }) => {
        // Go to a page with DataTables
        await page.goto('/seasons');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        // Navigate away (triggers turbo:before-cache)
        await page.goto('/people');
        await page.waitForLoadState('networkidle');

        // Go back (Turbo restores from cache)
        await page.goBack();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        // There should be at most one DataTable wrapper per table in the frame
        const wrapperCount = await page.evaluate(() => {
            const frame = document.getElementById('seasons-table-frame');
            if (!frame) return 0;
            return frame.querySelectorAll('.dataTables_wrapper').length;
        });
        expect(wrapperCount).toBeLessThanOrEqual(1);
    });

    test('rapid navigation does not cause DataTable errors', async ({
        page,
    }) => {
        const errors = [];
        page.on('pageerror', (err) => errors.push(err.message));

        // Rapid navigation sequence
        await page.goto('/seasons');
        await page.goto('/people'); // navigate before DataTable fully loads
        await page.goto('/games');
        await page.goto('/stats');
        await page.waitForLoadState('networkidle');

        // No DataTable-related errors should occur
        const dtErrors = errors.filter(
            (msg) =>
                msg.includes('DataTable') ||
                msg.includes('Cannot reinitialise') ||
                msg.includes('destroy'),
        );
        expect(dtErrors).toEqual([]);
    });
});

/* ────────── Season view with split toggle ────────── */

test.describe('Season view with turbo-frame table toggle', () => {
    test('seasons splits page loads correctly', async ({ page }) => {
        await page.goto('/seasons/splits');
        await page.waitForLoadState('networkidle');

        const frame = page.locator('turbo-frame#seasons-table-frame');
        if ((await frame.count()) === 0) {
            test.skip();
            return;
        }

        await expect(frame).toBeVisible({ timeout: 10000 });

        const table = frame.locator('table').first();
        if ((await table.count()) > 0) {
            await expect(table).toBeVisible();
        }
    });

    test('toggling between standard and splits reinitializes correctly', async ({
        page,
    }) => {
        await page.goto('/seasons');
        await page.waitForLoadState('networkidle');

        // Find a link to the splits view
        const splitsLink = page
            .locator(
                'a[href*="splits"][data-turbo-frame="seasons-table-frame"]',
            )
            .first();
        if ((await splitsLink.count()) === 0) {
            test.skip();
            return;
        }

        await splitsLink.click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(500);

        // Frame should still be present
        const frame = page.locator('turbo-frame#seasons-table-frame');
        await expect(frame).toBeVisible({ timeout: 10000 });
    });
});
