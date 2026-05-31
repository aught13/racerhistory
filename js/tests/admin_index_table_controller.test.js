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

describe("admin-index-table controller branch coverage", () => {
    let application;
    let dataTableApi;
    let dataTableFactory;
    let jQueryMock;
    let pagination;
    let capturedOptions;

    const startController = ({
        includeSearch = true,
        includeTable = true,
        tableId = "opponents-table",
        datatablesUrl = "/admin/opponents/datatables",
    } = {}) => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = `
            <div data-controller="admin-index-table">
                ${
                    includeSearch
                        ? '<input id="branch-search" data-admin-index-table-target="searchInput" value="" />'
                        : ""
                }
                ${
                    includeTable
                        ? `<table id="${tableId}" data-admin-index-table-target="table" ${
                              datatablesUrl
                                  ? `data-datatables-url="${datatablesUrl}"`
                                  : ""
                          }><tbody></tbody></table>`
                        : ""
                }
            </div>
        `;

        dataTableApi = {
            destroy: jest.fn(),
            settings: jest.fn(() => []),
            search: jest.fn(() => ({ draw: jest.fn() })),
        };
        capturedOptions = null;
        dataTableFactory = jest.fn((options) => {
            capturedOptions = options;
            return dataTableApi;
        });

        pagination = {
            hide: jest.fn(),
            show: jest.fn(),
        };

        jQueryMock = jest.fn((arg) => {
            if (arg && arg.__dtContainer === true) {
                return {
                    find: jest.fn(() => pagination),
                };
            }

            return {
                DataTable: dataTableFactory,
            };
        });

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

        const root = document.querySelector(
            '[data-controller="admin-index-table"]',
        );

        return {
            getController: async () => {
                for (let i = 0; i < 4; i += 1) {
                    const resolved =
                        application.getControllerForElementAndIdentifier(
                            root,
                            "admin-index-table",
                        ) ||
                        application.controllers.find(
                            (controller) =>
                                controller.identifier === "admin-index-table",
                        );

                    if (resolved) {
                        return resolved;
                    }

                    await Promise.resolve();
                }

                return undefined;
            },
        };
    };

    beforeEach(() => {
        jest.useFakeTimers();
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

    test("handles missing page config and missing table target guards", async () => {
        const noTable = startController({ includeTable: false });
        jest.advanceTimersByTime(0);
        const noTableController = await noTable.getController();

        expect(noTableController.pageConfig).toBeNull();

        if (noTableController.hasSearchInputTarget) {
            expect(noTableController.searchInputTarget.placeholder).toBe("");
        }

        const unknown = startController({ tableId: "unknown-table" });
        jest.advanceTimersByTime(0);
        const unknownController = await unknown.getController();

        expect(unknownController.pageConfig).toBeNull();
    });

    test("jQueryHandle and DataTables availability checks use fallback branches", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        window.jQuery = null;
        window.$ = null;
        expect(controller.jQueryHandle()).toBeNull();
        expect(controller.isDataTablesAvailable()).toBe(false);

        window.$ = {
            fn: {
                DataTable: function () {
                    return null;
                },
            },
        };
        expect(controller.jQueryHandle()).toBe(window.$);
        expect(controller.isDataTablesAvailable()).toBe(true);
    });

    test("hasRequiredExtensions and initWhenReady retry/max branches", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        expect(controller.hasRequiredExtensions({ scroller: false })).toBe(
            true,
        );

        delete jQueryMock.fn.dataTable.Scroller;
        expect(controller.hasRequiredExtensions({ scroller: true })).toBe(
            false,
        );

        const setTimeoutSpy = jest.spyOn(window, "setTimeout");
        const initTableSpy = jest.spyOn(controller, "initTable");
        const availSpy = jest
            .spyOn(controller, "isDataTablesAvailable")
            .mockReturnValue(false);

        setTimeoutSpy.mockClear();
        controller.retryCount = 0;
        controller.initWhenReady();
        expect(controller.retryCount).toBe(1);
        expect(setTimeoutSpy).toHaveBeenCalled();

        setTimeoutSpy.mockClear();
        controller.retryCount = 60;
        controller.initWhenReady();
        expect(setTimeoutSpy).not.toHaveBeenCalled();

        availSpy.mockRestore();
        initTableSpy.mockRestore();
        setTimeoutSpy.mockRestore();
    });

    test("destroyTable handles abort, catches errors, and no-instance guard", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        controller.dtInstance = {
            settings: jest.fn(() => [
                {
                    jqXHR: {
                        readyState: 2,
                        abort: jest.fn(),
                    },
                },
            ]),
            destroy: jest.fn(),
        };

        controller.destroyTable();
        expect(controller.dtInstance).toBeNull();

        const aborting = {
            readyState: 2,
            abort: jest.fn(() => {
                throw new Error("abort failed");
            }),
        };
        controller.dtInstance = {
            settings: jest.fn(() => [{ jqXHR: aborting }]),
            destroy: jest.fn(() => {
                throw new Error("destroy failed");
            }),
        };

        expect(() => controller.destroyTable()).not.toThrow();
        expect(controller.dtInstance).toBeNull();

        controller.dtInstance = null;
        expect(() => controller.destroyTable()).not.toThrow();
    });

    test("initTable handles guard returns, existing table, dataUrl missing, and callback pagination", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        window.jQuery = null;
        window.$ = null;
        expect(() => controller.initTable()).not.toThrow();

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => true);
        const existing = { id: "existing" };
        dataTableFactory.mockReturnValue(existing);
        controller.initTable();
        expect(controller.dtInstance).toBe(existing);

        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);
        const noUrl = startController({ datatablesUrl: "" });
        jest.advanceTimersByTime(0);
        const noUrlController = await noUrl.getController();

        dataTableFactory.mockClear();
        noUrlController.initTable();
        expect(dataTableFactory).not.toHaveBeenCalled();

        const draw = startController({ tableId: "team-seasons-table" });
        jest.advanceTimersByTime(0);
        const drawController = await draw.getController();

        drawController.initTable();
        expect(capturedOptions).toBeTruthy();
        const containerRef = { __dtContainer: true };

        capturedOptions.drawCallback.call({
            api: () => ({
                page: { info: () => ({ pages: 1 }) },
                table: () => ({ container: () => containerRef }),
            }),
        });
        expect(pagination.hide).toHaveBeenCalled();

        capturedOptions.drawCallback.call({
            api: () => ({
                page: { info: () => ({ pages: 4 }) },
                table: () => ({ container: () => containerRef }),
            }),
        });
        expect(pagination.show).toHaveBeenCalled();
    });

    test("initTable default option fallbacks and handleSearchInput guard/debounce branches", async () => {
        const mounted = startController({ tableId: "team-seasons-table" });
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        const pageConfigSpy = jest
            .spyOn(controller, "pageConfig", "get")
            .mockReturnValue({
                labelPlural: "rows",
                order: [[0, "asc"]],
                serverSide: false,
            });

        dataTableFactory.mockClear();
        controller.initTable();

        expect(capturedOptions.pageLength).toBe(50);
        expect(capturedOptions.lengthMenu).toEqual([25, 50, 100, 250]);
        expect(capturedOptions.scrollCollapse).toBe(true);
        expect(capturedOptions.deferRender).toBe(true);
        expect(capturedOptions.dom).toBe("rltip");

        pageConfigSpy.mockRestore();

        controller.dtInstance = null;
        expect(() => controller.handleSearchInput()).not.toThrow();

        controller.dtInstance = dataTableApi;
        controller.searchDebounce = window.setTimeout(() => {}, 1000);
        controller.searchInputTarget.value = "branch";
        controller.handleSearchInput();
        jest.advanceTimersByTime(250);
        expect(dataTableApi.search).toHaveBeenCalledWith("branch");

        dataTableApi.search.mockClear();
        controller.dtInstance = null;
        controller.handleSearchInput();
        jest.advanceTimersByTime(250);
        expect(dataTableApi.search).not.toHaveBeenCalled();
    });

    test("disconnect clears timers and removes search listener safely", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        const removeSpy = jest.spyOn(
            controller.searchInputTarget,
            "removeEventListener",
        );

        controller.initTimer = window.setTimeout(() => {}, 1000);
        controller.searchDebounce = window.setTimeout(() => {}, 1000);
        controller.retryTimer = window.setTimeout(() => {}, 1000);
        controller.disconnect();

        expect(removeSpy).toHaveBeenCalledWith(
            "input",
            controller.boundSearchInput,
        );
        expect(controller.initTimer).toBeNull();
        expect(controller.searchDebounce).toBeNull();
        expect(controller.retryTimer).toBeNull();

        removeSpy.mockRestore();

        const noSearch = startController({ includeSearch: false });
        jest.advanceTimersByTime(0);
        const noSearchController = await noSearch.getController();
        expect(() => noSearchController.disconnect()).not.toThrow();
    });
});
