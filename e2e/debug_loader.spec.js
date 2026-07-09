import { test } from '@playwright/test';
import { loginToAdmin } from './support/auth.js';

test('debug legacy loader for stat multi-add', async ({ page }) => {
    page.on('console', (msg) => console.log('PAGE LOG', msg.type(), msg.text()));

    const loggedIn = await loginToAdmin(page);
    test.skip(!loggedIn, 'Could not log in to the e2e admin account');

    await page.goto('/admin/stat-basket-game-person/add/1', { waitUntil: 'networkidle' });

    const dbgBefore = await page.evaluate(() => {
        return {
            runtimeBooted: window.__RH_RUNTIME_BOOTED__,
            loaderDebug: window.__RH_LOADER_DEBUG__ || null,
            statReady: window.__RH_STAT_MULTI_ADD_READY || null,
            addBtnAttr: (function () {
                const b = document.getElementById('add-row-btn');
                return b ? b.dataset.rhReady || null : null;
            })(),
        };
    });
    console.log('LOADER DEBUG BEFORE:', JSON.stringify(dbgBefore));

    // click the add button (this should trigger interaction load if deferred)
    await page.click('#add-row-btn');

    // wait a bit for module to load and handlers to run
    await page.waitForTimeout(1200);

    const rowsCount = await page.locator('.stat-row').count();
    console.log('ROWS AFTER CLICK:', rowsCount);

    const dbgAfter = await page.evaluate(() => {
        return window.__RH_LOADER_DEBUG__ || null;
    });
    const readyAfter = await page.evaluate(() => ({
        statReady: window.__RH_STAT_MULTI_ADD_READY || null,
        addBtnAttr: (function () {
            const b = document.getElementById('add-row-btn');
            return b ? b.dataset.rhReady || null : null;
        })(),
    }));
    console.log('READY AFTER:', JSON.stringify(readyAfter));
    console.log('LOADER DEBUG AFTER:', JSON.stringify(dbgAfter));
});
