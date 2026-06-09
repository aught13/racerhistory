import { jest } from "@jest/globals";

/* ——— Helpers ——————————————————————————————————————————————————— */
function removeFakeJquery() {
    delete window.$;
}

/* ——— Module-level tests ——————————————————————————————————————— */

describe("stats-init-loader module exports", () => {
    let addEventSpy;

    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        addEventSpy = jest.spyOn(document, "addEventListener");
    });

    afterEach(() => {
        addEventSpy.mockRestore();
        removeFakeJquery();
    });

    test("registers DOMContentLoaded and turbo:load handlers", async () => {
        await import("../../legacy/stats-init-loader.mjs");

        const domHandler = addEventSpy.mock.calls.find(
            (call) => call[0] === "DOMContentLoaded",
        );
        const turboHandler = addEventSpy.mock.calls.find(
            (call) => call[0] === "turbo:load",
        );
        expect(domHandler).toBeDefined();
        expect(turboHandler).toBeDefined();
    });

    test("exports initStatsPage function", async () => {
        const mod = await import("../../legacy/stats-init-loader.mjs");
        expect(typeof mod.initStatsPage).toBe("function");
    });

    test("exports initStatsDataTable function", async () => {
        const mod = await import("../../legacy/stats-init-loader.mjs");
        expect(typeof mod.initStatsDataTable).toBe("function");
    });

    test("exports initDragScroll function", async () => {
        const mod = await import("../../legacy/stats-init-loader.mjs");
        expect(typeof mod.initDragScroll).toBe("function");
    });

    test("exports fixScrollXHeaderAlignment function", async () => {
        const mod = await import("../../legacy/stats-init-loader.mjs");
        expect(typeof mod.fixScrollXHeaderAlignment).toBe("function");
    });

    test("exports initCardHover function", async () => {
        const mod = await import("../../legacy/stats-init-loader.mjs");
        expect(typeof mod.initCardHover).toBe("function");
    });

    test("exports NUMERIC_COLUMNS array", async () => {
        const mod = await import("../../legacy/stats-init-loader.mjs");
        expect(Array.isArray(mod.NUMERIC_COLUMNS)).toBe(true);
        expect(mod.NUMERIC_COLUMNS).toContain("PTS");
        expect(mod.NUMERIC_COLUMNS).toContain("GP");
    });

    test("exports SCROLLER_THRESHOLD constant", async () => {
        const mod = await import("../../legacy/stats-init-loader.mjs");
        expect(mod.SCROLLER_THRESHOLD).toBe(75);
    });
});

/* ——— initStatsPage ———————————————————————————————————————————— */

describe("initStatsPage", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
    });

    afterEach(() => {
        removeFakeJquery();
    });

    test("initializes card hover when cards present", async () => {
        document.body.innerHTML = `<div id="stats-type-cards">
            <a class="stat-type-card" href="/stats/player-season">Player Season</a>
        </div>`;

        const { initStatsPage } =
            await import("../../legacy/stats-init-loader.mjs");
        expect(() => initStatsPage()).not.toThrow();
    });

    test("does not throw when no elements exist", async () => {
        const { initStatsPage } =
            await import("../../legacy/stats-init-loader.mjs");
        expect(() => initStatsPage()).not.toThrow();
    });
});

/* ——— initStatsDataTable ——————————————————————————————————————— */

describe("initStatsDataTable", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
    });

    afterEach(() => {
        removeFakeJquery();
    });

    test("does nothing when table has no data-ajax-url", async () => {
        document.body.innerHTML = `<table id="stats-results-table">
            <thead><tr><th>Player</th><th>PTS</th></tr></thead>
            <tbody></tbody>
        </table>`;

        const table = document.getElementById("stats-results-table");
        const { initStatsDataTable } =
            await import("../../legacy/stats-init-loader.mjs");
        expect(() => initStatsDataTable(table)).not.toThrow();
    });

    test("detects numeric columns from headers", async () => {
        document.body.innerHTML = `<table id="stats-results-table"
            data-ajax-url="/stats/player-season?format=json">
            <thead><tr><th>Player</th><th>GP</th><th>PTS</th><th>Team</th></tr></thead>
            <tbody></tbody>
        </table>`;

        const { NUMERIC_COLUMNS } =
            await import("../../legacy/stats-init-loader.mjs");

        const table = document.getElementById("stats-results-table");
        const headers = table.querySelectorAll("thead th");
        const numericTargets = [];
        headers.forEach((th, idx) => {
            if (NUMERIC_COLUMNS.includes(th.textContent.trim())) {
                numericTargets.push(idx);
            }
        });

        // GP is at index 1, PTS at index 2
        expect(numericTargets).toEqual([1, 2]);
    });

    test("includes 3PM and Seasons in NUMERIC_COLUMNS", async () => {
        const { NUMERIC_COLUMNS } =
            await import("../../legacy/stats-init-loader.mjs");
        expect(NUMERIC_COLUMNS).toContain("3PM");
        expect(NUMERIC_COLUMNS).toContain("3PA");
        expect(NUMERIC_COLUMNS).toContain("Seasons");
        expect(NUMERIC_COLUMNS).toContain("#");
    });

    test("SCROLLER_THRESHOLD is 75", async () => {
        const { SCROLLER_THRESHOLD } =
            await import("../../legacy/stats-init-loader.mjs");
        expect(SCROLLER_THRESHOLD).toBe(75);
    });

    test("supports opponent-team-game ajax endpoint", async () => {
        document.body.innerHTML = `<table id="stats-results-table"
            data-ajax-url="/stats/opponent-team-game?format=json">
            <thead><tr><th>Opponent</th><th>Date</th><th>PTS</th></tr></thead>
            <tbody></tbody>
        </table>`;

        const table = document.getElementById("stats-results-table");
        const { initStatsDataTable } =
            await import("../../legacy/stats-init-loader.mjs");
        expect(() => initStatsDataTable(table)).not.toThrow();
    });
});

/* ——— initDragScroll ——————————————————————————————————————————— */

describe("initDragScroll", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
    });

    test("does nothing when container is null", async () => {
        const { initDragScroll } =
            await import("../../legacy/stats-init-loader.mjs");
        expect(() => initDragScroll(null)).not.toThrow();
    });

    test("adds is-dragging class on mousedown", async () => {
        document.body.innerHTML = `<div class="table-responsive" id="wrap">
            <table><tr><td>Data</td></tr></table>
        </div>`;

        const container = document.getElementById("wrap");
        const { initDragScroll } =
            await import("../../legacy/stats-init-loader.mjs");
        initDragScroll(container);

        container.dispatchEvent(
            new MouseEvent("mousedown", { bubbles: true, pageX: 100 }),
        );
        expect(container.classList.contains("is-dragging")).toBe(true);
    });

    test("removes is-dragging class on mouseup", async () => {
        document.body.innerHTML = `<div class="table-responsive" id="wrap">
            <table><tr><td>Data</td></tr></table>
        </div>`;

        const container = document.getElementById("wrap");
        const { initDragScroll } =
            await import("../../legacy/stats-init-loader.mjs");
        initDragScroll(container);

        container.dispatchEvent(
            new MouseEvent("mousedown", { bubbles: true, pageX: 100 }),
        );
        container.dispatchEvent(new MouseEvent("mouseup", { bubbles: true }));
        expect(container.classList.contains("is-dragging")).toBe(false);
    });

    test("skips drag on interactive targets", async () => {
        document.body.innerHTML = `<div class="table-responsive" id="wrap">
            <a href="#">Link</a>
        </div>`;

        const container = document.getElementById("wrap");
        const { initDragScroll } =
            await import("../../legacy/stats-init-loader.mjs");
        initDragScroll(container);

        const link = container.querySelector("a");
        link.dispatchEvent(
            new MouseEvent("mousedown", { bubbles: true, pageX: 50 }),
        );
        expect(container.classList.contains("is-dragging")).toBe(false);
    });
});

/* ——— initCardHover ———————————————————————————————————————————— */

describe("initCardHover", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
    });

    test("adds shadow-sm on mouseenter", async () => {
        document.body.innerHTML = `<div id="stats-type-cards">
            <a class="stat-type-card" href="#">Card</a>
        </div>`;

        const container = document.getElementById("stats-type-cards");
        const { initCardHover } =
            await import("../../legacy/stats-init-loader.mjs");
        initCardHover(container);

        const card = container.querySelector(".stat-type-card");
        card.dispatchEvent(new Event("mouseenter"));
        expect(card.classList.contains("shadow-sm")).toBe(true);
    });

    test("removes shadow-sm on mouseleave", async () => {
        document.body.innerHTML = `<div id="stats-type-cards">
            <a class="stat-type-card" href="#">Card</a>
        </div>`;

        const container = document.getElementById("stats-type-cards");
        const { initCardHover } =
            await import("../../legacy/stats-init-loader.mjs");
        initCardHover(container);

        const card = container.querySelector(".stat-type-card");
        card.dispatchEvent(new Event("mouseenter"));
        expect(card.classList.contains("shadow-sm")).toBe(true);

        card.dispatchEvent(new Event("mouseleave"));
        expect(card.classList.contains("shadow-sm")).toBe(false);
    });
});

describe("stats-init-loader cleanupStatsPage – back-button fix", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        delete window.$;
    });

    afterEach(() => {
        jest.restoreAllMocks();
        delete window.$;
    });

    test("cleanupStatsPage calls destroy(false) so table stays in DOM for Turbo cache", async () => {
        document.body.innerHTML = `<table id="stats-results-table"><thead><tr><th>X</th></tr></thead></table>`;
        const table = document.getElementById("stats-results-table");

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

        const mod = await import("../../legacy/stats-init-loader.mjs");
        mod.cleanupStatsPage();

        // destroy should be called with false (keep table in DOM for Turbo cache snapshot)
        expect(destroyFn).toHaveBeenCalledWith(false);
        // The table element should still be in the DOM after cleanup
        expect(document.getElementById("stats-results-table")).not.toBeNull();
    });
});
