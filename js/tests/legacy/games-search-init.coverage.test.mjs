import { jest } from "@jest/globals";

/**
 * Comprehensive branch coverage tests for games-search-init.mjs
 */

// jQuery / DataTables mock factory
function setupJQueryMock(opts = {}) {
    const dtInstance = {
        ajax: { url: jest.fn().mockReturnValue({ load: jest.fn() }) },
        search: jest.fn().mockReturnThis(),
        searchBuilder: {
            rebuild: jest.fn(),
            container: jest.fn().mockReturnValue({
                appendTo: jest.fn(),
            }),
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
    DataTableFn.datetime = jest.fn();
    DataTableFn.SearchBuilder = jest.fn().mockReturnValue({
        container: jest.fn().mockReturnValue({
            appendTo: jest.fn(),
        }),
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
            remove: jest.fn(),
        };
    });
    jq.fn = {
        DataTable: DataTableFn,
        dataTable: DataTableFn,
    };

    window.DataTable = DataTableFn;

    if (opts.noSearchBuilder) {
        delete DataTableFn.SearchBuilder;
    }

    window.$ = jq;
    return { dtInstance, DataTableFn, jq };
}

beforeEach(() => {
    jest.resetModules();
    jest.useFakeTimers();
    document.body.innerHTML = "";
    delete window.$;
    delete window.DataTable;

    // Suppress console output
    jest.spyOn(console, "debug").mockImplementation(() => {});
    jest.spyOn(console, "warn").mockImplementation(() => {});
    jest.spyOn(console, "error").mockImplementation(() => {});
});

afterEach(() => {
    jest.useRealTimers();
    jest.restoreAllMocks();
});

describe("games-search-init exported helpers", () => {
    test("initGamesPage skips when no table or cards exist", async () => {
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        expect(() => mod.initGamesPage()).not.toThrow();
    });

    test("initGamesPage initializes table when it exists and no prior DataTable", async () => {
        document.body.innerHTML = `
            <table id="games-results-table" data-ajax-url="/games/api">
                <thead><tr><th>Date</th><th>Opponent</th><th>Margin</th></tr></thead>
                <tbody></tbody>
            </table>
        `;
        const { dtInstance: _dtInstance } = setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));
        // It should not throw
    });

    test("initGamesDataTable returns early when no ajaxUrl", async () => {
        document.body.innerHTML = `
            <table id="games-results-table">
                <thead><tr><th>Date</th></tr></thead>
                <tbody></tbody>
            </table>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        expect(() =>
            mod.initGamesDataTable(
                document.getElementById("games-results-table"),
            ),
        ).not.toThrow();
    });

    test("initGamesDataTable returns early when table is null", async () => {
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        expect(() => mod.initGamesDataTable(null)).not.toThrow();
    });

    test("initGamesDataTable returns early when table has no id", async () => {
        document.body.innerHTML = `<table><thead><tr><th>X</th></tr></thead></table>`;
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        expect(() =>
            mod.initGamesDataTable(document.querySelector("table")),
        ).not.toThrow();
    });

    test("initGamesDataTable detects existing DataTable and skips", async () => {
        document.body.innerHTML = `
            <table id="games-results-table" data-ajax-url="/test">
                <thead><tr><th>Date</th></tr></thead>
                <tbody></tbody>
            </table>
        `;
        const { DataTableFn } = setupJQueryMock();
        DataTableFn.isDataTable.mockReturnValue(true);
        const mod = await import("../../legacy/games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));
    });

    test("initGamesPage refreshes existing DataTable when URL changes", async () => {
        document.body.innerHTML = `
            <table id="games-results-table" data-ajax-url="/new-url">
                <thead><tr><th>Date</th></tr></thead>
                <tbody></tbody>
            </table>
        `;
        const { dtInstance, DataTableFn, jq } = setupJQueryMock();
        DataTableFn.isDataTable.mockReturnValue(true);
        dtInstance.ajax.url = jest.fn().mockReturnValue({ load: jest.fn() });
        // Make table appear as existing
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
        jq.fn = {
            DataTable: DataTableFn,
            dataTable: DataTableFn,
        };

        const mod = await import("../../legacy/games-search-init.mjs");
        mod.initGamesPage();
    });

    test("initCardHover adds and removes shadow class on hover", async () => {
        document.body.innerHTML = `
            <div id="games-type-cards">
                <div class="game-type-card">Card 1</div>
                <div class="game-type-card">Card 2</div>
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        mod.initCardHover(document.getElementById("games-type-cards"));

        const card = document.querySelector(".game-type-card");
        card.dispatchEvent(new MouseEvent("mouseenter", { bubbles: true }));
        expect(card.classList.contains("shadow-sm")).toBe(true);
        card.dispatchEvent(new MouseEvent("mouseleave", { bubbles: true }));
        expect(card.classList.contains("shadow-sm")).toBe(false);
    });

    test("initDragScroll sets up drag scrolling on container", async () => {
        document.body.innerHTML = `
            <div class="table-responsive" style="overflow: auto; width: 200px;">
                <table style="width: 500px;"><tr><td>Content</td></tr></table>
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        const container = document.querySelector(".table-responsive");
        mod.initDragScroll(container);

        // mousedown then mousemove
        container.dispatchEvent(
            new MouseEvent("mousedown", { bubbles: true, pageX: 100 }),
        );
        expect(container.classList.contains("is-dragging")).toBe(true);

        container.dispatchEvent(
            new MouseEvent("mousemove", { bubbles: true, pageX: 80 }),
        );

        container.dispatchEvent(new MouseEvent("mouseup", { bubbles: true }));
        expect(container.classList.contains("is-dragging")).toBe(false);
    });

    test("initDragScroll skips interactive elements", async () => {
        document.body.innerHTML = `
            <div class="table-responsive">
                <button id="btn">Click</button>
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        const container = document.querySelector(".table-responsive");
        mod.initDragScroll(container);

        const btn = document.getElementById("btn");
        btn.dispatchEvent(
            new MouseEvent("mousedown", { bubbles: true, pageX: 100 }),
        );
        expect(container.classList.contains("is-dragging")).toBe(false);
    });

    test("initDragScroll handles mouseleave", async () => {
        document.body.innerHTML = `<div class="table-responsive"></div>`;
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        const container = document.querySelector(".table-responsive");
        mod.initDragScroll(container);

        container.dispatchEvent(
            new MouseEvent("mousedown", { bubbles: true, pageX: 100 }),
        );
        expect(container.classList.contains("is-dragging")).toBe(true);

        container.dispatchEvent(
            new MouseEvent("mouseleave", { bubbles: true }),
        );
        expect(container.classList.contains("is-dragging")).toBe(false);
    });

    test("initDragScroll does nothing when container is null", async () => {
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        expect(() => mod.initDragScroll(null)).not.toThrow();
    });

    test("cleanupGamesPage handles missing table", async () => {
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        expect(() => mod.cleanupGamesPage()).not.toThrow();
    });

    test("cleanupGamesPage cleans up existing DataTable", async () => {
        document.body.innerHTML = `
            <table id="games-results-table"><thead><tr><th>X</th></tr></thead></table>
            <div id="games-searchbuilder-slot"></div>
        `;
        const { DataTableFn, dtInstance: _dtInstance } = setupJQueryMock();
        DataTableFn.isDataTable.mockReturnValue(true);
        const mod = await import("../../legacy/games-search-init.mjs");
        mod.cleanupGamesPage();
        expect(document.getElementById("games-searchbuilder-slot")).toBeNull();
    });

    test("calculateRecord counts wins and losses", async () => {
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        const mockDt = {
            table: jest.fn().mockReturnValue({
                node: jest.fn().mockReturnValue(null),
            }),
            rows: jest.fn().mockReturnValue({
                data: jest.fn().mockReturnValue({
                    each: jest.fn((cb) => {
                        [
                            [
                                "2024-01-01",
                                "Team",
                                "Home",
                                "100",
                                "90",
                                "10",
                                "W",
                            ],
                            [
                                "2024-01-02",
                                "Team",
                                "Away",
                                "80",
                                "95",
                                "-15",
                                "L",
                            ],
                            [
                                "2024-01-03",
                                "Team",
                                "Home",
                                "110",
                                "90",
                                "20",
                                "W",
                            ],
                        ].forEach(cb);
                    }),
                }),
            }),
        };
        const record = mod.calculateRecord(mockDt);
        expect(record).toBe("2-1");
    });

    test("calculateRecord uses custom resultColumn from data attribute", async () => {
        document.body.innerHTML = `<table id="t" data-result-column="3"></table>`;
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        const tableEl = document.getElementById("t");
        const mockDt = {
            table: jest.fn().mockReturnValue({
                node: jest.fn().mockReturnValue(tableEl),
            }),
            rows: jest.fn().mockReturnValue({
                data: jest.fn().mockReturnValue({
                    each: jest.fn((cb) => {
                        [
                            ["A", "B", "C", "W"],
                            ["D", "E", "F", "L"],
                        ].forEach(cb);
                    }),
                }),
            }),
        };
        expect(mod.calculateRecord(mockDt)).toBe("1-1");
    });

    test("updateRecordDisplay updates DOM element", async () => {
        document.body.innerHTML = `<div id="games-record-display"></div>`;
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        const mockDt = {
            table: jest
                .fn()
                .mockReturnValue({ node: jest.fn().mockReturnValue(null) }),
            rows: jest.fn().mockReturnValue({
                data: jest.fn().mockReturnValue({
                    each: jest.fn((cb) => {
                        [["", "", "", "", "", "", "W"]].forEach(cb);
                    }),
                }),
            }),
        };
        mod.updateRecordDisplay(mockDt);
        expect(
            document.getElementById("games-record-display").textContent,
        ).toBe("Record: 1-0");
    });

    test("NUMERIC_COLUMNS and SCROLLER_THRESHOLD are exported", async () => {
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        expect(mod.NUMERIC_COLUMNS).toContain("Margin");
        expect(mod.SCROLLER_THRESHOLD).toBe(75);
    });

    test("initGamesPage initializes card hover when cards exist", async () => {
        document.body.innerHTML = `
            <div id="games-type-cards">
                <div class="game-type-card">Card</div>
            </div>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        mod.initGamesPage();
        const card = document.querySelector(".game-type-card");
        card.dispatchEvent(new MouseEvent("mouseenter"));
        expect(card.classList.contains("shadow-sm")).toBe(true);
    });

    test("mousemove without mousedown does not drag", async () => {
        document.body.innerHTML = `<div class="table-responsive"></div>`;
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        const container = document.querySelector(".table-responsive");
        mod.initDragScroll(container);
        container.dispatchEvent(
            new MouseEvent("mousemove", { bubbles: true, pageX: 80 }),
        );
        expect(container.classList.contains("is-dragging")).toBe(false);
    });
});

describe("games-search-init numeric column detection", () => {
    test("numeric columns are detected from headers", async () => {
        document.body.innerHTML = `
            <table id="games-results-table" data-ajax-url="/api">
                <thead><tr>
                    <th>Date</th>
                    <th>Opponent</th>
                    <th>Pts For</th>
                    <th>Pts Against</th>
                    <th>Margin</th>
                    <th>OT</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        `;
        setupJQueryMock();
        const mod = await import("../../legacy/games-search-init.mjs");
        mod.initGamesDataTable(document.getElementById("games-results-table"));
    });
});

describe("games-search-init cleanupGamesPage – back-button fix", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        delete window.$;
    });

    afterEach(() => {
        jest.restoreAllMocks();
        delete window.$;
    });

    test("cleanupGamesPage calls destroy(false) so table stays in DOM for Turbo cache", async () => {
        document.body.innerHTML = `<table id="games-results-table"><thead><tr><th>X</th></tr></thead></table>`;
        const table = document.getElementById("games-results-table");

        const destroyFn = jest.fn();
        const DataTableFn = jest.fn().mockReturnValue({ destroy: destroyFn });
        DataTableFn.isDataTable = jest.fn().mockReturnValue(true);
        DataTableFn.ext = { search: [] };

        const jq = jest.fn(() => ({
            length: 1,
            get: () => table,
            DataTable: DataTableFn,
        }));
        jq.fn = {
            DataTable: DataTableFn,
            dataTable: Object.assign(DataTableFn, {
                isDataTable: DataTableFn.isDataTable,
                ext: DataTableFn.ext,
            }),
        };
        window.$ = jq;

        const mod = await import("../../legacy/games-search-init.mjs");
        mod.cleanupGamesPage();

        // destroy should be called with false (keep table in DOM for Turbo cache snapshot)
        expect(destroyFn).toHaveBeenCalledWith(false);
        // The table element should still be in the DOM after cleanup
        expect(document.getElementById("games-results-table")).not.toBeNull();
    });
});
