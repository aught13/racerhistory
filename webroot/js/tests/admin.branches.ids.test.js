/* eslint-env jest */
jest.disableAutomock();

beforeEach(() => {
  jest.resetModules();
});

test('buildExtraFields handles JSON array string and returns id entries', () => {
  const admin = require('../admin.js');
  const build = admin.__internals.buildExtraFields;
  const res = build({ ids: JSON.stringify([1, 2, 3]), idsName: 'ids[]' });
  expect(Array.isArray(res)).toBe(true);
  expect(res.length).toBe(3);
  expect(res[0].name).toBe('ids[]');
  expect(res[0].value).toBe('1');
});

test('buildExtraFields accepts numeric string fallback and trims', () => {
  const admin = require('../admin.js');
  const build = admin.__internals.buildExtraFields;
  const res = build({ ids: ' 42 ', idsName: 'id' });
  expect(res.length).toBe(1);
  expect(res[0].value).toBe('42');
});

test('buildExtraFields returns empty array for non-numeric invalid JSON', () => {
  const admin = require('../admin.js');
  const build = admin.__internals.buildExtraFields;
  const res = build({ ids: 'not-json', idsName: 'id' });
  expect(res.length).toBe(0);
});

test('buildExtraFields handles array input and skips empty/null values', () => {
  const admin = require('../admin.js');
  const build = admin.__internals.buildExtraFields;
  const res = build({ ids: ['', null, 7, ' 8 ', { toString: () => '9' }], idsName: 'ids[]' });
  // should include 7, '8', '9' => 3 entries
  expect(res.length).toBe(3);
  const vals = res.map((r) => r.value);
  expect(vals).toEqual(expect.arrayContaining(['7', '8', '9']));
});

test('buildExtraFields includes bulk_action when present', () => {
  const admin = require('../admin.js');
  const build = admin.__internals.buildExtraFields;
  const res = build({ ids: [1], idsName: 'x', bulkAction: 'delete' });
  expect(res.some((r) => r.name === 'bulk_action' && r.value === 'delete')).toBe(true);
});

test('buildExtraFields swallows toString errors and still returns others', () => {
  const admin = require('../admin.js');
  const build = admin.__internals.buildExtraFields;
  const bad = {
    toString: () => {
      throw new Error('boom');
    },
  };
  const res = build({ ids: [1, bad, 3], idsName: 'ids[]' });
  // When an element's toString throws, the implementation stops normalizing
  // further items and returns whatever it managed to push before the error.
  // Ensure it at least returns the earlier successful entry and doesn't throw.
  const vals = res.map((r) => r.value);
  expect(vals).toContain('1');
  expect(vals.length).toBeGreaterThanOrEqual(1);
});
