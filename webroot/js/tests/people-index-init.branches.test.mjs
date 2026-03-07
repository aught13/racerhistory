import { jest } from "@jest/globals";

/**
 * Targeted branch coverage tests for modules/people-index-init.js
 * Focuses on uncovered branches in setupSearchBuilder, setupNameFilter,
 * destroyExisting with filter cleanup, and DataTable creation error paths.
 */

let jq, DataTableFn, dtInstance, sbMock, SBConstructor;

function setupMocks(opts = {}) {
    dtInstance = {
        destroy: jest.fn(),
        draw: jest.fn(),
        search: jest.fn().mockReturnThis(),
        columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
        table: jest.fn().mockReturnValue({
            node: jest.fn().mockReturnValue(null),
        }),
        api: jest.fn(),
    };
    dtInstance.api.mockReturnValue(dtInstance);

    DataTableFn = jest.fn().mockReturnValue(dtInstance);
    DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

    sbMock = {
        destroy: jest.fn(),
        container: jest.fn().mockReturnValue(document.createElement("div")),
        dom: { container: document.createElement("div") },
    };

    SBConstructor = jest.fn().mockReturnValue(sbMock);

    jq = jest.fn((sel) => {
        if (typeof sel === "string") {
            const els = document.querySelectorAll(sel);
            return {
                length: els.length,
                get: (i) => (i !== undefined ? els[i] || null : null),
                DataTable: DataTableFn,
                remove: jest.fn(),
                append: jest.fn((child) => {
                    if (els[0] && child instanceof Node) {
                        els[0].appendChild(child);
                    }
                }),
                empty: jest.fn(() => {
                    if (els[0]) els[0].innerHTML = "";
                }),
                addClass: jest.fn(),
            };
        }
        return {
            length: 1,
            get: () => sel,
            DataTable: DataTableFn,
            remove: jest.fn(),
        };
    });

    jq.fn = {
        DataTable: DataTableFn,
        dataTable: Object.assign(jest.fn(), {
            isDataTable: DataTableFn.isDataTable,
            ext: { search: [] },
            SearchBuilder: SBConstructor,
        }),
    };
    window.$ = jq;

    if (opts.noSearchBuilder) {
        delete jq.fn.dataTable.SearchBuilder;
    }

    return { dtInstance, DataTableFn, sbMock, SBConstructor, jq };
}

beforeEach(() => {
    jest.resetModules();
    jest.restoreAllMocks();
    document.body.innerHTML = "";
    delete window.$;
    jest.spyOn(console, "debug").mockImplementation(() => {});
    jest.spyOn(console, "warn").mockImplementation(() => {});
    jest.spyOn(console, "error").mockImplementation(() => {});
});

afterEach(() => {
    jest.restoreAllMocks();
    delete window.$;
});

describe("people-index-init destroyExisting filter cleanup", () => {
    test("destroyExisting removes _peopleNameFilterFn from ext.search", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <div id="people-controls"></div>
      <div id="people-searchbuilder-panel"></div>
      <input id="people-name-search" />
    `;
        const { DataTableFn: DF, dtInstance: dt } = setupMocks();
        const filters = window.$.fn.dataTable.ext.search;
        const mod = await import("../modules/people-index-init.js");

        // First init creates filter
        mod.default();
        const opts = DF.mock.calls[0]?.[0];

        // Simulate initComplete so name filter gets registered
        const tableNode = document.querySelector("#people-table");
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            table: jest.fn().mockReturnValue({
                node: jest.fn().mockReturnValue(tableNode),
            }),
        };
        opts.initComplete.call({ api: jest.fn().mockReturnValue(apiMock) });

        // Filter function should be registered
        expect(filters.length).toBeGreaterThan(0);
        expect(tableNode._peopleNameFilterFn).toBeDefined();

        // Second init triggers destroyExisting which should remove it
        DF.isDataTable.mockReturnValue(true);
        DF.mockClear();
        DF.mockReturnValue(dt);
        mod.default();

        // Filter should be removed
        expect(tableNode._peopleNameFilterFn).toBeUndefined();
    });

    test("destroyExisting when DataTable() throws in catch", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
    `;
        const { DataTableFn: DF } = setupMocks();
        DF.isDataTable.mockReturnValue(true);
        // First DataTable() call (to get existing instance) throws
        DF.mockImplementationOnce(() => {
            throw new Error("Cannot get DT");
        });
        // Second call (creating new) succeeds
        DF.mockReturnValue(dtInstance);

        const mod = await import("../modules/people-index-init.js");
        const result = mod.default();
        // Should still return a table (the second call succeeds)
        expect(result.table).toBeDefined();
    });

    test("destroyExisting with SearchBuilder that has no destroy but has dom.container", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <div id="people-controls"></div>
      <div id="people-searchbuilder-panel"></div>
    `;
        const { DataTableFn: DF } = setupMocks();
        const mod = await import("../modules/people-index-init.js");

        // First init creates SearchBuilder
        mod.default();
        const opts = DF.mock.calls[0]?.[0];
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            table: jest
                .fn()
                .mockReturnValue({ node: jest.fn().mockReturnValue(null) }),
        };
        opts.initComplete.call({ api: jest.fn().mockReturnValue(apiMock) });

        // Modify sbInstance to not have destroy
        // (sbMock was used by SBConstructor, it has destroy by default)
        // For re-init, set the mock sbInstance to only have dom.container
        sbMock.destroy = undefined;
        sbMock.container = undefined;

        // Second init triggers destroyExisting
        DF.isDataTable.mockReturnValue(true);
        DF.mockClear();
        DF.mockReturnValue(dtInstance);
        mod.default();
        // Should not throw
    });

    test("destroyExisting with SearchBuilder that only has container()", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <div id="people-controls"></div>
      <div id="people-searchbuilder-panel"></div>
    `;
        const { DataTableFn: DF } = setupMocks();
        const mod = await import("../modules/people-index-init.js");

        mod.default();
        const opts = DF.mock.calls[0]?.[0];
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            table: jest
                .fn()
                .mockReturnValue({ node: jest.fn().mockReturnValue(null) }),
        };
        opts.initComplete.call({ api: jest.fn().mockReturnValue(apiMock) });

        // Set sbMock to only have container (no destroy, no dom)
        delete sbMock.destroy;
        delete sbMock.dom;
        sbMock.container = jest
            .fn()
            .mockReturnValue(document.createElement("div"));

        DF.isDataTable.mockReturnValue(true);
        DF.mockClear();
        DF.mockReturnValue(dtInstance);
        mod.default();
    });
});

describe("people-index-init setupSearchBuilder edge cases", () => {
    test("setupSearchBuilder with existing sbInstance reuses container()", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <div id="people-controls"></div>
      <div id="people-searchbuilder-panel"></div>
    `;
        const { DataTableFn: DF, SBConstructor: SBC } = setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DF.mock.calls[0]?.[0];
        const tableNode = document.querySelector("#people-table");
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            table: jest.fn().mockReturnValue({
                node: jest.fn().mockReturnValue(tableNode),
            }),
        };
        const context = { api: jest.fn().mockReturnValue(apiMock) };

        // First initComplete creates sbInstance
        opts.initComplete.call(context);
        expect(SBC).toHaveBeenCalledTimes(1);

        // Invoke initComplete AGAIN (simulating reinit) to hit the "sbInstance exists" path
        opts.initComplete.call(context);
        // SBConstructor should NOT be called a second time
        expect(SBC).toHaveBeenCalledTimes(1);
    });

    test("setupSearchBuilder with existing sbInstance uses dom.container fallback", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <div id="people-controls"></div>
      <div id="people-searchbuilder-panel"></div>
    `;
        const { DataTableFn: DF, SBConstructor: SBC } = setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DF.mock.calls[0]?.[0];
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            table: jest
                .fn()
                .mockReturnValue({ node: jest.fn().mockReturnValue(null) }),
        };
        const context = { api: jest.fn().mockReturnValue(apiMock) };

        opts.initComplete.call(context);

        // Remove container function, keep dom.container
        delete sbMock.container;
        sbMock.dom = { container: document.createElement("div") };

        opts.initComplete.call(context);
        expect(SBC).toHaveBeenCalledTimes(1);
    });

    test("setupSearchBuilder where new SB has dom.container instead of container()", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <div id="people-controls"></div>
      <div id="people-searchbuilder-panel"></div>
    `;
        const { DataTableFn: DF, SBConstructor: SBC } = setupMocks();
        // SB returns instance with dom.container but no container()
        SBC.mockReturnValue({
            dom: { container: document.createElement("div") },
        });
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DF.mock.calls[0]?.[0];
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            table: jest
                .fn()
                .mockReturnValue({ node: jest.fn().mockReturnValue(null) }),
        };
        opts.initComplete.call({ api: jest.fn().mockReturnValue(apiMock) });
    });

    test("setupSearchBuilder where new SB returns null container", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <div id="people-controls"></div>
      <div id="people-searchbuilder-panel"></div>
    `;
        const { DataTableFn: DF, SBConstructor: SBC } = setupMocks();
        // SB returns instance with no container and no dom
        SBC.mockReturnValue({});
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DF.mock.calls[0]?.[0];
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            table: jest
                .fn()
                .mockReturnValue({ node: jest.fn().mockReturnValue(null) }),
        };
        opts.initComplete.call({ api: jest.fn().mockReturnValue(apiMock) });

        // Should show "Advanced filter not available." placeholder
        const panel = document.querySelector("#people-searchbuilder-panel");
        expect(panel.textContent).toContain("Advanced filter not available");
    });

    test("setupSearchBuilder with no controls element skips", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <div id="people-searchbuilder-panel"></div>
    `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default({ controlsSelector: "#nonexistent" });

        const opts = DataTableFn.mock.calls[0]?.[0];
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            table: jest
                .fn()
                .mockReturnValue({ node: jest.fn().mockReturnValue(null) }),
        };
        opts.initComplete.call({ api: jest.fn().mockReturnValue(apiMock) });
    });

    test("setupSearchBuilder with no panel element skips", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <div id="people-controls"></div>
    `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default({ panelSelector: "#nonexistent" });

        const opts = DataTableFn.mock.calls[0]?.[0];
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            table: jest
                .fn()
                .mockReturnValue({ node: jest.fn().mockReturnValue(null) }),
        };
        opts.initComplete.call({ api: jest.fn().mockReturnValue(apiMock) });
    });
});

describe("people-index-init setupNameFilter edge cases", () => {
    test("setupNameFilter with no api() method uses dtApi directly", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <input id="people-name-search" />
    `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        // Context without api() method - just the properties directly
        const dtApi = {
            draw: jest.fn(),
            search: jest.fn().mockReturnThis(),
            table: jest.fn().mockReturnValue({
                node: jest
                    .fn()
                    .mockReturnValue(document.querySelector("#people-table")),
            }),
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
        };
        opts.initComplete.call(dtApi);
    });

    test("setupNameFilter client-side filter with no nTr falls back to data[0]", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>Test User</td></tr></tbody>
      </table>
      <input id="people-name-search" />
    `;
        setupMocks();
        const filters = window.$.fn.dataTable.ext.search;
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        const tableNode = document.querySelector("#people-table");
        const apiMock = {
            draw: jest.fn(),
            table: jest.fn().mockReturnValue({
                node: jest.fn().mockReturnValue(tableNode),
            }),
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
        };
        opts.initComplete.call({ api: jest.fn().mockReturnValue(apiMock) });

        const filterFn = filters[filters.length - 1];
        const input = document.querySelector("#people-name-search");
        input.value = "test";

        // No nTr (null row) → falls back to data[0]
        const result = filterFn(
            { nTable: tableNode, aoData: [{ nTr: null }] },
            ["Test User"],
            0,
        );
        expect(result).toBe(true);

        // nTr exists but no data-person-search attribute → falls back to data[0]
        const row = document.createElement("tr");
        const result2 = filterFn(
            { nTable: tableNode, aoData: [{ nTr: row }] },
            ["Test User"],
            0,
        );
        expect(result2).toBe(true);
    });

    test("setupNameFilter client-side: input already bound twice skips rebind", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <input id="people-name-search" data-people-search-bound="true" />
    `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        const tableNode = document.querySelector("#people-table");
        const apiMock = {
            draw: jest.fn(),
            table: jest.fn().mockReturnValue({
                node: jest.fn().mockReturnValue(tableNode),
            }),
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
        };
        opts.initComplete.call({ api: jest.fn().mockReturnValue(apiMock) });

        // Input already marked as bound, so input event should not trigger api.draw
        const input = document.querySelector("#people-name-search");
        input.value = "test";
        input.dispatchEvent(new Event("input"));
        // draw should NOT be called since binding was skipped
    });

    test("setupNameFilter server-side: already-bound input skips rebind", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <input id="people-name-search" data-people-search-bound="true" />
    `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default({ dataUrl: "/api/people" });

        const opts = DataTableFn.mock.calls[0]?.[0];
        expect(opts.serverSide).toBe(true);

        const apiMock = {
            draw: jest.fn(),
            search: jest.fn().mockReturnThis(),
            table: jest.fn().mockReturnValue({
                node: jest
                    .fn()
                    .mockReturnValue(document.querySelector("#people-table")),
            }),
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
        };
        opts.initComplete.call({ api: jest.fn().mockReturnValue(apiMock) });
    });

    test("setupNameFilter where tableNode is null early returns", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <input id="people-name-search" />
    `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        // Call with context where api().table().node() returns null AND tableEl is also null
        // This is tricky since tableEl is captured at function scope
        // but we can make api().table().node() return null
        const apiMock = {
            draw: jest.fn(),
            table: jest.fn().mockReturnValue({
                node: jest.fn().mockReturnValue(null),
            }),
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
        };
        opts.initComplete.call({ api: jest.fn().mockReturnValue(apiMock) });
    });

    test("drawCallback calls setupNameFilter with this context", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <input id="people-name-search" />
    `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        // drawCallback is called with a context that has api()
        const tableNode = document.querySelector("#people-table");
        const apiMock = {
            draw: jest.fn(),
            search: jest.fn().mockReturnThis(),
            table: jest.fn().mockReturnValue({
                node: jest.fn().mockReturnValue(tableNode),
            }),
            api: jest.fn(),
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
        };
        apiMock.api.mockReturnValue(apiMock);
        opts.drawCallback.call(apiMock);
    });
});

describe("people-index-init initComplete edge cases", () => {
    test("initComplete with api().columns.adjust().draw(false) chain", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <div id="people-controls"></div>
      <div id="people-searchbuilder-panel"></div>
    `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        const drawMock = jest.fn();
        const adjustMock = jest.fn().mockReturnValue({ draw: drawMock });
        const apiMock = {
            columns: { adjust: adjustMock },
            draw: jest.fn(),
            table: jest
                .fn()
                .mockReturnValue({ node: jest.fn().mockReturnValue(null) }),
        };
        const context = { api: jest.fn().mockReturnValue(apiMock) };
        opts.initComplete.call(context);

        expect(context.api).toHaveBeenCalled();
        expect(adjustMock).toHaveBeenCalled();
        expect(drawMock).toHaveBeenCalledWith(false);
    });

    test("initComplete without api method skips columns.adjust", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
    `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        // Context without api method
        opts.initComplete.call({});
    });

    test("filter button already exists gets reused", async () => {
        document.body.innerHTML = `
      <table id="people-table">
        <thead><tr><th>Name</th></tr></thead>
        <tbody><tr><td>A</td></tr></tbody>
      </table>
      <div id="people-controls">
        <button id="people-filter-btn">Existing</button>
      </div>
      <div id="people-searchbuilder-panel" class="d-none"></div>
    `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            table: jest
                .fn()
                .mockReturnValue({ node: jest.fn().mockReturnValue(null) }),
        };
        opts.initComplete.call({ api: jest.fn().mockReturnValue(apiMock) });

        const btn = document.getElementById("people-filter-btn");
        // destroyExisting removes old btn; setupSearchBuilder creates a new one
        expect(btn).toBeTruthy();
    });
});
