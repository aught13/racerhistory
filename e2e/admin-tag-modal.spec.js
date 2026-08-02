import { test, expect } from '@playwright/test';
import { loginToAdmin } from './support/auth.js';

test.describe('Tag modal integration', () => {
  test.beforeEach(async ({ page }) => {
    const loggedIn = await loginToAdmin(page, { timeout: 15000 });
    test.skip(!loggedIn, 'Could not authenticate as the e2e admin user');
  });

  test('opens modal, performs lookup, and saves tags', async ({ page }) => {
    await page.goto('/admin/images/bulk-upload-form', { waitUntil: 'domcontentloaded' });

    // Stub lookup endpoints so tests are deterministic
    await page.route('**/admin/tag-lookups/persons*', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ success: true, persons: [{ id: 42, label: 'Test Person' }] }),
      }),
    );

    // Stub apply endpoint to return the applied tag and form fields
    await page.route('**/admin/tags/apply/images/*', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          applied: ['person-42'],
          tags: [{ slug: 'person-42', name: 'Test Person' }],
          formFields: { person_select: [42], tags: 'test-tag' },
        }),
      }),
    );

    // Click the Edit Tags button
    const editBtn = page.locator('button:has-text("Edit Tags")').first();
    await expect(editBtn).toBeVisible();
    await editBtn.click();

    // Wait for modal to appear
    const modal = page.locator('.modal.show');
    await modal.waitFor({ state: 'visible', timeout: 5000 });

    // Type into the person search and wait for datalist option to appear
    const personSearch = modal.locator('input[name="person_search"]');
    await personSearch.fill('Test');

    // Wait for the fetch to be triggered and the datalist populated
    const personsList = modal.locator('datalist');
    await expect(personsList).toBeAttached();

    // Click Add Person
    const addBtn = modal.locator('button:has-text("Add Person")');
    await expect(addBtn).toBeVisible();
    await addBtn.click();

    // Click Save Tags in modal footer
    const saveBtn = page.locator('.modal.show button:has-text("Save Tags")').first();
    await expect(saveBtn).toBeVisible();
    await saveBtn.click();

    // Wait for modal to close
    await expect(page.locator('.modal.show')).toHaveCount(0);

    // Verify the trigger badges updated
    const badges = page.locator('.tag-modal-trigger .tag-badges');
    await expect(badges).toContainText('Test Person');

    // Verify hidden input was added with person_select[] value
    const hidden = page.locator('.tag-modal-trigger .tag-modal-hidden-inputs input[name="person_select[]"]');
    await expect(hidden).toHaveCount(1);
    await expect(hidden.first()).toHaveValue('42');
  });

  test('scopes game lookup by selected team season', async ({ page }) => {
    await page.goto('/admin/images/bulk-upload-form', { waitUntil: 'domcontentloaded' });

    let gameLookupUrl = null;
    await page.route('**/admin/tag-lookups/games*', (route) => {
      gameLookupUrl = route.request().url();
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          games: [{ id: 999, label: 'State at Murray', team_season_id: 110 }],
        }),
      });
    });

    const editBtn = page.locator('button:has-text("Edit Tags")').first();
    await expect(editBtn).toBeVisible();
    await editBtn.click();

    const modal = page.locator('.modal.show');
    await modal.waitFor({ state: 'visible', timeout: 5000 });

    const teamSeason = modal.locator('select[name="teamseason_select"]');
    await expect(teamSeason).toBeVisible();
    await teamSeason.evaluate((selectEl) => {
      const target = Array.from(selectEl.options).find((opt) => opt.value && /2025-2026/.test(opt.textContent || ''));
      if (target) {
        selectEl.value = target.value;
        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });

    const gameSearch = modal.locator('input[name="game_search"]');
    await expect(gameSearch).toBeEnabled();
    await gameSearch.fill('St');

    await expect.poll(() => gameLookupUrl).toContain('teamseason_id=');
    await expect(modal.locator('datalist[id$="gamesList"] option')).toHaveCount(1);
  });
});
