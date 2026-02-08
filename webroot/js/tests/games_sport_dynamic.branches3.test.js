/* games_sport_dynamic.branches3.test.js
 * Focused branch tests for webroot/js/games_sport_dynamic.js
 */
// ...existing code...

beforeEach(() => {
  jest.resetModules();
  document.body.innerHTML = '';
  // ensure any globals are clean
  try {
    delete window.$;
  } catch { /* ignore */ }
});

test('buildFieldControl creates number input with min/max and label', () => {
  // prepare DOM so module will export helpers
  const sel = document.createElement('select');
  sel.id = 'team-season-select';
  sel.setAttribute('data-sport-url', '/sport-meta');
  document.body.appendChild(sel);
  const section = document.createElement('div');
  section.id = 'sport-specific-section';
  document.body.appendChild(section);

  const mod = require('../games_sport_dynamic.js');
  const m = mod && mod.buildFieldControl ? mod : mod && mod.default ? mod.default : mod;
  const { buildFieldControl } = m;

  const field = {
    field_name: 'score',
    display_label: 'Score',
    field_type: 'number',
    min: 0,
    max: 10,
    default_value: '5',
  };
  const wrapper = buildFieldControl(field, {});
  const input = wrapper.querySelector('input');
  const label = wrapper.querySelector('label');
  expect(input).toBeTruthy();
  expect(input.type).toBe('number');
  expect(input.min).toBe('0');
  expect(input.max).toBe('10');
  expect(input.value).toBe('5');
  expect(label.textContent).toBe('Score');
});

test('groupFields groups by field_group and renderEav creates cards and rows', () => {
  const sel = document.createElement('select');
  sel.id = 'team-season-select';
  sel.setAttribute('data-sport-url', '/sport-meta');
  document.body.appendChild(sel);
  const section = document.createElement('div');
  section.id = 'sport-specific-section';
  document.body.appendChild(section);

  const mod = require('../games_sport_dynamic.js');
  const { groupFields, renderEav } = mod;

  const fields = [
    { field_name: 'a', field_group: 'one' },
    { field_name: 'b', field_group: 'one' },
    { field_name: 'c', field_group: 'two' },
    { field_name: 'd', field_group: 'two' },
    { field_name: 'e', field_group: 'two' },
  ];
  const grouped = groupFields(fields);
  expect(Object.keys(grouped).sort()).toEqual(['one', 'two']);

  // render and ensure cards created
  renderEav(fields, { a: '1', b: '2' });
  const cards = document.querySelectorAll('#sport-specific-section .card');
  expect(cards.length).toBe(2);
  // check rows were created (chunking logic)
  const rows = document.querySelectorAll('#sport-specific-section .card-body .row');
  expect(rows.length).toBeGreaterThanOrEqual(1);
});

test('fetchMeta uses HTML fragment when available and updates sport name', async () => {
  const sel = document.createElement('select');
  sel.id = 'team-season-select';
  sel.setAttribute('data-sport-url', '/sport-meta');
  sel.value = '';
  document.body.appendChild(sel);
  const section = document.createElement('div');
  section.id = 'sport-specific-section';
  document.body.appendChild(section);
  const indicator = document.createElement('div');
  indicator.id = 'sport-indicator';
  document.body.appendChild(indicator);
  const loading = document.createElement('div');
  loading.id = 'sport-loading';
  document.body.appendChild(loading);
  const sportName = document.createElement('span');
  sportName.id = 'current-sport';
  document.body.appendChild(sportName);

  // mock fetch: first call returns ok HTML fragment
  global.fetch = jest.fn()
    .mockResolvedValueOnce({ ok: true, text: async () => '<div data-sport-name="Soccer">x</div>' });

  const mod = require('../games_sport_dynamic.js');
  const { fetchMeta } = mod;

  await fetchMeta('1');
  expect(document.getElementById('current-sport').textContent).toBe('Soccer');
  // section should contain HTML
  expect(document.getElementById('sport-specific-section').innerHTML).toContain('data-sport-name');
});

test('fetchMeta falls back to JSON and warns on failure', async () => {
  const sel = document.createElement('select');
  sel.id = 'team-season-select';
  sel.setAttribute('data-sport-url', '/sport-meta');
  sel.value = '1';
  document.body.appendChild(sel);
  const section = document.createElement('div');
  section.id = 'sport-specific-section';
  document.body.appendChild(section);
  const sportName = document.createElement('span');
  sportName.id = 'current-sport';
  document.body.appendChild(sportName);

  // first fetch throws (HTML path), second returns JSON success, third throws to hit warn
  const okJson = { success: true, sportName: 'Rugby', eavTemplate: [], values: {} };
  global.fetch = jest
    .fn()
    .mockRejectedValueOnce(new Error('html fail'))
    .mockResolvedValueOnce({ json: async () => okJson });

  const mod = require('../games_sport_dynamic.js');
  const { fetchMeta } = mod;

  // first case: fallback JSON success
  sel.value = '1';
  await fetchMeta('1');
  expect(document.getElementById('current-sport').textContent).toBe('Rugby');

  // now make both fetches fail to hit the console.warn branch
  global.fetch = jest.fn().mockRejectedValue(new Error('network'));
  const warnSpy = jest.spyOn(console, 'warn').mockImplementation(() => {});
  await fetchMeta('1');
  expect(warnSpy).toHaveBeenCalled();
  warnSpy.mockRestore();
});
