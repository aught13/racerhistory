/* seasons-init.modules.test.js
 * Focused tests for webroot/js/modules/seasons-init.js
 */
/* eslint-env jest */

beforeEach(() => {
  jest.resetModules();
  document.body.innerHTML = "";
  // ensure clean global jQuery shim
  try {
    delete window.$;
  } catch (e) {
    // ignore
  }
});

test('returns null when no table present', () => {
  // mock minimal jQuery that returns empty selection
  window.$ = (sel) => ({ length: 0, get: () => [] });
  window.$.fn = { dataTable: { isDataTable: () => false } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  const res = initSeasons();
  expect(res).toEqual({ sb: null, table: null });
});

test('initializes DataTable and shows placeholder when SearchBuilder missing', () => {
  // prepare DOM
  const table = document.createElement('table');
  table.id = 'seasons-table';
  const thead = document.createElement('thead');
  const theadTr = document.createElement('tr');
  const th = document.createElement('th');
  th.textContent = 'Col1';
  theadTr.appendChild(th);
  thead.appendChild(theadTr);
  table.appendChild(thead);
  const tbody = document.createElement('tbody');
  const row = document.createElement('tr');
  const td = document.createElement('td');
  td.textContent = '1';
  row.appendChild(td);
  tbody.appendChild(row);
  table.appendChild(tbody);
  document.body.appendChild(table);

  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  // mock jQuery + DataTable without SearchBuilder
  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          // simulate DataTable init lifecycle by calling initComplete
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          if (options && typeof options.initComplete === 'function') {
            options.initComplete.call(thisArg);
          }
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {
        const root = document.querySelector(sel);
        if (root) root.innerHTML = '';
      },
      append: (el) => {
        const root = document.querySelector(sel);
        if (root && el && el.nodeType) root.appendChild(el);
      },
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => false, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  const res = initSeasons({ columnLabels: ['Col1'], columns: [0] });

  expect(res.table).not.toBeNull();
  // filter button should have been created
  expect(document.getElementById('seasons-filter-btn')).toBeTruthy();
  // placeholder text added to panel
  const ph = document.querySelector('#searchbuilder-panel .p-3');
  expect(ph).toBeTruthy();
  expect(ph.textContent).toMatch(/Advanced filter/);
});

test('initializes DataTable and SearchBuilder when available', () => {
  // prepare DOM similar to previous test
  const table = document.createElement('table');
  table.id = 'seasons-table';
  const thead = document.createElement('thead');
  const theadTr = document.createElement('tr');
  const th = document.createElement('th');
  th.textContent = 'Col1';
  theadTr.appendChild(th);
  thead.appendChild(theadTr);
  table.appendChild(thead);
  const tbody = document.createElement('tbody');
  const row = document.createElement('tr');
  const td = document.createElement('td');
  td.textContent = '1';
  row.appendChild(td);
  tbody.appendChild(row);
  table.appendChild(tbody);
  document.body.appendChild(table);

  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  // mock jQuery + DataTable + SearchBuilder available
  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          if (options && typeof options.initComplete === 'function') {
            options.initComplete.call(thisArg);
          }
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: () => {},
      addClass: () => {},
      on: () => {},
    };
  };
  // provide SearchBuilder implementation
  window.$.fn = {
    dataTable: {
      isDataTable: () => false,
      SearchBuilder: function (dtApi, opts) {
        this._container = document.createElement('div');
        this._container.textContent = 'SB';
        this.container = function () {
          return this._container;
        };
        this.destroy = function () {};
      },
    },
  };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  const res = initSeasons({ columnLabels: ['Col1'], columns: [0] });

  expect(res.table).not.toBeNull();
  // ensure DataTable initialized and filter button exists (SearchBuilder UI may vary)
  expect(document.getElementById('seasons-filter-btn')).toBeTruthy();
});

test('restores header labels and invokes user callbacks', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>Old A</th><th>Old B</th></tr></thead>
    <tbody><tr><td></td><td></td></tr></tbody>
  `;
  document.body.appendChild(table);

  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  const initComplete = jest.fn();
  const drawCallback = jest.fn();

  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          // simulate DataTable altering header text before initComplete runs
          table.querySelectorAll('thead th')[0].textContent = 'Changed A';
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          if (options && typeof options.initComplete === 'function') {
            options.initComplete.call(thisArg);
          }
          if (options && typeof options.drawCallback === 'function') {
            options.drawCallback.call(thisArg);
          }
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: () => {},
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => false, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  initSeasons({
    columnLabels: ['Old A', 'Old B'],
    dataTableOptions: { initComplete, drawCallback },
  });

  const headers = table.querySelectorAll('thead th');
  expect(headers[0].textContent).toBe('Old A');
  expect(initComplete).toHaveBeenCalled();
  expect(drawCallback).toHaveBeenCalled();
});

test('handles SearchBuilder constructor error with placeholder', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>Col1</th></tr></thead>
    <tbody><tr><td></td></tr></tbody>
  `;
  document.body.appendChild(table);

  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          if (options && typeof options.initComplete === 'function') {
            options.initComplete.call(thisArg);
          }
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: (el) => {
        if (el && el.nodeType) panel.appendChild(el);
      },
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = {
    dataTable: {
      isDataTable: () => false,
      SearchBuilder: function () {
        throw new Error('SB failed');
      },
    },
  };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  initSeasons({ columnLabels: ['Col1'] });

  const placeholder = panel.querySelector('.p-3');
  expect(placeholder).toBeTruthy();
  expect(placeholder.textContent).toMatch(/Advanced filter/);
});

test('renumberRows falls back to first cell when number column missing', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th></tr></thead>
    <tbody><tr><td>old</td></tr></tbody>
  `;
  document.body.appendChild(table);

  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          if (options && typeof options.initComplete === 'function') {
            options.initComplete.call(thisArg);
          }
          if (options && typeof options.drawCallback === 'function') {
            options.drawCallback.call(thisArg);
          }
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: () => {},
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => false, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  initSeasons({ numberColumn: 3 });

  const firstCell = table.querySelector('tbody td');
  expect(firstCell.textContent).toBe('1');
});

test('destroyExisting removes prior DataTable instance', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th></tr></thead>
    <tbody><tr><td>1</td></tr></tbody>
  `;
  document.body.appendChild(table);
  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  const destroySpy = jest.fn();

  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function () {
          return { destroy: destroySpy, api: () => ({ columns: { adjust: () => ({ draw: () => {} }) } }) };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: () => {},
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => true, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  initSeasons();

  expect(destroySpy).toHaveBeenCalled();
});

test('applies columnDefs from columnLabels and preserves custom defs', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th><th>B</th></tr></thead>
    <tbody><tr><td>1</td><td>2</td></tr></tbody>
  `;
  document.body.appendChild(table);
  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  let capturedOptions = null;
  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          capturedOptions = options;
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          options?.initComplete?.call(thisArg);
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: () => {},
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => false, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  initSeasons({
    columnLabels: ['A', 'B'],
    dataTableOptions: { columnDefs: [{ targets: 99, visible: false }] },
  });

  expect(Array.isArray(capturedOptions.columnDefs)).toBe(true);
  expect(capturedOptions.columnDefs.some((def) => def.targets === 0)).toBe(true);
  expect(capturedOptions.columnDefs.some((def) => def.targets === 99)).toBe(true);
});

test('returns nulls when controls or panel missing', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th></tr></thead>
    <tbody><tr><td>1</td></tr></tbody>
  `;
  document.body.appendChild(table);

  // controls and panel are not present
  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          options?.initComplete?.call(thisArg);
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: () => {},
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => false, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  const res = initSeasons();

  expect(res.table).not.toBeNull();
  expect(document.getElementById('seasons-filter-btn')).toBeNull();
});

test('handles DataTable init failure and returns nulls', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th></tr></thead>
    <tbody><tr><td>1</td></tr></tbody>
  `;
  document.body.appendChild(table);
  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function () {
          throw new Error('DT failed');
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: () => {},
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => false, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  const res = initSeasons();

  expect(res).toEqual({ sb: null, table: null });
});

test('adds placeholder when SearchBuilder missing and panel cleared', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th></tr></thead>
    <tbody><tr><td>1</td></tr></tbody>
  `;
  document.body.appendChild(table);

  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  panel.innerHTML = '<div class="existing">Keep</div>';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          options?.initComplete?.call(thisArg);
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: (el) => {
        if (el && el.nodeType) panel.appendChild(el);
      },
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => false, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  initSeasons();

  expect(panel.querySelector('.p-3')).toBeTruthy();
});

test('handles DataTable retrieval failure when already initialized', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th></tr></thead>
    <tbody><tr><td>1</td></tr></tbody>
  `;
  document.body.appendChild(table);
  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function () {
          throw new Error('read fail');
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: () => {},
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => true, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  const res = initSeasons();

  expect(res).toEqual({ sb: null, table: null });
});

test('filter button toggles panel visibility', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th></tr></thead>
    <tbody><tr><td>1</td></tr></tbody>
  `;
  document.body.appendChild(table);

  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  panel.classList.add('d-none');
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          options?.initComplete?.call(thisArg);
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: (el) => {
        if (el && el.nodeType) panel.appendChild(el);
      },
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => false, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  initSeasons();

  const btn = document.getElementById('seasons-filter-btn');
  expect(btn).toBeTruthy();

  btn.click();
  expect(panel.classList.contains('d-none')).toBe(false);
  expect(btn.getAttribute('aria-expanded')).toBe('true');

  btn.click();
  expect(panel.classList.contains('d-none')).toBe(true);
  expect(btn.getAttribute('aria-expanded')).toBe('false');
});

test('search builder with missing container uses placeholder', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th></tr></thead>
    <tbody><tr><td>1</td></tr></tbody>
  `;
  document.body.appendChild(table);

  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          options?.initComplete?.call(thisArg);
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: (el) => {
        if (el && el.nodeType) panel.appendChild(el);
      },
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = {
    dataTable: {
      isDataTable: () => false,
      SearchBuilder: function () {
        // no container or dom provided
        this.destroy = function () {};
      },
    },
  };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  initSeasons();

  expect(panel.querySelector('.p-3')).toBeTruthy();
});

test('reuses SearchBuilder instance when initComplete runs twice', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th></tr></thead>
    <tbody><tr><td>1</td></tr></tbody>
  `;
  document.body.appendChild(table);

  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  const containerEl = document.createElement('div');
  containerEl.className = 'sb-container';

  const sbFactory = jest.fn(() => ({
    container: () => containerEl,
    destroy: () => {},
  }));

  let panelEmptyCalls = 0;
  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          options?.initComplete?.call(thisArg);
          options?.initComplete?.call(thisArg);
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    if (sel === '#searchbuilder-panel') {
      return {
        empty: () => {
          panelEmptyCalls += 1;
          panel.innerHTML = '';
        },
        append: (el) => {
          if (el && el.nodeType) panel.appendChild(el);
        },
        addClass: () => {},
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: () => {},
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = {
    dataTable: {
      isDataTable: () => false,
      SearchBuilder: sbFactory,
    },
  };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  initSeasons({ columnLabels: ['A'] });

  expect(sbFactory).toHaveBeenCalledTimes(1);
  expect(panel.querySelector('.sb-container')).toBeTruthy();
  expect(panelEmptyCalls).toBeGreaterThan(1);
});

test('skips empty column labels when building columnDefs', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th><th></th><th></th></tr></thead>
    <tbody><tr><td>1</td><td>2</td><td>3</td></tr></tbody>
  `;
  document.body.appendChild(table);
  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  let capturedOptions = null;
  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          capturedOptions = options;
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          options?.initComplete?.call(thisArg);
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: () => {},
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => false, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  initSeasons({ columnLabels: ['A', '', null] });

  expect(capturedOptions.columnDefs).toEqual([{ targets: 0, title: 'A' }]);
});

test('renumberRows exits when no rows present', () => {
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th></tr></thead>
    <tbody></tbody>
  `;
  document.body.appendChild(table);
  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          options?.initComplete?.call(thisArg);
          options?.drawCallback?.call(thisArg);
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: () => {},
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => false, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  initSeasons();

  expect(table.querySelectorAll('tbody td').length).toBe(0);
});

test('restores headers for scroll head table when present', () => {
  const wrapper = document.createElement('div');
  wrapper.className = 'dataTables_wrapper';
  const table = document.createElement('table');
  table.id = 'seasons-table';
  table.innerHTML = `
    <thead><tr><th>A</th><th>B</th></tr></thead>
    <tbody><tr><td>1</td><td>2</td></tr></tbody>
  `;
  const scrollHead = document.createElement('div');
  scrollHead.className = 'dataTables_scrollHead';
  scrollHead.innerHTML = `
    <table><thead><tr><th>A</th><th>B</th></tr></thead></table>
  `;
  wrapper.appendChild(scrollHead);
  wrapper.appendChild(table);
  document.body.appendChild(wrapper);

  const panel = document.createElement('div');
  panel.id = 'searchbuilder-panel';
  document.body.appendChild(panel);
  const controls = document.createElement('div');
  controls.id = 'seasons-controls';
  document.body.appendChild(controls);

  window.$ = function (sel) {
    const isTableSel = sel === '#seasons-table' || sel === table || sel === table.id;
    if (isTableSel) {
      return {
        length: 1,
        get: (i) => (typeof i === 'number' ? table : [table]),
        DataTable: function (options) {
          table.querySelectorAll('thead th')[0].textContent = 'Changed';
          scrollHead.querySelectorAll('th')[0].textContent = 'Changed';
          const apiObj = { columns: { adjust: () => ({ draw: () => {} }) } };
          const thisArg = { api: () => apiObj };
          options?.initComplete?.call(thisArg);
          return { destroy: () => {}, api: () => apiObj };
        },
      };
    }
    return {
      remove: () => {},
      empty: () => {},
      append: () => {},
      addClass: () => {},
      on: () => {},
    };
  };
  window.$.fn = { dataTable: { isDataTable: () => false, SearchBuilder: undefined } };

  const mod = require('../modules/seasons-init.js');
  const initSeasons = mod && mod.default ? mod.default : mod;
  initSeasons({ columnLabels: ['A', 'B'] });

  expect(table.querySelectorAll('thead th')[0].textContent).toBe('A');
  expect(scrollHead.querySelectorAll('th')[0].textContent).toBe('A');
});
