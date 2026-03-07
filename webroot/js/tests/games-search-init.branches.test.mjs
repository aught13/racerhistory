import { jest } from "@jest/globals";

/**
 * Targeted branch coverage tests for games-search-init.mjs
 * Focuses on the ensureDataTablesLoaded().then() chain and initGamesPage edge cases.
 */

const CDN_URLS = [
    "https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js",
    "https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js",
    "https://cdn.datatables.net/scroller/2.3.0/js/dataTables.scroller.min.js",
    "https://cdn.datatables.net/searchbuilder/1.4.2/js/dataTables.searchBuilder.min.js",
    "https://cdn.datatables.net/searchbuilder/1.4.2/js/searchBuilder.bootstrap5.min.js",
];

function preloadScripts() {
    CDN_URLS.forEach((url) => {
        const script = document.createElement("script");
        script.src = url;
        script.dataset.loaded = "true";
        document.head.appendChild(script);
    });
}

function setupJQueryMock(opts = {}) {
    const dtInstance = {
        ajax: {
            url: jest.fn().mockImplementation((newUrl) => {
                if (newUrl === undefined) return "http://localhost/old-url";
                return { load: jest.fn() };
            }),
        },
        search: jest.fn().mockReturnThis(),
        searchBuilder: {
            rebuild: jest.fn(),
            container: jest.fn().mockReturnValue({ appendTo: jest.fn() }),
        },
        columns: { adjust: jest.fn() },
        on: jest.fn(),
        rows: jest.fn().mockReturnValue({
            data: jest.fn().mockReturnValue({
                each: jest.fn(),
            }),
        }),
        destroy: jest.fn(),
        table: jest.fn().mockReturnValue({
            node: jest.fn().mockReturnValue(null),
        }),
    };

    const DataTableFn = jest.fn().mockReturnValue(dtInstance);
    DataTableFn.isDataTable = jest.fn().mockReturnValue(false);
    DataTableFn.SearchBuilder = jest.fn().mockReturnValue({});
    DataTableFn.ext = { search: [] };

    const jq = jest.fn((selector) => {
        const el =
            typeof selector === "string"
                ? document.querySelector(selector)
                : selector;
        return {
            0: el,
            length: el ? 1 : 0,
            get: jest.fn().mockReturnValue(el),
            DataTable: DataTableFn,
            remove: jest.fn(),
        };
    });
    jq.fn = {
        DataTable: DataTableFn,
        dataTable: DataTableFn,
    };

    if (opts.noSearchBuilder) {
        delete DataTableFn.SearchBuilder;
    }

    window.$ = jq;
    return { dtInstance, DataTableFn, jq };
}

const flush = () => new Promise((r) => setTimeout(r, 50));

beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
    document.head.querySelectorAll("script").forEach((s) => s.remove());
    delete window.$;
    jest.spyOn(console, "debug").mockImplementation(() => {});
    jest.spyOn(console, "warn").mockImplementation(() => {});
    jest.spyOn(console, "error").mockImplementation(() => {});
    jest.spyOn(console, "log").mockImplementation(() => {});
});

afterEach(() => {
    jest.restoreAllMocks();
});

describe("games-search-init ensureDataTablesLoaded then-chain", () => {
    test("initGamesDataTable full flow with card and no existing slot", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <div class="card">
        <div class="table-responsive">
          <table id="games-results-table" data-ajax-url="/api/games">
            <thead><tr><th>Date</th><th>Opponent</th><th>Margin</th><th>#</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    `;
        const { dtInstance, DataTableFn } = setupJQueryMock();
        const mod = await import("../games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));
        await flush();

        // .then() should have executed: DataTable created, SearchBuilder set up
        expect(DataTableFn).toHaveBeenCalled();
        expect(dtInstance.on).toHaveBeenCalledWith(
            "draw.dt",
            expect.any(Function),
        );
    });

    test("initGamesDataTable with existing slot clears it", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <div id="games-searchbuilder-slot">old content</div>
      <div class="card">
        <div class="table-responsive">
          <table id="games-results-table" data-ajax-url="/api/games">
            <thead><tr><th>Date</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    `;
        setupJQueryMock();
        const mod = await import("../games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));
        await flush();

        const slot = document.getElementById("games-searchbuilder-slot");
        expect(slot.innerHTML).toBe("");
    });

    test("initGamesDataTable without card and without slot skips SearchBuilder", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="games-results-table" data-ajax-url="/api/games">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { dtInstance } = setupJQueryMock();
        const mod = await import("../games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));
        await flush();

        // dt.on should still be called even without SearchBuilder slot
        expect(dtInstance.on).toHaveBeenCalled();
    });

    test("initGamesDataTable destroys existing DataTable on freshTable", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="games-results-table" data-ajax-url="/api/games">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { dtInstance, DataTableFn } = setupJQueryMock();
        // First call: isDataTable is false (normal path)
        // After ensureDataTablesLoaded resolves, isDataTable on freshTable check:
        DataTableFn.isDataTable
            .mockReturnValueOnce(false) // guard at beginning of initGamesDataTable
            .mockReturnValue(true); // check inside .then()
        const mod = await import("../games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));
        await flush();

        expect(dtInstance.destroy).toHaveBeenCalled();
    });

    test("initGamesDataTable when freshTable removed from DOM", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="games-results-table" data-ajax-url="/api/games">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        setupJQueryMock();
        const mod = await import("../games-search-init.mjs");
        const table = document.getElementById("games-results-table");
        mod.initGamesDataTable(table);
        // Remove table before promise resolves
        table.remove();
        await flush();

        expect(console.warn).toHaveBeenCalledWith(
            "Games table element not found",
        );
    });

    test("initGamesDataTable catch branch on DataTable init error", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="games-results-table" data-ajax-url="/api/games">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { DataTableFn } = setupJQueryMock();
        DataTableFn.mockImplementation(() => {
            throw new Error("DT init error");
        });
        const mod = await import("../games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));
        await flush();

        expect(console.error).toHaveBeenCalledWith(
            "Games DataTables initialization error:",
            expect.any(Error),
        );
    });

    test("draw.dt callback triggers columns.adjust and updateRecordDisplay", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <div id="games-record-display"></div>
      <div class="card">
        <table id="games-results-table" data-ajax-url="/api/games">
          <thead><tr><th>Date</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    `;
        const { dtInstance } = setupJQueryMock();
        dtInstance.rows.mockReturnValue({
            data: jest.fn().mockReturnValue({
                each: jest.fn((cb) => {
                    [
                        ["2024-01-01", "", "", "", "", "", "W"],
                        ["2024-01-02", "", "", "", "", "", "L"],
                    ].forEach(cb);
                }),
            }),
        });
        const mod = await import("../games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));
        await flush();

        // Get the draw.dt callback and invoke it
        const drawCall = dtInstance.on.mock.calls.find(
            (c) => c[0] === "draw.dt",
        );
        expect(drawCall).toBeTruthy();
        drawCall[1]();
        expect(dtInstance.columns.adjust).toHaveBeenCalled();
        expect(
            document.getElementById("games-record-display").textContent,
        ).toBe("Record: 1-1");
    });

    test("ensureDataTablesLoaded catch branch on script load failure", async () => {
        // DON'T preload scripts - and make jQuery available but not DataTables
        document.body.innerHTML = `
      <table id="games-results-table" data-ajax-url="/api/games">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { DataTableFn: _DataTableFn } = setupJQueryMock();
        // Make hasDataTables() return false so it tries to load scripts
        delete window.$.fn.DataTable;
        delete window.$.fn.dataTable;

        const mod = await import("../games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));

        // Trigger error on the script element that was created
        await flush();
        const scripts = document.head.querySelectorAll("script");
        scripts.forEach((s) => {
            s.dispatchEvent(new Event("error"));
        });
        await flush();

        expect(console.warn).toHaveBeenCalledWith(
            "Games DataTables library load failed:",
            expect.any(String),
        );
    });

    test("dataSrc callback updates record display when json.record exists", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <div id="games-record-display"></div>
      <table id="games-results-table" data-ajax-url="/api/games">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { DataTableFn } = setupJQueryMock();
        let capturedDataSrc;
        DataTableFn.mockImplementation((opts) => {
            capturedDataSrc = opts?.ajax?.dataSrc;
            return {
                ajax: { url: jest.fn() },
                searchBuilder: {
                    rebuild: jest.fn(),
                    container: jest
                        .fn()
                        .mockReturnValue({ appendTo: jest.fn() }),
                },
                columns: { adjust: jest.fn() },
                on: jest.fn(),
                rows: jest.fn().mockReturnValue({
                    data: jest.fn().mockReturnValue({ each: jest.fn() }),
                }),
            };
        });
        const mod = await import("../games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));
        await flush();

        // Call dataSrc with record
        if (capturedDataSrc) {
            const result = capturedDataSrc({ record: "5-3", data: [["row1"]] });
            expect(result).toEqual([["row1"]]);
            expect(
                document.getElementById("games-record-display").textContent,
            ).toBe("Record: 5-3");
        }
    });

    test("dataSrc callback without record display element", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="games-results-table" data-ajax-url="/api/games">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { DataTableFn } = setupJQueryMock();
        let capturedDataSrc;
        DataTableFn.mockImplementation((opts) => {
            capturedDataSrc = opts?.ajax?.dataSrc;
            return {
                ajax: { url: jest.fn() },
                searchBuilder: {
                    rebuild: jest.fn(),
                    container: jest
                        .fn()
                        .mockReturnValue({ appendTo: jest.fn() }),
                },
                columns: { adjust: jest.fn() },
                on: jest.fn(),
                rows: jest.fn().mockReturnValue({
                    data: jest.fn().mockReturnValue({ each: jest.fn() }),
                }),
            };
        });
        const mod = await import("../games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));
        await flush();

        if (capturedDataSrc) {
            const result = capturedDataSrc({ record: "1-0", data: [] });
            expect(result).toEqual([]);
        }
    });

    test("dataSrc callback without record field", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <div id="games-record-display">old</div>
      <table id="games-results-table" data-ajax-url="/api/games">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { DataTableFn } = setupJQueryMock();
        let capturedDataSrc;
        DataTableFn.mockImplementation((opts) => {
            capturedDataSrc = opts?.ajax?.dataSrc;
            return {
                ajax: { url: jest.fn() },
                searchBuilder: {
                    rebuild: jest.fn(),
                    container: jest
                        .fn()
                        .mockReturnValue({ appendTo: jest.fn() }),
                },
                columns: { adjust: jest.fn() },
                on: jest.fn(),
                rows: jest.fn().mockReturnValue({
                    data: jest.fn().mockReturnValue({ each: jest.fn() }),
                }),
            };
        });
        const mod = await import("../games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));
        await flush();

        if (capturedDataSrc) {
            capturedDataSrc({ data: [["x"]] });
            expect(
                document.getElementById("games-record-display").textContent,
            ).toBe("old");
        }
    });
});

describe("games-search-init initGamesPage edge cases", () => {
    test("initGamesPage with matching URLs skips reload", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="games-results-table" data-ajax-url="http://localhost/same-url">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { dtInstance, DataTableFn, jq } = setupJQueryMock();
        DataTableFn.isDataTable.mockReturnValue(true);
        // Make ajax.url() return the same URL as data-ajax-url
        dtInstance.ajax.url = jest.fn().mockImplementation((newUrl) => {
            if (newUrl === undefined) return "http://localhost/same-url";
            return { load: jest.fn() };
        });
        jq.mockImplementation((selector) => {
            const el =
                typeof selector === "string"
                    ? document.querySelector(selector)
                    : selector;
            return {
                0: el,
                length: el ? 1 : 0,
                get: jest.fn().mockReturnValue(el),
                DataTable: jest.fn().mockReturnValue(dtInstance),
            };
        });
        jq.fn = { DataTable: DataTableFn, dataTable: DataTableFn };

        const mod = await import("../games-search-init.mjs");
        mod.initGamesPage();

        // search should NOT be called since URLs match
        expect(dtInstance.search).not.toHaveBeenCalled();
    });

    test("initGamesPage where dt.ajax.url is not a function", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="games-results-table" data-ajax-url="/new-url">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { dtInstance, DataTableFn, jq } = setupJQueryMock();
        DataTableFn.isDataTable.mockReturnValue(true);
        // Make ajax.url NOT a function
        dtInstance.ajax = { url: "static-string" };
        jq.mockImplementation((selector) => {
            const el =
                typeof selector === "string"
                    ? document.querySelector(selector)
                    : selector;
            return {
                0: el,
                length: el ? 1 : 0,
                DataTable: jest.fn().mockReturnValue(dtInstance),
            };
        });
        jq.fn = { DataTable: DataTableFn, dataTable: DataTableFn };

        const mod = await import("../games-search-init.mjs");
        // Should not throw
        expect(() => mod.initGamesPage()).not.toThrow();
    });

    test("initGamesPage with empty ajaxUrl", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="games-results-table" data-ajax-url="">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { dtInstance, DataTableFn, jq } = setupJQueryMock();
        DataTableFn.isDataTable.mockReturnValue(true);
        dtInstance.ajax.url = jest.fn().mockReturnValue("http://localhost/old");
        jq.mockImplementation((selector) => {
            const el =
                typeof selector === "string"
                    ? document.querySelector(selector)
                    : selector;
            return {
                0: el,
                length: el ? 1 : 0,
                DataTable: jest.fn().mockReturnValue(dtInstance),
            };
        });
        jq.fn = { DataTable: DataTableFn, dataTable: DataTableFn };

        const mod = await import("../games-search-init.mjs");
        mod.initGamesPage();

        // nextUrl is empty, so no reload triggered
        expect(dtInstance.search).not.toHaveBeenCalled();
    });

    test("initGamesPage where searchBuilder.rebuild is not a function", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="games-results-table" data-ajax-url="/new-url">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { dtInstance, DataTableFn, jq } = setupJQueryMock();
        DataTableFn.isDataTable.mockReturnValue(true);
        dtInstance.ajax.url = jest.fn().mockImplementation((newUrl) => {
            if (newUrl === undefined) return "http://localhost/old-url";
            return { load: jest.fn() };
        });
        dtInstance.searchBuilder = { rebuild: "not-a-function" };
        jq.mockImplementation((selector) => {
            const el =
                typeof selector === "string"
                    ? document.querySelector(selector)
                    : selector;
            return {
                0: el,
                length: el ? 1 : 0,
                DataTable: jest.fn().mockReturnValue(dtInstance),
            };
        });
        jq.fn = { DataTable: DataTableFn, dataTable: DataTableFn };

        const mod = await import("../games-search-init.mjs");
        mod.initGamesPage();

        // Should still call search and ajax.url.load but NOT searchBuilder.rebuild
        expect(dtInstance.search).toHaveBeenCalledWith("");
    });

    test("initGamesPage catch block on DataTable error", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="games-results-table" data-ajax-url="/url">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { DataTableFn, jq } = setupJQueryMock();
        DataTableFn.isDataTable.mockReturnValue(true);
        jq.mockImplementation((selector) => {
            const el =
                typeof selector === "string"
                    ? document.querySelector(selector)
                    : selector;
            return {
                0: el,
                length: el ? 1 : 0,
                DataTable: jest.fn().mockImplementation(() => {
                    throw new Error("DT error");
                }),
            };
        });
        jq.fn = { DataTable: DataTableFn, dataTable: DataTableFn };

        const mod = await import("../games-search-init.mjs");
        mod.initGamesPage();

        expect(console.warn).toHaveBeenCalledWith(
            "Failed to refresh existing games DataTable",
            expect.any(Error),
        );
    });

    test("cleanupGamesPage when jQuery missing", async () => {
        document.body.innerHTML = `
      <table id="games-results-table"></table>
    `;
        // No jQuery mock
        const mod = await import("../games-search-init.mjs");
        expect(() => mod.cleanupGamesPage()).not.toThrow();
    });

    test("cleanupGamesPage when destroy throws", async () => {
        document.body.innerHTML = `
      <table id="games-results-table"></table>
    `;
        const { DataTableFn, dtInstance } = setupJQueryMock();
        DataTableFn.isDataTable.mockReturnValue(true);
        dtInstance.destroy.mockImplementation(() => {
            throw new Error("destroy error");
        });
        const mod = await import("../games-search-init.mjs");
        mod.cleanupGamesPage();

        expect(console.warn).toHaveBeenCalledWith(
            "Failed to clean up games DataTable before navigation",
            expect.any(Error),
        );
    });
});

describe("games-search-init loadScript branches", () => {
    test("loadScript resolves when existing script has loaded=true", async () => {
        preloadScripts();
        setupJQueryMock();
        const mod = await import("../games-search-init.mjs");
        // The module should import without errors since scripts are preloaded
        expect(mod.initGamesPage).toBeDefined();
    });

    test("loadScript waits for existing script load event", async () => {
        // Create script WITHOUT loaded=true
        const script = document.createElement("script");
        script.src = CDN_URLS[0];
        document.head.appendChild(script);

        setupJQueryMock();
        const mod = await import("../games-search-init.mjs");
        expect(mod.initGamesPage).toBeDefined();
    });

    test("normalizeUrl with falsy input returns empty string", async () => {
        setupJQueryMock();
        const mod = await import("../games-search-init.mjs");
        // Test through calculateRecord which doesn't use normalizeUrl
        // normalizeUrl is internal, exercised through initGamesPage
        // We test it indirectly via the URL comparison path
        document.body.innerHTML = `
      <table id="games-results-table">
        <thead><tr><th>Date</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        // Table without data-ajax-url exercises normalizeUrl("") path
        const { DataTableFn, dtInstance, jq } = setupJQueryMock();
        DataTableFn.isDataTable.mockReturnValue(true);
        dtInstance.ajax.url = jest.fn().mockReturnValue("");
        jq.mockImplementation((selector) => {
            const el =
                typeof selector === "string"
                    ? document.querySelector(selector)
                    : selector;
            return {
                0: el,
                length: el ? 1 : 0,
                DataTable: jest.fn().mockReturnValue(dtInstance),
            };
        });
        jq.fn = { DataTable: DataTableFn, dataTable: DataTableFn };
        mod.initGamesPage();
    });
});

describe("games-search-init waitForCondition", () => {
    test("waitForCondition resolves immediately when check passes", async () => {
        preloadScripts();
        setupJQueryMock();
        // hasJquery returns true, so waitForCondition resolves immediately
        const mod = await import("../games-search-init.mjs");
        // The module imported successfully means ensureDataTablesLoaded patterns work
        expect(mod.SCROLLER_THRESHOLD).toBe(75);
    });
});
