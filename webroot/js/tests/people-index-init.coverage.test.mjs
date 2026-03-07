import { jest } from "@jest/globals";

/**
 * Coverage tests for modules/people-index-init.js
 * Targets: destroyExisting, setupSearchBuilder, setupNameFilter,
 *   server-side mode, client-side filter, error handling
 */

let jq, DataTableFn, dtInstance, sbMock;

function setupMocks() {
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

    const SBConstructor = jest.fn().mockReturnValue(sbMock);

    jq = jest.fn((sel) => {
        if (typeof sel === "string") {
            const els = document.querySelectorAll(sel);
            return {
                length: els.length,
                get: (i) => els[i] || null,
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

    return { dtInstance, DataTableFn, sbMock, SBConstructor };
}

beforeEach(() => {
    jest.resetModules();
    jest.restoreAllMocks();
    document.body.innerHTML = "";
    delete window.$;
});

afterEach(() => {
    jest.restoreAllMocks();
    delete window.$;
});

describe("people-index-init.js (coverage)", () => {
    test("returns null when DataTables not available", async () => {
        document.body.innerHTML = `<table id="people-table"></table>`;
        // No jQuery
        const mod = await import("../modules/people-index-init.js");
        const result = mod.default();
        expect(result).toEqual({ sb: null, table: null });
    });

    test("returns null when $.fn.DataTable is missing", async () => {
        document.body.innerHTML = `<table id="people-table"></table>`;
        window.$ = jest.fn();
        window.$.fn = {};
        const mod = await import("../modules/people-index-init.js");
        const result = mod.default();
        expect(result).toEqual({ sb: null, table: null });
    });

    test("returns null when table not found", async () => {
        document.body.innerHTML = `<div>No table</div>`;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        const result = mod.default({ tableSelector: "#people-table" });
        expect(result).toEqual({ sb: null, table: null });
    });

    test("destroys existing DataTable on re-init", async () => {
        document.body.innerHTML = `
            <table id="people-table"><thead><tr><th>Name</th></tr></thead><tbody><tr><td>A</td></tr></tbody></table>
            <div id="people-controls"></div>
            <div id="people-searchbuilder-panel"></div>
            <input id="people-name-search" />
        `;
        const { dtInstance: _dt, DataTableFn: DT } = setupMocks();
        DT.isDataTable.mockReturnValue(true);
        const mod = await import("../modules/people-index-init.js");

        // First init
        mod.default();
        expect(DT.isDataTable).toHaveBeenCalled();
    });

    test("initComplete triggers setupSearchBuilder and setupNameFilter", async () => {
        document.body.innerHTML = `
            <table id="people-table"><thead><tr><th>Name</th><th>Team</th><th>Pos</th></tr></thead><tbody><tr><td>A</td><td>B</td><td>C</td></tr></tbody></table>
            <div id="people-controls"></div>
            <div id="people-searchbuilder-panel"></div>
            <input id="people-name-search" />
        `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        // Capture the initComplete callback
        const opts = DataTableFn.mock.calls[0]?.[0];
        expect(opts).toBeDefined();
        expect(opts.initComplete).toBeDefined();

        // Simulate initComplete being called
        const drawFn = jest.fn();
        const adjustFn = jest.fn().mockReturnValue({ draw: drawFn });
        const context = {
            api: jest.fn().mockReturnValue({
                columns: { adjust: adjustFn },
                draw: drawFn,
                table: jest.fn().mockReturnValue({
                    node: jest
                        .fn()
                        .mockReturnValue(
                            document.querySelector("#people-table"),
                        ),
                }),
                search: jest.fn().mockReturnThis(),
            }),
        };
        opts.initComplete.call(context);

        // Button should be created in controls
        const btn = document.getElementById("people-filter-btn");
        expect(btn).toBeTruthy();
    });

    test("filter button toggles panel visibility", async () => {
        document.body.innerHTML = `
            <table id="people-table"><thead><tr><th>Name</th></tr></thead><tbody><tr><td>A</td></tr></tbody></table>
            <div id="people-controls"></div>
            <div id="people-searchbuilder-panel" class="d-none"></div>
            <input id="people-name-search" />
        `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        const context = {
            api: jest.fn().mockReturnValue({
                columns: {
                    adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
                },
                draw: jest.fn(),
                table: jest.fn().mockReturnValue({
                    node: jest
                        .fn()
                        .mockReturnValue(
                            document.querySelector("#people-table"),
                        ),
                }),
                search: jest.fn().mockReturnThis(),
            }),
        };
        opts.initComplete.call(context);

        const btn = document.getElementById("people-filter-btn");
        btn.click();
        const _panel = document.querySelector("#people-searchbuilder-panel");
        // After first click, panel should be visible
        expect(btn.getAttribute("aria-expanded")).toBe("true");

        btn.click();
        // After second click, panel should be hidden again
        expect(btn.getAttribute("aria-expanded")).toBe("false");
    });

    test("SearchBuilder unavailable shows placeholder", async () => {
        document.body.innerHTML = `
            <table id="people-table"><thead><tr><th>Name</th></tr></thead><tbody><tr><td>A</td></tr></tbody></table>
            <div id="people-controls"></div>
            <div id="people-searchbuilder-panel"></div>
        `;
        setupMocks();
        delete window.$.fn.dataTable.SearchBuilder;
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        const context = {
            api: jest.fn().mockReturnValue({
                columns: {
                    adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
                },
                draw: jest.fn(),
                table: jest.fn().mockReturnValue({
                    node: jest
                        .fn()
                        .mockReturnValue(
                            document.querySelector("#people-table"),
                        ),
                }),
            }),
        };
        opts.initComplete.call(context);

        const panel = document.querySelector("#people-searchbuilder-panel");
        expect(panel.classList.contains("d-none")).toBe(true);
    });

    test("server-side mode uses search() on input", async () => {
        document.body.innerHTML = `
            <table id="people-table" data-people-data-url="/api/people"><thead><tr><th>Name</th></tr></thead><tbody><tr><td>A</td></tr></tbody></table>
            <div id="people-controls"></div>
            <div id="people-searchbuilder-panel"></div>
            <input id="people-name-search" />
        `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default({ dataUrl: "/api/people" });

        const opts = DataTableFn.mock.calls[0]?.[0];
        expect(opts.serverSide).toBe(true);
        expect(opts.ajax.url).toBe("/api/people");

        // Simulate initComplete for name filter binding
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            search: jest.fn().mockReturnThis(),
            table: jest.fn().mockReturnValue({
                node: jest
                    .fn()
                    .mockReturnValue(document.querySelector("#people-table")),
            }),
        };
        const context = { api: jest.fn().mockReturnValue(apiMock) };
        opts.initComplete.call(context);

        // Simulate typing in the search input
        const input = document.querySelector("#people-name-search");
        input.value = "Smith";
        input.dispatchEvent(new Event("input"));

        expect(apiMock.search).toHaveBeenCalledWith("Smith");
        expect(apiMock.draw).toHaveBeenCalled();
    });

    test("client-side mode uses custom filter function", async () => {
        document.body.innerHTML = `
            <table id="people-table">
                <thead><tr><th>Name</th></tr></thead>
                <tbody>
                    <tr data-person-search="John Smith"><td>John Smith</td></tr>
                    <tr data-person-search="Jane Doe"><td>Jane Doe</td></tr>
                </tbody>
            </table>
            <div id="people-controls"></div>
            <div id="people-searchbuilder-panel"></div>
            <input id="people-name-search" />
        `;
        const { dtInstance: _dt } = setupMocks();
        const filters = window.$.fn.dataTable.ext.search;

        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        const tableNode = document.querySelector("#people-table");
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            table: jest.fn().mockReturnValue({
                node: jest.fn().mockReturnValue(tableNode),
            }),
        };
        const context = { api: jest.fn().mockReturnValue(apiMock) };

        // Trigger initComplete
        opts.initComplete.call(context);

        // A filter function should have been pushed
        expect(filters.length).toBeGreaterThan(0);
        const filterFn = filters[filters.length - 1];

        // Test: empty query passes all rows
        const input = document.querySelector("#people-name-search");
        input.value = "";
        expect(
            filterFn(
                {
                    nTable: tableNode,
                    aoData: [{ nTr: tableNode.querySelector("tbody tr") }],
                },
                ["John Smith"],
                0,
            ),
        ).toBe(true);

        // Test: matching query
        input.value = "john";
        expect(
            filterFn(
                {
                    nTable: tableNode,
                    aoData: [{ nTr: tableNode.querySelector("tbody tr") }],
                },
                ["John Smith"],
                0,
            ),
        ).toBe(true);

        // Test: non-matching query
        input.value = "zzz";
        expect(
            filterFn(
                {
                    nTable: tableNode,
                    aoData: [
                        {
                            nTr: tableNode.querySelectorAll("tbody tr")[1],
                        },
                    ],
                },
                ["Jane Doe"],
                0,
            ),
        ).toBe(false);

        // Test: different table should pass
        expect(
            filterFn(
                {
                    nTable: document.createElement("table"),
                    aoData: [],
                },
                [],
                0,
            ),
        ).toBe(true);
    });

    test("drawCallback re-binds name filter", async () => {
        document.body.innerHTML = `
            <table id="people-table"><thead><tr><th>Name</th></tr></thead><tbody><tr><td>A</td></tr></tbody></table>
            <input id="people-name-search" />
        `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        expect(opts.drawCallback).toBeDefined();

        // Call drawCallback
        const apiMock = {
            draw: jest.fn(),
            table: jest.fn().mockReturnValue({
                node: jest
                    .fn()
                    .mockReturnValue(document.querySelector("#people-table")),
            }),
            api: jest.fn(),
        };
        apiMock.api.mockReturnValue(apiMock);
        opts.drawCallback.call(apiMock);
    });

    test("destroyExisting handles SearchBuilder with dom.container", async () => {
        document.body.innerHTML = `
            <table id="people-table"><thead><tr><th>Name</th></tr></thead><tbody><tr><td>A</td></tr></tbody></table>
            <div id="people-controls"><button id="people-filter-btn">F</button></div>
            <div id="people-searchbuilder-panel"></div>
        `;
        setupMocks();
        DataTableFn.isDataTable.mockReturnValue(true);
        const mod = await import("../modules/people-index-init.js");

        // First call creates table + sb
        mod.default();

        // Second call should destroy existing
        DataTableFn.isDataTable.mockReturnValue(true);
        mod.default();

        expect(dtInstance.destroy).toHaveBeenCalled();
    });

    test("DataTable construction error returns null", async () => {
        document.body.innerHTML = `
            <table id="people-table"><thead><tr><th>Name</th></tr></thead><tbody><tr><td>A</td></tr></tbody></table>
        `;
        setupMocks();
        DataTableFn.mockImplementation(() => {
            throw new Error("DT error");
        });
        const debugSpy = jest
            .spyOn(console, "debug")
            .mockImplementation(() => {});
        const mod = await import("../modules/people-index-init.js");
        const result = mod.default();
        expect(result).toEqual({ sb: null, table: null });
        expect(debugSpy).toHaveBeenCalled();
    });

    test("SearchBuilder creation error shows fallback", async () => {
        document.body.innerHTML = `
            <table id="people-table"><thead><tr><th>Name</th></tr></thead><tbody><tr><td>A</td></tr></tbody></table>
            <div id="people-controls"></div>
            <div id="people-searchbuilder-panel"></div>
        `;
        const { SBConstructor } = setupMocks();
        SBConstructor.mockImplementation(() => {
            throw new Error("SB error");
        });
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        const context = {
            api: jest.fn().mockReturnValue({
                columns: {
                    adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
                },
                draw: jest.fn(),
                table: jest.fn().mockReturnValue({ node: jest.fn() }),
            }),
        };
        opts.initComplete.call(context);
    });

    test("setupNameFilter with no search input does nothing", async () => {
        document.body.innerHTML = `
            <table id="people-table"><thead><tr><th>Name</th></tr></thead><tbody><tr><td>A</td></tr></tbody></table>
        `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default({ searchInputSelector: "#nonexistent" });

        const opts = DataTableFn.mock.calls[0]?.[0];
        const context = {
            api: jest.fn().mockReturnValue({
                columns: {
                    adjust: jest.fn().mockReturnValue({ draw: jest.fn() }),
                },
                draw: jest.fn(),
                table: jest.fn().mockReturnValue({
                    node: jest.fn().mockReturnValue(null),
                }),
            }),
        };
        opts.initComplete.call(context);
    });

    test("custom options are merged", async () => {
        document.body.innerHTML = `
            <table id="people-table"><thead><tr><th>Name</th></tr></thead><tbody><tr><td>A</td></tr></tbody></table>
        `;
        setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default({ dataTableOptions: { pageLength: 25 } });

        const opts = DataTableFn.mock.calls[0]?.[0];
        expect(opts.pageLength).toBe(25);
    });

    test("setupSearchBuilder reuses existing sbInstance", async () => {
        document.body.innerHTML = `
            <table id="people-table"><thead><tr><th>Name</th></tr></thead><tbody><tr><td>A</td></tr></tbody></table>
            <div id="people-controls"></div>
            <div id="people-searchbuilder-panel"></div>
        `;
        const { SBConstructor } = setupMocks();
        const mod = await import("../modules/people-index-init.js");
        mod.default();

        const opts = DataTableFn.mock.calls[0]?.[0];
        const apiMock = {
            columns: { adjust: jest.fn().mockReturnValue({ draw: jest.fn() }) },
            draw: jest.fn(),
            table: jest.fn().mockReturnValue({
                node: jest
                    .fn()
                    .mockReturnValue(document.querySelector("#people-table")),
            }),
        };
        const context = { api: jest.fn().mockReturnValue(apiMock) };

        // First call creates SB
        opts.initComplete.call(context);
        expect(SBConstructor).toHaveBeenCalledTimes(1);

        // Second call via drawCallback should reuse
        opts.drawCallback.call(context);
    });
});
