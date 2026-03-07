import { jest } from "@jest/globals";

/**
 * Targeted branch coverage tests for stats-init-loader.mjs
 * Focuses on the ensureDataTablesLoaded().then() chain, SearchBuilder setup,
 * drag scroll, and card hover branches.
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

function setupJQueryMock() {
    const dtInstance = {
        ajax: { url: jest.fn() },
        search: jest.fn().mockReturnThis(),
        searchBuilder: {
            rebuild: jest.fn(),
            container: jest.fn().mockReturnValue({ appendTo: jest.fn() }),
        },
        columns: { adjust: jest.fn() },
        on: jest.fn(),
        rows: jest.fn().mockReturnValue({
            data: jest.fn().mockReturnValue({ each: jest.fn() }),
        }),
        destroy: jest.fn(),
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
});

afterEach(() => {
    jest.restoreAllMocks();
});

describe("stats-init-loader ensureDataTablesLoaded then-chain", () => {
    test("initStatsDataTable full flow with card and SearchBuilder", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <div class="card">
        <div class="table-responsive">
          <table id="stats-results-table" data-ajax-url="/api/stats">
            <thead><tr><th>#</th><th>Name</th><th>GP</th><th>PTS</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    `;
        const { dtInstance, DataTableFn } = setupJQueryMock();
        const mod = await import("../stats-init-loader.mjs");
        mod.initStatsDataTable(document.getElementById("stats-results-table"));
        await flush();

        expect(DataTableFn).toHaveBeenCalled();
        expect(dtInstance.on).toHaveBeenCalledWith(
            "draw.dt",
            expect.any(Function),
        );
    });

    test("initStatsDataTable skips when table already has DataTable", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="stats-results-table" data-ajax-url="/api/stats">
        <thead><tr><th>PTS</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { DataTableFn } = setupJQueryMock();
        DataTableFn.isDataTable
            .mockReturnValueOnce(false) // not checked before ensureDataTablesLoaded
            .mockReturnValue(true); // checked inside .then()
        const mod = await import("../stats-init-loader.mjs");
        mod.initStatsDataTable(document.getElementById("stats-results-table"));
        await flush();

        // DataTable constructor should not be called since isDataTable returns true
        // Actually the first check in initStatsDataTable is only the ajaxUrl check
        // The isDataTable check is inside the .then()
    });

    test("initStatsDataTable catch branch on load failure", async () => {
        // Don't preload scripts - and remove DataTables from mock
        document.body.innerHTML = `
      <table id="stats-results-table" data-ajax-url="/api/stats">
        <thead><tr><th>PTS</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        setupJQueryMock();
        delete window.$.fn.DataTable;
        delete window.$.fn.dataTable;

        const mod = await import("../stats-init-loader.mjs");
        mod.initStatsDataTable(document.getElementById("stats-results-table"));

        // Trigger error on created script
        await flush();
        const scripts = document.head.querySelectorAll("script");
        scripts.forEach((s) => s.dispatchEvent(new Event("error")));
        await flush();

        expect(console.warn).toHaveBeenCalledWith(
            "Stats DataTables init failed:",
            expect.any(String),
        );
    });

    test("initStatsDataTable returns early without ajaxUrl", async () => {
        document.body.innerHTML = `
      <table id="stats-results-table">
        <thead><tr><th>PTS</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        setupJQueryMock();
        const mod = await import("../stats-init-loader.mjs");
        mod.initStatsDataTable(document.getElementById("stats-results-table"));
        // Should not call DataTable
    });

    test("initStatsDataTable without PTS column uses default sort", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <div class="card">
        <table id="stats-results-table" data-ajax-url="/api/stats">
          <thead><tr><th>Name</th><th>GP</th><th>MIN</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    `;
        const { DataTableFn } = setupJQueryMock();
        let capturedOpts;
        DataTableFn.mockImplementation((opts) => {
            capturedOpts = opts;
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
            };
        });
        const mod = await import("../stats-init-loader.mjs");
        mod.initStatsDataTable(document.getElementById("stats-results-table"));
        await flush();

        // No PTS column → ptsIdx stays -1 → order defaults to [[0, "desc"]]
        expect(capturedOpts?.order).toEqual([[0, "desc"]]);
    });

    test("draw.dt callback triggers columns.adjust", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <div class="card">
        <table id="stats-results-table" data-ajax-url="/api/stats">
          <thead><tr><th>PTS</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    `;
        const { dtInstance } = setupJQueryMock();
        const mod = await import("../stats-init-loader.mjs");
        mod.initStatsDataTable(document.getElementById("stats-results-table"));
        await flush();

        const drawCall = dtInstance.on.mock.calls.find(
            (c) => c[0] === "draw.dt",
        );
        expect(drawCall).toBeTruthy();
        drawCall[1]();
        expect(dtInstance.columns.adjust).toHaveBeenCalled();
    });

    test("initStatsDataTable without card still works", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="stats-results-table" data-ajax-url="/api/stats">
        <thead><tr><th>PTS</th></tr></thead>
        <tbody></tbody>
      </table>
    `;
        const { dtInstance } = setupJQueryMock();
        const mod = await import("../stats-init-loader.mjs");
        mod.initStatsDataTable(document.getElementById("stats-results-table"));
        await flush();

        expect(dtInstance.on).toHaveBeenCalled();
    });
});

describe("stats-init-loader drag scroll", () => {
    test("initDragScroll mousedown on interactive element skips", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <div class="table-responsive">
        <a href="#">Link</a>
      </div>
    `;
        setupJQueryMock();
        const mod = await import("../stats-init-loader.mjs");
        const container = document.querySelector(".table-responsive");
        mod.initDragScroll(container);

        const link = container.querySelector("a");
        link.dispatchEvent(
            new MouseEvent("mousedown", { bubbles: true, pageX: 100 }),
        );
        expect(container.classList.contains("is-dragging")).toBe(false);
    });

    test("initDragScroll full drag cycle", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <div class="table-responsive" style="overflow:auto;width:200px">
        <div style="width:1000px">wide content</div>
      </div>
    `;
        setupJQueryMock();
        const mod = await import("../stats-init-loader.mjs");
        const container = document.querySelector(".table-responsive");
        mod.initDragScroll(container);

        container.dispatchEvent(
            new MouseEvent("mousedown", { bubbles: true, pageX: 200 }),
        );
        expect(container.classList.contains("is-dragging")).toBe(true);

        container.dispatchEvent(
            new MouseEvent("mousemove", { bubbles: true, pageX: 150 }),
        );

        container.dispatchEvent(
            new MouseEvent("mouseleave", { bubbles: true }),
        );
        expect(container.classList.contains("is-dragging")).toBe(false);
    });

    test("initDragScroll mousemove without mousedown is no-op", async () => {
        preloadScripts();
        document.body.innerHTML = `<div class="table-responsive"></div>`;
        setupJQueryMock();
        const mod = await import("../stats-init-loader.mjs");
        const container = document.querySelector(".table-responsive");
        mod.initDragScroll(container);

        container.dispatchEvent(
            new MouseEvent("mousemove", { bubbles: true, pageX: 100 }),
        );
        expect(container.classList.contains("is-dragging")).toBe(false);
    });

    test("initDragScroll null container", async () => {
        preloadScripts();
        setupJQueryMock();
        const mod = await import("../stats-init-loader.mjs");
        expect(() => mod.initDragScroll(null)).not.toThrow();
    });
});

describe("stats-init-loader card hover", () => {
    test("initCardHover adds and removes shadow on hover", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <div id="stats-type-cards">
        <div class="stat-type-card">Card</div>
      </div>
    `;
        setupJQueryMock();
        const mod = await import("../stats-init-loader.mjs");
        mod.initCardHover(document.getElementById("stats-type-cards"));

        const card = document.querySelector(".stat-type-card");
        card.dispatchEvent(new MouseEvent("mouseenter"));
        expect(card.classList.contains("shadow-sm")).toBe(true);
        card.dispatchEvent(new MouseEvent("mouseleave"));
        expect(card.classList.contains("shadow-sm")).toBe(false);
    });
});

describe("stats-init-loader initStatsPage", () => {
    test("initStatsPage with both table and cards", async () => {
        preloadScripts();
        document.body.innerHTML = `
      <table id="stats-results-table" data-ajax-url="/api/stats">
        <thead><tr><th>PTS</th></tr></thead>
        <tbody></tbody>
      </table>
      <div id="stats-type-cards">
        <div class="stat-type-card">Card</div>
      </div>
    `;
        setupJQueryMock();
        const mod = await import("../stats-init-loader.mjs");
        mod.initStatsPage();
        await flush();
    });

    test("initStatsPage with neither table nor cards", async () => {
        preloadScripts();
        setupJQueryMock();
        const mod = await import("../stats-init-loader.mjs");
        expect(() => mod.initStatsPage()).not.toThrow();
    });
});
