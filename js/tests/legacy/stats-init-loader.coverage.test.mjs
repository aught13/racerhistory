import { jest } from "@jest/globals";

/**
 * Comprehensive branch coverage tests for stats-init-loader.mjs
 */

function setupJQueryMock(_opts = {}) {
    const dtInstance = {
        searchBuilder: {
            container: jest.fn().mockReturnValue({ appendTo: jest.fn() }),
            rebuild: jest.fn(),
        },
        columns: { adjust: jest.fn() },
        on: jest.fn(),
        settings: jest
            .fn()
            .mockReturnValue([{ nScrollHead: null, nScrollBody: null }]),
        destroy: jest.fn(),
    };

    const DataTableFn = jest.fn().mockReturnValue(dtInstance);
    DataTableFn.isDataTable = jest.fn().mockReturnValue(false);
    DataTableFn.SearchBuilder = jest.fn().mockReturnValue({
        container: jest.fn().mockReturnValue({ appendTo: jest.fn() }),
        rebuild: jest.fn(),
    });
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
        };
    });
    jq.fn = { DataTable: DataTableFn, dataTable: DataTableFn };

    window.$ = jq;
    return { dtInstance, DataTableFn, jq };
}

beforeEach(() => {
    jest.resetModules();
    jest.useFakeTimers();
    document.body.innerHTML = "";
    delete window.$;
    jest.spyOn(console, "debug").mockImplementation(() => {});
    jest.spyOn(console, "warn").mockImplementation(() => {});
    jest.spyOn(console, "error").mockImplementation(() => {});
});

afterEach(() => {
    jest.useRealTimers();
    jest.restoreAllMocks();
});

describe("stats-init-loader exports", () => {
    test("initStatsPage does nothing without table or cards", async () => {
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        expect(() => mod.initStatsPage()).not.toThrow();
    });

    test("initStatsDataTable returns early when no ajaxUrl", async () => {
        document.body.innerHTML = `
            <table id="stats-results-table">
                <thead><tr><th>Player</th></tr></thead>
            </table>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        expect(() =>
            mod.initStatsDataTable(
                document.getElementById("stats-results-table"),
            ),
        ).not.toThrow();
    });

    test("initStatsPage calls initStatsDataTable when table exists", async () => {
        document.body.innerHTML = `
            <table id="stats-results-table" data-ajax-url="/stats/api">
                <thead><tr><th>Player</th><th>GP</th><th>PTS</th></tr></thead>
                <tbody></tbody>
            </table>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        mod.initStatsPage();
    });

    test("initStatsDataTable detects numeric column indices from headers", async () => {
        document.body.innerHTML = `
            <table id="stats-results-table" data-ajax-url="/stats/api">
                <thead><tr>
                    <th>Player</th>
                    <th>GP</th>
                    <th>GS</th>
                    <th>PTS</th>
                    <th>AST</th>
                    <th>RB</th>
                    <th>STL</th>
                    <th>BS</th>
                    <th>TRN</th>
                    <th>PF</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        mod.initStatsDataTable(document.getElementById("stats-results-table"));
    });

    test("initStatsDataTable uses default sort when PTS column missing", async () => {
        document.body.innerHTML = `
            <table id="stats-results-table" data-ajax-url="/stats/api">
                <thead><tr><th>Player</th><th>GP</th></tr></thead>
                <tbody></tbody>
            </table>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        mod.initStatsDataTable(document.getElementById("stats-results-table"));
    });

    test("initCardHover adds/removes shadow classes on hover", async () => {
        document.body.innerHTML = `
            <div id="stats-type-cards">
                <div class="stat-type-card">Card 1</div>
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        mod.initCardHover(document.getElementById("stats-type-cards"));

        const card = document.querySelector(".stat-type-card");
        card.dispatchEvent(new MouseEvent("mouseenter"));
        expect(card.classList.contains("shadow-sm")).toBe(true);
        card.dispatchEvent(new MouseEvent("mouseleave"));
        expect(card.classList.contains("shadow-sm")).toBe(false);
    });

    test("initStatsPage initializes cards when they exist", async () => {
        document.body.innerHTML = `
            <div id="stats-type-cards">
                <div class="stat-type-card">Card</div>
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        mod.initStatsPage();
        const card = document.querySelector(".stat-type-card");
        card.dispatchEvent(new MouseEvent("mouseenter"));
        expect(card.classList.contains("shadow-sm")).toBe(true);
    });

    test("initDragScroll handles drag events on container", async () => {
        document.body.innerHTML = `<div class="table-responsive"></div>`;
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        const container = document.querySelector(".table-responsive");
        mod.initDragScroll(container);

        container.dispatchEvent(
            new MouseEvent("mousedown", { bubbles: true, pageX: 100 }),
        );
        expect(container.classList.contains("is-dragging")).toBe(true);

        container.dispatchEvent(
            new MouseEvent("mousemove", { bubbles: true, pageX: 50 }),
        );

        container.dispatchEvent(new MouseEvent("mouseup", { bubbles: true }));
        expect(container.classList.contains("is-dragging")).toBe(false);
    });

    test("initDragScroll handles mouseleave", async () => {
        document.body.innerHTML = `<div class="table-responsive"></div>`;
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        const container = document.querySelector(".table-responsive");
        mod.initDragScroll(container);

        container.dispatchEvent(
            new MouseEvent("mousedown", { bubbles: true, pageX: 100 }),
        );
        container.dispatchEvent(
            new MouseEvent("mouseleave", { bubbles: true }),
        );
        expect(container.classList.contains("is-dragging")).toBe(false);
    });

    test("initDragScroll skips interactive elements", async () => {
        document.body.innerHTML = `
            <div class="table-responsive">
                <a href="#">Link</a>
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        const container = document.querySelector(".table-responsive");
        mod.initDragScroll(container);

        const link = container.querySelector("a");
        link.dispatchEvent(
            new MouseEvent("mousedown", { bubbles: true, pageX: 100 }),
        );
        expect(container.classList.contains("is-dragging")).toBe(false);
    });

    test("initDragScroll does nothing when container is null", async () => {
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        expect(() => mod.initDragScroll(null)).not.toThrow();
    });

    test("mousemove without mousedown does not drag", async () => {
        document.body.innerHTML = `<div class="table-responsive"></div>`;
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        const container = document.querySelector(".table-responsive");
        mod.initDragScroll(container);
        container.dispatchEvent(
            new MouseEvent("mousemove", { bubbles: true, pageX: 80 }),
        );
        expect(container.classList.contains("is-dragging")).toBe(false);
    });

    test("NUMERIC_COLUMNS and SCROLLER_THRESHOLD are exported", async () => {
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        expect(mod.NUMERIC_COLUMNS).toContain("PTS");
        expect(mod.NUMERIC_COLUMNS).toContain("GP");
        expect(mod.SCROLLER_THRESHOLD).toBe(75);
    });

    test("fixScrollXHeaderAlignment returns early with null scroll containers", async () => {
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        const dt = {
            settings: () => [{ nScrollHead: null, nScrollBody: null }],
        };
        expect(() => mod.fixScrollXHeaderAlignment(dt)).not.toThrow();
    });

    test("fixScrollXHeaderAlignment returns early with undefined settings entry", async () => {
        setupJQueryMock();
        const mod = await import("../../legacy/stats-init-loader.mjs");
        const dt = { settings: () => [undefined] };
        expect(() => mod.fixScrollXHeaderAlignment(dt)).not.toThrow();
    });

    test("fixScrollXHeaderAlignment sets header th widths to match body td widths", async () => {
        setupJQueryMock();
        document.body.innerHTML = `
            <div class="dataTables_scrollHead">
                <div class="dataTables_scrollHeadInner">
                    <table>
                        <thead><tr><th>Player</th><th>GP</th></tr></thead>
                    </table>
                </div>
            </div>
            <div class="dataTables_scrollBody">
                <table>
                    <tbody><tr><td>Ja Morant</td><td>33</td></tr></tbody>
                </table>
            </div>
        `;
        const scrollHead = document.querySelector(".dataTables_scrollHead");
        const scrollBody = document.querySelector(".dataTables_scrollBody");
        const mod = await import("../../legacy/stats-init-loader.mjs");
        const dt = {
            settings: () => [
                { nScrollHead: scrollHead, nScrollBody: scrollBody },
            ],
        };
        mod.fixScrollXHeaderAlignment(dt);
        const headThs = scrollHead.querySelectorAll("thead th");
        expect(headThs[0].style.boxSizing).toBe("border-box");
        expect(headThs[0].style.width).toBe("0px"); // JSDOM returns 0 from getBoundingClientRect
        const headTable = scrollHead.querySelector(
            ".dataTables_scrollHeadInner table",
        );
        expect(headTable.style.tableLayout).toBe("fixed");
    });

    test("fixScrollXHeaderAlignment returns early when body has no rows", async () => {
        setupJQueryMock();
        document.body.innerHTML = `
            <div class="dataTables_scrollHead">
                <div class="dataTables_scrollHeadInner">
                    <table><thead><tr><th>Player</th></tr></thead></table>
                </div>
            </div>
            <div class="dataTables_scrollBody">
                <table><tbody></tbody></table>
            </div>
        `;
        const scrollHead = document.querySelector(".dataTables_scrollHead");
        const scrollBody = document.querySelector(".dataTables_scrollBody");
        const mod = await import("../../legacy/stats-init-loader.mjs");
        const dt = {
            settings: () => [
                { nScrollHead: scrollHead, nScrollBody: scrollBody },
            ],
        };
        expect(() => mod.fixScrollXHeaderAlignment(dt)).not.toThrow();
        const headTable = scrollHead.querySelector(
            ".dataTables_scrollHeadInner table",
        );
        expect(headTable.style.tableLayout).toBe("");
    });

    test("fixScrollXHeaderAlignment returns early when column counts differ", async () => {
        setupJQueryMock();
        document.body.innerHTML = `
            <div class="dataTables_scrollHead">
                <div class="dataTables_scrollHeadInner">
                    <table><thead><tr><th>Player</th><th>GP</th></tr></thead></table>
                </div>
            </div>
            <div class="dataTables_scrollBody">
                <table>
                    <tbody><tr><td>Only One Cell</td></tr></tbody>
                </table>
            </div>
        `;
        const scrollHead = document.querySelector(".dataTables_scrollHead");
        const scrollBody = document.querySelector(".dataTables_scrollBody");
        const mod = await import("../../legacy/stats-init-loader.mjs");
        const dt = {
            settings: () => [
                { nScrollHead: scrollHead, nScrollBody: scrollBody },
            ],
        };
        expect(() => mod.fixScrollXHeaderAlignment(dt)).not.toThrow();
        const headTable = scrollHead.querySelector(
            ".dataTables_scrollHeadInner table",
        );
        expect(headTable.style.tableLayout).toBe("");
    });
});
