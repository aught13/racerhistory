import { test, expect } from "@playwright/test";

/**
 * E2E tests for the roster multi-add form.
 *
 * These tests verify the dynamic form loading behaviour of the
 * /admin/team-season-rosters/add page, including:
 *   - Multi-row add form structure
 *   - "Add Another" button creating new rows
 *   - Remove button functionality
 *   - Turbo Frame wrapping
 *
 * NOTE: These tests require admin authentication. They use a login helper
 * that POSTs credentials. If the server is not running or login fails,
 * tests will be skipped gracefully.
 */

/* ────────── helpers ────────── */

/**
 * Attempt to log in as admin. Returns true on success.
 * Uses the app's CakeDC/Users login form.
 */
async function loginAsAdmin(page) {
  try {
    await page.goto("/login", { waitUntil: "networkidle", timeout: 5000 });

    // Fill login form
    await page.fill('input[name="username"]', "admin");
    await page.fill('input[name="password"]', "admin");
    await page.click('button[type="submit"]');

    // Wait for redirect after login
    await page.waitForURL((url) => !url.pathname.includes("login"), {
      timeout: 5000,
    });
    return true;
  } catch {
    return false;
  }
}

/* ────────── tests ────────── */

test.describe("Roster Multi-Add Form", () => {
  test.beforeEach(async ({ page }) => {
    const loggedIn = await loginAsAdmin(page);
    test.skip(!loggedIn, "Could not log in — server may not be running");
  });

  test("form loads with turbo-frame and initial roster row", async ({
    page,
  }) => {
    await page.goto("/admin/team-season-rosters/add?team_season_id=1");
    await page.waitForLoadState("networkidle");

    // Turbo frame should be present
    const frame = page.locator("turbo-frame#roster-add-frame");
    await expect(frame).toBeVisible();

    // Initial roster row should exist
    const rows = page.locator(".roster-row");
    await expect(rows).toHaveCount(1);

    // Add Another button should be visible
    const addBtn = page.locator("#add-row-btn");
    await expect(addBtn).toBeVisible();
    await expect(addBtn).toContainText("Add Another");

    // Save All button should be visible
    const saveBtn = page.locator("#save-all-btn");
    await expect(saveBtn).toBeVisible();
  });

  test("form has person search, number, position, height, weight fields", async ({
    page,
  }) => {
    await page.goto("/admin/team-season-rosters/add?team_season_id=1");
    await page.waitForLoadState("networkidle");

    const row = page.locator(".roster-row").first();

    // Person AJAX search input
    await expect(row.locator(".roster-person-search")).toBeVisible();
    // Hidden person_id input
    await expect(row.locator(".roster-person-id")).toBeAttached();

    // Other fields
    await expect(
      row.locator('input[name="rows[0][roster_number]"]'),
    ).toBeVisible();
    await expect(
      row.locator('input[name="rows[0][roster_position]"]'),
    ).toBeVisible();
    await expect(
      row.locator('input[name="rows[0][roster_height]"]'),
    ).toBeVisible();
    await expect(
      row.locator('input[name="rows[0][roster_weight]"]'),
    ).toBeVisible();
  });

  test("Add Another button creates a new row", async ({ page }) => {
    await page.goto("/admin/team-season-rosters/add?team_season_id=1");
    await page.waitForLoadState("networkidle");

    // Start with 1 row
    await expect(page.locator(".roster-row")).toHaveCount(1);

    // Click Add Another
    await page.click("#add-row-btn");

    // Should now have 2 rows
    await expect(page.locator(".roster-row")).toHaveCount(2);

    // Second row should have index 1 in field names
    const secondHidden = page.locator(
      'input[name="rows[1][person_id]"]',
    );
    await expect(secondHidden).toBeAttached();
  });

  test("adding multiple rows creates correct count", async ({ page }) => {
    await page.goto("/admin/team-season-rosters/add?team_season_id=1");
    await page.waitForLoadState("networkidle");

    await page.click("#add-row-btn");
    await page.click("#add-row-btn");
    await page.click("#add-row-btn");

    await expect(page.locator(".roster-row")).toHaveCount(4);
  });

  test("remove button is disabled when only one row exists", async ({
    page,
  }) => {
    await page.goto("/admin/team-season-rosters/add?team_season_id=1");
    await page.waitForLoadState("networkidle");

    const removeBtn = page.locator(".remove-row-btn").first();
    await expect(removeBtn).toBeDisabled();
  });

  test("remove button becomes enabled when multiple rows exist", async ({
    page,
  }) => {
    await page.goto("/admin/team-season-rosters/add?team_season_id=1");
    await page.waitForLoadState("networkidle");

    await page.click("#add-row-btn");

    // Both remove buttons should be enabled
    const removeBtns = page.locator(".remove-row-btn");
    await expect(removeBtns.first()).toBeEnabled();
    await expect(removeBtns.last()).toBeEnabled();
  });

  test("removing a row updates the count and re-indexes", async ({
    page,
  }) => {
    await page.goto("/admin/team-season-rosters/add?team_season_id=1");
    await page.waitForLoadState("networkidle");

    // Add two rows (total 3)
    await page.click("#add-row-btn");
    await page.click("#add-row-btn");
    await expect(page.locator(".roster-row")).toHaveCount(3);

    // Remove the second row
    await page.locator(".roster-row").nth(1).locator(".remove-row-btn").click();

    // Should be 2 rows
    await expect(page.locator(".roster-row")).toHaveCount(2);

    // Last row should have re-indexed to index 1
    const lastHidden = page.locator(".roster-row").last().locator(".roster-person-id");
    await expect(lastHidden).toHaveAttribute("name", "rows[1][person_id]");
  });

  test("team_season_id is pre-selected from query string", async ({
    page,
  }) => {
    await page.goto("/admin/team-season-rosters/add?team_season_id=1");
    await page.waitForLoadState("networkidle");

    const teamSeasonSelect = page.locator('select[name="team_season_id"]');
    await expect(teamSeasonSelect).toHaveValue("1");
  });

  test("New Person button opens modal", async ({ page }) => {
    await page.goto("/admin/team-season-rosters/add?team_season_id=1");
    await page.waitForLoadState("networkidle");

    // Click "New Person" button
    await page.click('[data-bs-target="#add-person-modal"]');

    // Modal should be visible
    const modal = page.locator("#add-person-modal");
    await expect(modal).toBeVisible();
  });
});
