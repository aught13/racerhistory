/* global KeyboardEvent */
/* season-view-init.branches.test.mjs
 * Focused tests for webroot/js/modules/season-view-init.mjs
 */
import initSeasonView from '../modules/season-view-init.mjs';

beforeEach(() => {
  document.body.innerHTML = '';
});

test('initSeasonView registers stats tab and mounts advanced table on click (valid payload)', () => {
  // prepare DOM with stats tab and advanced panel
  const root = document.createElement('div');
  const tabButton = document.createElement('button');
  tabButton.setAttribute('data-season-stats-tab', 'advanced');
  const panelsWrap = document.createElement('div');
  panelsWrap.setAttribute('data-season-stats-tabs', '');
  const advancedPanel = document.createElement('div');
  advancedPanel.setAttribute('data-season-advanced-stats', '');
  const container = document.createElement('div');
  container.setAttribute('data-season-advanced-table-container', '');

  const payload = {
    players: [
      { name: 'A', GP: 1, FGM: 2, FGA: 4, TPM: 1, TPA: 2, FTM: 0, FTA: 0, PTS: 5 },
    ],
    teamTotals: { name: 'Team', GP: 1, FGM: 2, FGA: 4, TPM: 1, TPA: 2, FTM: 0, FTA: 0, PTS: 5 },
  };

  advancedPanel.dataset.seasonAdvancedStats = JSON.stringify(payload);
  panelsWrap.appendChild(tabButton);
  panelsWrap.appendChild(advancedPanel);
  advancedPanel.appendChild(container);
  root.appendChild(panelsWrap);
  document.body.appendChild(root);

  // call init which wires up handlers
  initSeasonView({ root: document });

  // click tab to trigger mountAdvancedShootingTable via handler
  tabButton.click();

  expect(container.querySelector('table')).toBeTruthy();
});

test('initSeasonView mounts placeholder when advanced payload invalid', () => {
  const root = document.createElement('div');
  const tabButton = document.createElement('button');
  tabButton.setAttribute('data-season-stats-tab', 'advanced');
  const panelsWrap = document.createElement('div');
  panelsWrap.setAttribute('data-season-stats-tabs', '');
  const advancedPanel = document.createElement('div');
  advancedPanel.setAttribute('data-season-advanced-stats', '');
  const container = document.createElement('div');
  container.setAttribute('data-season-advanced-table-container', '');

  advancedPanel.dataset.seasonAdvancedStats = 'not-json';
  panelsWrap.appendChild(tabButton);
  panelsWrap.appendChild(advancedPanel);
  advancedPanel.appendChild(container);
  root.appendChild(panelsWrap);
  document.body.appendChild(root);

  initSeasonView({ root: document });
  tabButton.click();

  expect(container.innerHTML).toMatch(/could not be loaded|unavailable/);
});

test('advanced table omits three-point columns when no attempts', () => {
  const root = document.createElement('div');
  const tabButton = document.createElement('button');
  tabButton.setAttribute('data-season-stats-tab', 'advanced');
  const panelsWrap = document.createElement('div');
  panelsWrap.setAttribute('data-season-stats-tabs', '');
  const advancedPanel = document.createElement('div');
  advancedPanel.setAttribute('data-season-advanced-stats', '');
  const container = document.createElement('div');
  container.setAttribute('data-season-advanced-table-container', '');

  const payload = {
    players: [
      { name: 'A', GP: 1, FGM: 2, FGA: 4, TPM: 0, TPA: 0, FTM: 0, FTA: 0, PTS: 5 },
    ],
  };

  advancedPanel.dataset.seasonAdvancedStats = JSON.stringify(payload);
  panelsWrap.appendChild(tabButton);
  panelsWrap.appendChild(advancedPanel);
  advancedPanel.appendChild(container);
  root.appendChild(panelsWrap);
  document.body.appendChild(root);

  initSeasonView({ root: document });
  tabButton.click();

  const headers = Array.from(container.querySelectorAll('th')).map((th) => th.textContent);
  expect(headers).not.toContain('TP%');
  expect(headers).toContain('FG%');
});

test('image gallery modal closes on background click and Escape', () => {
  const root = document.createElement('div');
  root.innerHTML = `
    <div data-season-image-gallery>
      <img class="season-photo-thumb-img" data-image-id="1" data-image-filename="a.jpg" />
    </div>
    <div data-season-image-modal data-modal-open>
      <button data-modal-close>Close</button>
      <picture>
        <source data-modal-image-webp />
        <img data-modal-image-fallback />
      </picture>
    </div>
  `;
  document.body.appendChild(root);

  initSeasonView({ root });

  const modal = root.querySelector('[data-season-image-modal]');
  modal.dispatchEvent(new MouseEvent('click', { bubbles: true }));
  expect(modal.hasAttribute('data-modal-open')).toBe(false);

  modal.setAttribute('data-modal-open', '');
  document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
  expect(modal.hasAttribute('data-modal-open')).toBe(false);
});

test('blog click falls back to location when view frame missing', () => {
  const root = document.createElement('div');
  root.innerHTML = `
    <div data-season-blog>
      <turbo-frame id="blog-frame">
        <div class="blog-list-item" data-blog-post="missing"></div>
      </turbo-frame>
    </div>
  `;
  document.body.appendChild(root);

  const originalLocation = window.location;
  // jsdom location is read-only; replace with a stub to observe href updates
  Object.defineProperty(window, 'location', {
    value: { href: 'http://localhost/' },
    configurable: true,
  });

  initSeasonView({ root });
  const item = root.querySelector('.blog-list-item');
  item.dispatchEvent(new MouseEvent('click', { bubbles: true }));

  expect(window.location.href).toContain('/blog/missing');

  Object.defineProperty(window, 'location', {
    value: originalLocation,
    configurable: true,
  });
});

test('advanced panel marks rendered when container missing', () => {
  const root = document.createElement('div');
  const tabButton = document.createElement('button');
  tabButton.setAttribute('data-season-stats-tab', 'advanced');
  const panelsWrap = document.createElement('div');
  panelsWrap.setAttribute('data-season-stats-tabs', '');
  const advancedPanel = document.createElement('div');
  advancedPanel.setAttribute('data-season-advanced-stats', '');
  advancedPanel.dataset.seasonAdvancedStats = JSON.stringify({ players: [] });

  panelsWrap.appendChild(tabButton);
  panelsWrap.appendChild(advancedPanel);
  root.appendChild(panelsWrap);
  document.body.appendChild(root);

  initSeasonView({ root: document });
  tabButton.click();

  expect(advancedPanel.dataset.seasonAdvancedRendered).toBe('true');
});

test('blog click falls back when Turbo missing but view frame exists', () => {
  const root = document.createElement('div');
  root.innerHTML = `
    <div data-season-blog>
      <turbo-frame id="blog-frame">
        <div class="blog-list-item" data-blog-post="alpha"></div>
        <turbo-frame id="blog-view" data-view-frame></turbo-frame>
      </turbo-frame>
    </div>
  `;
  document.body.appendChild(root);

  const originalLocation = window.location;
  Object.defineProperty(window, 'location', {
    value: { href: 'http://localhost/' },
    configurable: true,
  });

  delete window.Turbo;

  initSeasonView({ root });
  const item = root.querySelector('.blog-list-item');
  item.dispatchEvent(new MouseEvent('click', { bubbles: true }));

  expect(window.location.href).toContain('/blog/alpha');

  Object.defineProperty(window, 'location', {
    value: originalLocation,
    configurable: true,
  });
});

test('advanced table renders em dash for invalid percentages', () => {
  const root = document.createElement('div');
  const tabButton = document.createElement('button');
  tabButton.setAttribute('data-season-stats-tab', 'advanced');
  const panelsWrap = document.createElement('div');
  panelsWrap.setAttribute('data-season-stats-tabs', '');
  const advancedPanel = document.createElement('div');
  advancedPanel.setAttribute('data-season-advanced-stats', '');
  const container = document.createElement('div');
  container.setAttribute('data-season-advanced-table-container', '');

  const payload = {
    players: [
      { name: 'A', GP: 1, FGM: 0, FGA: 0, TPM: 0, TPA: 0, FTM: 0, FTA: 0, PTS: 0 },
    ],
  };

  advancedPanel.dataset.seasonAdvancedStats = JSON.stringify(payload);
  panelsWrap.appendChild(tabButton);
  panelsWrap.appendChild(advancedPanel);
  advancedPanel.appendChild(container);
  root.appendChild(panelsWrap);
  document.body.appendChild(root);

  initSeasonView({ root: document });
  tabButton.click();

  const cellTexts = Array.from(container.querySelectorAll('td')).map((td) => td.textContent);
  expect(cellTexts).toContain('—');
});
