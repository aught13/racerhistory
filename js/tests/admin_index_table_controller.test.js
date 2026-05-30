/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import AdminIndexTableController from "../controllers/admin_index_table_controller.js";

describe("admin-index-table controller", () => {
    let application;
    let dataTableApi;
    let dataTableFactory;
    let jQueryMock;

    beforeEach(() => {
        jest.useFakeTimers();

        document.body.innerHTML = `
            <div data-controller="admin-index-table">
                <input
                    id="opponents-search"
                    data-admin-index-table-target="searchInput"
                    value=""
                />
                <table
                    id="opponents-table"
                    data-admin-index-table-target="table"
                    data-datatables-url="/admin/opponents/datatables"
                >
                    <tbody></tbody>
                </table>
            </div>
        `;

        dataTableApi = {
            destroy: jest.fn(),
            search: jest.fn(() => ({ draw: jest.fn() })),
        };
        dataTableFactory = jest.fn(() => dataTableApi);

        jQueryMock = jest.fn(() => ({
            DataTable: dataTableFactory,
        }));
        jQueryMock.fn = {
            DataTable: function () {
                return null;
            },
            dataTable: {
                Scroller: function () {
                    return null;
                },
            },
        };
        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        application = Application.start();
        application.register("admin-index-table", AdminIndexTableController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        jest.runOnlyPendingTimers();
        jest.useRealTimers();

        delete window.jQuery;
        delete window.$;
        document.body.innerHTML = "";
    });

    test("initializes the table and debounces search", () => {
        jest.advanceTimersByTime(0);
        expect(dataTableFactory).toHaveBeenCalledTimes(1);

        const config = dataTableFactory.mock.calls[0][0];
        expect(config.serverSide).toBe(true);
        expect(config.processing).toBe(true);
        expect(config.ajax.url).toBe("/admin/opponents/datatables");
        expect(config.columns).toHaveLength(5);

        const searchInput = document.getElementById("opponents-search");
        searchInput.value = "Belmont";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));

        expect(dataTableApi.search).not.toHaveBeenCalled();
        jest.advanceTimersByTime(250);
        expect(dataTableApi.search).toHaveBeenCalledWith("Belmont");
    });

    test("does not destroy the table on turbo before cache", () => {
        jest.advanceTimersByTime(0);
        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(dataTableApi.destroy).not.toHaveBeenCalled();
    });
});

describe("admin-index-table controller (team-seasons local table)", () => {
    let application;
    let drawMock;
    let dataTableApi;
    let dataTableFactory;
    let jQueryMock;

    beforeEach(() => {
        jest.useFakeTimers();

        document.body.innerHTML = `
            <div data-controller="admin-index-table">
                <input
                    id="team-seasons-search"
                    data-admin-index-table-target="searchInput"
                    value=""
                />
                <table id="team-seasons-table" data-admin-index-table-target="table">
                    <tbody>
                        <tr><td>Racers</td><td>2024-2025</td></tr>
                    </tbody>
                </table>
            </div>
        `;

        drawMock = jest.fn();
        dataTableApi = {
            destroy: jest.fn(),
            search: jest.fn(() => ({ draw: drawMock })),
        };
        dataTableFactory = jest.fn(() => dataTableApi);

        jQueryMock = jest.fn(() => ({
            DataTable: dataTableFactory,
        }));
        jQueryMock.fn = {
            DataTable: function () {
                return null;
            },
            dataTable: {},
        };
        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        application = Application.start();
        application.register("admin-index-table", AdminIndexTableController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        jest.runOnlyPendingTimers();
        jest.useRealTimers();

        delete window.jQuery;
        delete window.$;
        document.body.innerHTML = "";
    });

    test("initializes local DataTable options and debounced search", () => {
        jest.advanceTimersByTime(0);
        expect(dataTableFactory).toHaveBeenCalledTimes(1);

        const config = dataTableFactory.mock.calls[0][0];
        expect(config.serverSide).toBeUndefined();
        expect(config.pageLength).toBe(15);
        expect(config.scrollX).toBe(true);
        expect(config.order).toEqual([[1, "desc"]]);

        const searchInput = document.getElementById("team-seasons-search");
        searchInput.value = "Racers";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));

        expect(dataTableApi.search).not.toHaveBeenCalled();
        jest.advanceTimersByTime(250);
        expect(dataTableApi.search).toHaveBeenCalledWith("Racers");
        expect(drawMock).toHaveBeenCalledTimes(1);
    });
});

describe("admin-index-table controller (game-types local table)", () => {
    let application;
    let drawMock;
    let dataTableApi;
    let dataTableFactory;
    let jQueryMock;

    beforeEach(() => {
        jest.useFakeTimers();

        document.body.innerHTML = `
            <div data-controller="admin-index-table">
                <input
                    id="game-types-search"
                    data-admin-index-table-target="searchInput"
                    value=""
                />
                <table id="game-types-table" data-admin-index-table-target="table">
                    <tbody>
                        <tr><td>Conference</td><td>Yes</td><td>Yes</td><td>CONF</td></tr>
                    </tbody>
                </table>
            </div>
        `;

        drawMock = jest.fn();
        dataTableApi = {
            destroy: jest.fn(),
            search: jest.fn(() => ({ draw: drawMock })),
        };
        dataTableFactory = jest.fn(() => dataTableApi);

        jQueryMock = jest.fn(() => ({
            DataTable: dataTableFactory,
        }));
        jQueryMock.fn = {
            DataTable: function () {
                return null;
            },
            dataTable: {},
        };
        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        application = Application.start();
        application.register("admin-index-table", AdminIndexTableController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        jest.runOnlyPendingTimers();
        jest.useRealTimers();

        delete window.jQuery;
        delete window.$;
        document.body.innerHTML = "";
    });

    test("initializes game-types local options and debounced search", () => {
        jest.advanceTimersByTime(0);
        expect(dataTableFactory).toHaveBeenCalledTimes(1);

        const config = dataTableFactory.mock.calls[0][0];
        expect(config.serverSide).toBeUndefined();
        expect(config.pageLength).toBe(25);
        expect(config.lengthMenu).toEqual([25, 50, 100]);
        expect(config.order).toEqual([[0, "asc"]]);

        const searchInput = document.getElementById("game-types-search");
        expect(searchInput.placeholder).toBe("Name or abbreviation...");

        searchInput.value = "Conference";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));

        expect(dataTableApi.search).not.toHaveBeenCalled();
        jest.advanceTimersByTime(250);
        expect(dataTableApi.search).toHaveBeenCalledWith("Conference");
        expect(drawMock).toHaveBeenCalledTimes(1);
    });
});
