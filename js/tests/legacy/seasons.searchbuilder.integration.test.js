/** @jest-environment jsdom */

import { jest } from "@jest/globals";
describe("Seasons SearchBuilder integration (module)", () => {
    let teardown;
    let setupDataTablesMock;
    let initSeasons;

    beforeEach(async () => {
        document.body.innerHTML = `
            <div id="seasons-controls" class="d-flex"></div>
            <div id="searchbuilder-panel" class="searchbuilder-panel"></div>
            <div id="splits-controls" class="d-flex"></div>
            <div id="splits-searchbuilder-panel" class="searchbuilder-panel"></div>
            <table id="seasons-table"><thead></thead><tbody>
                <tr><td class="text-muted seasons-row-number">0</td></tr>
            </tbody></table>
            <table id="season-splits-table"><thead></thead><tbody>
                <tr><td class="text-muted seasons-row-number">0</td></tr>
            </tbody></table>
        `;

        const mockModule = await import("./helpers/datatables.mock");
        setupDataTablesMock = mockModule.default || mockModule;
        teardown = setupDataTablesMock();

        const seasonsModule =
            await import("../../legacy/modules/seasons-init.cjs");
        initSeasons = seasonsModule.default || seasonsModule;
    });

    afterEach(() => {
        if (typeof teardown === "function") teardown();
    });

    test("module initializes DataTable and appends SearchBuilder to panel and toggles", async () => {
        const result = initSeasons();
        expect(result).toBeTruthy();
        // button should be created
        const btn = document.getElementById("seasons-filter-btn");
        expect(btn).toBeTruthy();
        const panel = document.getElementById("searchbuilder-panel");
        expect(panel).toBeTruthy();
        // initially hidden
        expect(panel.classList.contains("d-none")).toBe(true);
        // panel contains SearchBuilder
        const sbEl = panel.querySelector(".dtsb-searchBuilder");
        expect(sbEl).toBeTruthy();

        // toggle open
        btn.click();
        expect(panel.classList.contains("d-none")).toBe(false);
        expect(panel.classList.contains("sb-open")).toBe(true);
        expect(btn.getAttribute("aria-expanded")).toBe("true");

        // toggle closed
        btn.click();
        expect(panel.classList.contains("d-none")).toBe(true);
        expect(panel.classList.contains("sb-open")).toBe(false);
        expect(btn.getAttribute("aria-expanded")).toBe("false");
    });

    test("custom filter button id is respected", async () => {
        initSeasons({
            tableSelector: "#season-splits-table",
            controlsSelector: "#splits-controls",
            panelSelector: "#splits-searchbuilder-panel",
            filterButtonId: "splits-filter-btn",
        });
        expect(document.getElementById("splits-filter-btn")).toBeTruthy();
    });

    test("renumbers rows when the table redraws", async () => {
        document.querySelector("#seasons-table tbody").innerHTML = `
            <tr><td class="text-muted seasons-row-number">0</td></tr>
            <tr><td class="text-muted seasons-row-number">0</td></tr>
        `;
        initSeasons();
        const rows = document.querySelectorAll("#seasons-table tbody tr");
        expect(rows[0].querySelector("td").textContent).toBe("1");
        expect(rows[1].querySelector("td").textContent).toBe("2");
    });

    test("adds placeholder when SearchBuilder is missing", async () => {
        delete global.$.fn.dataTable.SearchBuilder;

        initSeasons({
            tableSelector: "#seasons-table",
            controlsSelector: "#seasons-controls",
            panelSelector: "#searchbuilder-panel",
        });

        const panel = document.getElementById("searchbuilder-panel");
        const placeholder = panel.querySelector(".p-3.text-muted.small");
        expect(placeholder).toBeTruthy();
        expect(panel.classList.contains("d-none")).toBe(true);

        const btn = document.getElementById("seasons-filter-btn");
        expect(btn.getAttribute("aria-expanded")).toBe("false");
    });

    test("calls custom initComplete and drawCallback hooks", async () => {
        const initComplete = jest.fn();
        const drawCallback = jest.fn();

        initSeasons({
            dataTableOptions: {
                initComplete,
                drawCallback,
            },
        });

        expect(initComplete).toHaveBeenCalledTimes(1);
        expect(drawCallback).toHaveBeenCalledTimes(1);
    });

    test("applies column labels without changing visible headers", async () => {
        document.querySelector("#seasons-table thead").innerHTML = `
            <tr>
                <th rowspan="2">#</th>
                <th rowspan="2">Team</th>
                <th rowspan="2">Season</th>
                <th rowspan="2">Conf</th>
                <th rowspan="2">Conf finish</th>
                <th colspan="3">Overall</th>
                <th colspan="3">Conference</th>
                <th colspan="3">Conference Tourn</th>
                <th colspan="3">Postseason</th>
            </tr>
            <tr>
                <th>W</th>
                <th>L</th>
                <th>Pct</th>
                <th>W</th>
                <th>L</th>
                <th>Pct</th>
                <th>W</th>
                <th>L</th>
                <th>Pct</th>
                <th>W</th>
                <th>L</th>
                <th>Pct</th>
            </tr>
        `;

        const labels = [];
        labels[5] = "OW";
        labels[6] = "OL";
        labels[7] = "OPct";

        initSeasons({ columnLabels: labels });

        const calls = global.__datatableCalls || [];
        expect(calls.length).toBeGreaterThan(0);
        const defs = calls[0].opts.columnDefs || [];
        const def = defs.find((entry) => entry.targets === 5);
        expect(def && def.title).toBe("OW");

        const headerCells = document.querySelectorAll(
            "#seasons-table thead tr:last-child th",
        );
        expect(headerCells[0].textContent).toBe("W");
        expect(headerCells[1].textContent).toBe("L");
    });

    test("returns nulls when table is missing", async () => {
        const result = initSeasons({ tableSelector: "#missing-table" });
        expect(result).toEqual({ sb: null, table: null });
    });

    test("renumbers rows using fallback when numberColumn is missing", async () => {
        document.querySelector("#seasons-table tbody").innerHTML = `
            <tr><td class="text-muted seasons-row-number">0</td></tr>
            <tr><td class="text-muted seasons-row-number">0</td></tr>
        `;

        initSeasons({ numberColumn: 4 });

        const rows = document.querySelectorAll("#seasons-table tbody tr");
        expect(rows[0].querySelector("td").textContent).toBe("1");
        expect(rows[1].querySelector("td").textContent).toBe("2");
    });

    test("handles SearchBuilder constructor errors", async () => {
        global.$.fn.dataTable.SearchBuilder = jest.fn(() => {
            throw new Error("boom");
        });

        initSeasons();

        const panel = document.getElementById("searchbuilder-panel");
        const placeholder = panel.querySelector(".p-3.text-muted.small");
        expect(placeholder).toBeTruthy();
    });

    test("returns nulls when DataTable initialization throws", async () => {
        const original$ = global.$;
        global.$ = (selectorOrEl) => {
            const wrapper = original$(selectorOrEl);
            wrapper.DataTable = jest.fn(() => {
                throw new Error("boom");
            });
            return wrapper;
        };
        global.$.fn = original$.fn;

        const result = initSeasons();
        expect(result).toEqual({ sb: null, table: null });

        global.$ = original$;
    });
});
