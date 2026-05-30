import { test, expect } from "@playwright/test";
import { loginToAdmin } from "./support/auth.js";

/**
 * E2E tests for the stat multi-add forms.
 *
 * These tests verify the dynamic multi-row form behaviour of:
 *   - /admin/stat-basket-game-person/add/:gameId
 *   - /admin/stat-basket-game-opponent/add/:gameId
 *
 * Including:
 *   - Multi-row form structure
 *   - "Add Another" button creating new rows
 *   - Remove button functionality
 *   - Turbo Frame wrapping
 *   - "Add to Season Totals" checkbox (player only)
 *
 * NOTE: These tests require admin authentication and a running server.
 */

/* ────────── Player Stat (Person) tests ────────── */

test.describe("Stat Multi-Add: Player (Person)", () => {
    test.beforeEach(async ({ page }) => {
        const loggedIn = await loginToAdmin(page);
        test.skip(!loggedIn, "Could not log in to the e2e admin account");
    });

    test("form loads with turbo-frame and initial stat row", async ({
        page,
    }) => {
        await page.goto("/admin/stat-basket-game-person/add/1");
        await page.waitForLoadState("networkidle");

        const frame = page.locator("turbo-frame#stat-person-add-frame");
        await expect(frame).toBeVisible();

        const rows = page.locator(".stat-row");
        await expect(rows).toHaveCount(1);

        const addBtn = page.locator("#add-row-btn");
        await expect(addBtn).toBeVisible();
        await expect(addBtn).toContainText("Add Another");

        const saveBtn = page.locator("#save-all-btn");
        await expect(saveBtn).toBeVisible();
    });

    test("form has player select, stat fields", async ({ page }) => {
        await page.goto("/admin/stat-basket-game-person/add/1");
        await page.waitForLoadState("networkidle");

        const row = page.locator(".stat-row").first();

        // Player select
        await expect(row.locator(".stat-player-select")).toBeVisible();

        // Stat fields
        await expect(row.locator('input[name="rows[0][PTS]"]')).toBeVisible();
        await expect(row.locator('input[name="rows[0][MIN]"]')).toBeVisible();
        await expect(row.locator('input[name="rows[0][FGM]"]')).toBeVisible();
    });

    test("Add Another button creates a new row", async ({ page }) => {
        await page.goto("/admin/stat-basket-game-person/add/1");
        await page.waitForLoadState("networkidle");

        await expect(page.locator(".stat-row")).toHaveCount(1);

        await page.click("#add-row-btn");

        await expect(page.locator(".stat-row")).toHaveCount(2);

        // Second row should have index 1
        const secondSelect = page.locator(
            'select[name="rows[1][team_season_roster_id]"]',
        );
        await expect(secondSelect).toBeAttached();
    });

    test("adding multiple rows creates correct count", async ({ page }) => {
        await page.goto("/admin/stat-basket-game-person/add/1");
        await page.waitForLoadState("networkidle");

        await page.click("#add-row-btn");
        await page.click("#add-row-btn");
        await page.click("#add-row-btn");

        await expect(page.locator(".stat-row")).toHaveCount(4);
    });

    test("remove button is disabled when only one row exists", async ({
        page,
    }) => {
        await page.goto("/admin/stat-basket-game-person/add/1");
        await page.waitForLoadState("networkidle");

        const removeBtn = page.locator(".remove-row-btn").first();
        await expect(removeBtn).toBeDisabled();
    });

    test("remove button becomes enabled with multiple rows", async ({
        page,
    }) => {
        await page.goto("/admin/stat-basket-game-person/add/1");
        await page.waitForLoadState("networkidle");

        await page.click("#add-row-btn");

        const removeBtns = page.locator(".remove-row-btn");
        await expect(removeBtns.first()).toBeEnabled();
        await expect(removeBtns.last()).toBeEnabled();
    });

    test("removing a row updates count and re-indexes", async ({ page }) => {
        await page.goto("/admin/stat-basket-game-person/add/1");
        await page.waitForLoadState("networkidle");

        await page.click("#add-row-btn");
        await page.click("#add-row-btn");
        await expect(page.locator(".stat-row")).toHaveCount(3);

        // Remove the second row
        await page
            .locator(".stat-row")
            .nth(1)
            .locator(".remove-row-btn")
            .click();

        await expect(page.locator(".stat-row")).toHaveCount(2);

        // Last row should be re-indexed to 1
        const lastPTS = page
            .locator(".stat-row")
            .last()
            .locator('input[name="rows[1][PTS]"]');
        await expect(lastPTS).toBeAttached();
    });

    test("Add to Season Totals checkbox is present", async ({ page }) => {
        await page.goto("/admin/stat-basket-game-person/add/1");
        await page.waitForLoadState("networkidle");

        const checkbox = page.locator("#add-to-totals-checkbox");
        await expect(checkbox).toBeAttached();
        await expect(checkbox).not.toBeChecked();
    });

    test("row labels update correctly", async ({ page }) => {
        await page.goto("/admin/stat-basket-game-person/add/1");
        await page.waitForLoadState("networkidle");

        await page.click("#add-row-btn");

        const labels = page.locator(".stat-row-label");
        await expect(labels.first()).toHaveText("Player #1");
        await expect(labels.last()).toHaveText("Player #2");
    });
});

/* ────────── Opponent Stat tests ────────── */

test.describe("Stat Multi-Add: Opponent", () => {
    test.beforeEach(async ({ page }) => {
        const loggedIn = await loginToAdmin(page);
        test.skip(!loggedIn, "Could not log in to the e2e admin account");
    });

    test("form loads with turbo-frame and initial stat row", async ({
        page,
    }) => {
        await page.goto("/admin/stat-basket-game-opponent/add/1");
        await page.waitForLoadState("networkidle");

        const frame = page.locator("turbo-frame#stat-opponent-add-frame");
        await expect(frame).toBeVisible();

        const rows = page.locator(".stat-row");
        await expect(rows).toHaveCount(1);

        await expect(page.locator("#add-row-btn")).toBeVisible();
        await expect(page.locator("#save-all-btn")).toBeVisible();
    });

    test("form has name, jersey, position fields", async ({ page }) => {
        await page.goto("/admin/stat-basket-game-opponent/add/1");
        await page.waitForLoadState("networkidle");

        const row = page.locator(".stat-row").first();

        await expect(row.locator(".stat-opp-name")).toBeVisible();
        await expect(
            row.locator('input[name="rows[0][jersey]"]'),
        ).toBeVisible();
        await expect(
            row.locator('input[name="rows[0][position]"]'),
        ).toBeVisible();
        await expect(row.locator('input[name="rows[0][PTS]"]')).toBeVisible();
    });

    test("Add Another button creates a new opponent row", async ({ page }) => {
        await page.goto("/admin/stat-basket-game-opponent/add/1");
        await page.waitForLoadState("networkidle");

        await expect(page.locator(".stat-row")).toHaveCount(1);

        await page.click("#add-row-btn");

        await expect(page.locator(".stat-row")).toHaveCount(2);

        const secondName = page.locator('input[name="rows[1][name]"]');
        await expect(secondName).toBeAttached();
    });

    test("remove button is disabled when only one row exists", async ({
        page,
    }) => {
        await page.goto("/admin/stat-basket-game-opponent/add/1");
        await page.waitForLoadState("networkidle");

        const removeBtn = page.locator(".remove-row-btn").first();
        await expect(removeBtn).toBeDisabled();
    });

    test("removing a row re-indexes opponent rows", async ({ page }) => {
        await page.goto("/admin/stat-basket-game-opponent/add/1");
        await page.waitForLoadState("networkidle");

        await page.click("#add-row-btn");
        await page.click("#add-row-btn");
        await expect(page.locator(".stat-row")).toHaveCount(3);

        await page
            .locator(".stat-row")
            .nth(1)
            .locator(".remove-row-btn")
            .click();

        await expect(page.locator(".stat-row")).toHaveCount(2);

        const lastName = page
            .locator(".stat-row")
            .last()
            .locator('input[name="rows[1][name]"]');
        await expect(lastName).toBeAttached();
    });

    test("no Add to Season Totals checkbox for opponent", async ({ page }) => {
        await page.goto("/admin/stat-basket-game-opponent/add/1");
        await page.waitForLoadState("networkidle");

        const checkbox = page.locator("#add-to-totals-checkbox");
        await expect(checkbox).toHaveCount(0);
    });
});
