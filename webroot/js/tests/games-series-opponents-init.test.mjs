/**
 * @jest-environment jsdom
 */

import { jest } from "@jest/globals";

function setupDataTablesMock() {
    const dt = {
        search: jest.fn().mockReturnThis(),
        draw: jest.fn().mockReturnThis(),
        columns: {
            adjust: jest.fn(),
        },
        destroy: jest.fn(),
        api: jest.fn(function () {
            return dt;
        }),
    };

    const DataTableFn = jest.fn().mockReturnValue(dt);
    DataTableFn.isDataTable = jest.fn().mockReturnValue(false);

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

    jq.fn = {
        DataTable: DataTableFn,
        dataTable: DataTableFn,
    };

    window.$ = jq;

    return { dt, DataTableFn };
}

describe("games-series-opponents-init", () => {
    beforeEach(() => {
        jest.resetModules();
        jest.useFakeTimers();
        document.body.innerHTML = "";
        delete window.$;
        jest.spyOn(console, "warn").mockImplementation(() => {});
    });

    afterEach(() => {
        jest.runOnlyPendingTimers();
        jest.useRealTimers();
        jest.restoreAllMocks();
    });

    test("initSeriesOpponentsTable exits when required DOM is missing", async () => {
        const { initSeriesOpponentsTable } =
            await import("../games-series-opponents-init.mjs");

        expect(() => initSeriesOpponentsTable()).not.toThrow();
    });

    test("initSeriesOpponentsTable initializes DataTable and external search input", async () => {
        document.body.innerHTML = `
            <input id="series-opponents-search" type="text" />
            <table id="series-opponents-table" data-opponents-url="/games/series-opponents?format=json">
                <thead>
                    <tr>
                        <th>Opponent</th>
                        <th>Short</th>
                        <th>Games</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        `;

        const { dt, DataTableFn } = setupDataTablesMock();
        const { initSeriesOpponentsTable } =
            await import("../games-series-opponents-init.mjs");

        initSeriesOpponentsTable();
        await Promise.resolve();

        expect(DataTableFn).toHaveBeenCalled();

        const searchInput = document.getElementById("series-opponents-search");
        searchInput.value = "bel";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(260);

        expect(dt.search).toHaveBeenCalledWith("bel");
        expect(dt.draw).toHaveBeenCalled();
    });

    test("cleanupSeriesOpponentsTable destroys DataTable and unbinds input handler", async () => {
        document.body.innerHTML = `
            <input id="series-opponents-search" type="text" />
            <table id="series-opponents-table" data-opponents-url="/games/series-opponents?format=json">
                <thead><tr><th>Opponent</th><th>Short</th><th>Games</th><th></th></tr></thead>
                <tbody></tbody>
            </table>
        `;

        const { dt, DataTableFn } = setupDataTablesMock();
        DataTableFn.isDataTable.mockReturnValue(true);

        const { cleanupSeriesOpponentsTable } =
            await import("../games-series-opponents-init.mjs");

        const searchInput = document.getElementById("series-opponents-search");
        const handler = jest.fn();
        searchInput._seriesOpponentsSearchHandler = handler;
        searchInput.addEventListener("input", handler);

        cleanupSeriesOpponentsTable();

        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        expect(handler).not.toHaveBeenCalled();
        expect(dt.destroy).toHaveBeenCalledWith(false);
    });
});
