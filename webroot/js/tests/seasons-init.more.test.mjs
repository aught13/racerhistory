import { jest } from '@jest/globals';
import mod from '../modules/seasons-init.js';
const initSeasons = mod && mod.default ? mod.default : mod;

describe('seasons-init edge cases', () => {
  beforeEach(() => {
    jest.resetModules();
    // clean any previous globals
    try {
      delete global.window;
    } catch (e) {}
    global.window = {};
    document.body.innerHTML = '';
  });

  test('throws when jQuery/DataTables missing', () => {
    expect(() => initSeasons()).toThrow(/jQuery \/ DataTables not available/);
  });

  test('returns nulls when table selector not present', () => {
    // minimal jQuery shim that returns empty selection
    function $(sel) {
      return { length: 0, get() { return undefined; } };
    }
    $.fn = {};
    global.window.$ = $;

    const res = initSeasons({ tableSelector: '#no-such' });
    expect(res).toEqual({ sb: null, table: null });
  });
});
