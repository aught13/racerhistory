/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import PersonsIndexController from "../controllers/persons_index_controller.js";

describe("persons-index controller", () => {
    let application;
    let drawMock;
    let dataTableApi;
    let dataTableFactory;
    let jQueryMock;

    beforeEach(() => {
        jest.useFakeTimers();

        document.body.innerHTML = `
            <div
                data-controller="persons-index"
                data-persons-index-bulk-delete-url-value="/admin/persons/bulk-delete"
            >
                <div id="persons-bulk-action-bar" data-persons-index-target="bulkBar" style="display: none;"></div>
                <select id="bulk-action-select-persons" data-persons-index-target="actionSelect">
                    <option value="">Choose...</option>
                    <option value="delete">Delete</option>
                </select>
                <button id="bulk-action-btn-persons" data-persons-index-target="bulkButton" disabled>Go</button>
                <input id="persons-search" data-persons-index-target="searchInput" />
                <table
                    id="persons-table"
                    data-persons-index-target="table"
                    data-datatables-url="/admin/persons/datatables"
                >
                    <tbody></tbody>
                </table>
                <input
                    type="checkbox"
                    id="select-all-persons"
                    data-persons-index-target="selectAll"
                />
                <form id="delete-form-persons-bulk" data-persons-index-target="bulkForm"></form>
            </div>
        `;

        window.__rhStimulusShowConfirmDelete = jest.fn();

        drawMock = jest.fn();
        dataTableApi = {
            destroy: jest.fn(),
            on: jest.fn(),
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
        };
        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        application = Application.start();
        application.register("persons-index", PersonsIndexController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        jest.runOnlyPendingTimers();
        jest.useRealTimers();

        delete window.__rhStimulusShowConfirmDelete;
        delete window.jQuery;
        delete window.$;
        document.body.innerHTML = "";
    });

    test("initializes DataTable and debounced search", () => {
        jest.advanceTimersByTime(0);
        expect(dataTableFactory).toHaveBeenCalledTimes(1);

        const config = dataTableFactory.mock.calls[0][0];
        expect(config.serverSide).toBe(true);
        expect(config.scroller).toBe(true);
        expect(config.ajax.url).toBe("/admin/persons/datatables");

        const searchInput = document.getElementById("persons-search");
        searchInput.value = "Jordan";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));

        expect(dataTableApi.search).not.toHaveBeenCalled();
        jest.advanceTimersByTime(SEARCH_DEBOUNCE_MS());
        expect(dataTableApi.search).toHaveBeenCalledWith("Jordan");
        expect(drawMock).toHaveBeenCalledTimes(1);
    });

    test("toggles bulk bar and triggers bulk delete confirmation", () => {
        jest.advanceTimersByTime(0);
        const tableBody = document.querySelector("#persons-table tbody");
        tableBody.innerHTML = `
            <tr><td><input type="checkbox" class="person-checkbox" value="101"></td></tr>
            <tr><td><input type="checkbox" class="person-checkbox" value="202"></td></tr>
        `;

        const firstCheckbox = tableBody.querySelector(".person-checkbox");
        firstCheckbox.checked = true;
        firstCheckbox.dispatchEvent(new Event("change", { bubbles: true }));

        const bulkBar = document.getElementById("persons-bulk-action-bar");
        const actionSelect = document.getElementById(
            "bulk-action-select-persons",
        );
        const bulkButton = document.getElementById("bulk-action-btn-persons");

        expect(bulkBar.style.display).toBe("flex");
        expect(bulkButton.disabled).toBe(true);

        actionSelect.value = "delete";
        actionSelect.dispatchEvent(new Event("change", { bubbles: true }));

        expect(bulkButton.disabled).toBe(false);

        bulkButton.click();

        expect(window.__rhStimulusShowConfirmDelete).toHaveBeenCalledTimes(1);
        expect(window.__rhStimulusShowConfirmDelete).toHaveBeenCalledWith(
            expect.objectContaining({
                deleteUrl: "/admin/persons/bulk-delete",
                itemType: "persons (bulk)",
                ids: JSON.stringify(["101"]),
                idsName: "person_ids[]",
                formId: "delete-form-persons-bulk",
                bulkAction: "delete",
            }),
        );
    });

    test("does not destroy DataTable on turbo:before-cache", () => {
        jest.advanceTimersByTime(0);
        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(dataTableApi.destroy).not.toHaveBeenCalled();
    });

    test("toggles select-all and syncs state on draw callback", () => {
        jest.advanceTimersByTime(0);

        const tableBody = document.querySelector("#persons-table tbody");
        tableBody.innerHTML = `
            <tr><td><input type="checkbox" class="person-checkbox" value="101"></td></tr>
            <tr><td><input type="checkbox" class="person-checkbox" value="202"></td></tr>
        `;

        const selectAll = document.getElementById("select-all-persons");
        selectAll.checked = true;
        selectAll.dispatchEvent(new Event("change", { bubbles: true }));

        const checkboxes = tableBody.querySelectorAll(".person-checkbox");
        expect(checkboxes[0].checked).toBe(true);
        expect(checkboxes[1].checked).toBe(true);

        const drawHandler = dataTableApi.on.mock.calls.find(
            (call) => call[0] === "draw",
        )[1];

        checkboxes[1].checked = false;
        drawHandler();
        expect(selectAll.checked).toBe(false);

        checkboxes[1].checked = true;
        drawHandler();
        expect(selectAll.checked).toBe(true);
    });

    test("guards bulk delete when action, selection, or confirm helper are unavailable", () => {
        jest.advanceTimersByTime(0);

        const tableBody = document.querySelector("#persons-table tbody");
        const actionSelect = document.getElementById(
            "bulk-action-select-persons",
        );
        const bulkButton = document.getElementById("bulk-action-btn-persons");

        tableBody.innerHTML =
            '<tr><td><input type="checkbox" class="person-checkbox" value="101"></td></tr>';

        bulkButton.click();
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        tableBody.querySelector(".person-checkbox").checked = true;
        bulkButton.click();
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        actionSelect.value = "archive";
        actionSelect.dispatchEvent(new Event("change", { bubbles: true }));
        bulkButton.click();
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        actionSelect.value = "delete";
        actionSelect.dispatchEvent(new Event("change", { bubbles: true }));
        delete window.__rhStimulusShowConfirmDelete;

        bulkButton.click();
        expect(window.__rhStimulusShowConfirmDelete).toBeUndefined();
    });

    test("retries DataTables initialization and skips when table URL is missing", () => {
        jQueryMock.fn.DataTable = undefined;

        jest.advanceTimersByTime(0);
        expect(dataTableFactory).not.toHaveBeenCalled();

        jQueryMock.fn.DataTable = function () {
            return null;
        };
        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);

        jest.advanceTimersByTime(200);
        expect(dataTableFactory).toHaveBeenCalledTimes(1);

        application.stop();
        application = null;

        document.body.innerHTML = `
            <div data-controller="persons-index">
                <table id="persons-table" data-persons-index-target="table"></table>
            </div>
        `;

        application = Application.start();
        application.register("persons-index", PersonsIndexController);

        jest.advanceTimersByTime(0);
        expect(dataTableFactory).toHaveBeenCalledTimes(1);
    });

    test("handles DataTable destroy errors safely on disconnect", () => {
        dataTableApi.destroy.mockImplementation(() => {
            throw new Error("destroy failed");
        });

        jest.advanceTimersByTime(0);

        expect(() => application.stop()).not.toThrow();
        application = null;
    });
});

describe("persons-index controller branch coverage", () => {
    let application;
    let drawMock;
    let dataTableApi;
    let dataTableFactory;
    let jQueryMock;

    const startController = ({
        includeTable = true,
        datatablesUrl = "/admin/persons/datatables",
        includeSearchInput = true,
        includeSelectAll = true,
        includeBulkBar = true,
        includeActionSelect = true,
        includeBulkButton = true,
        includeBulkForm = true,
        includeRows = true,
    } = {}) => {
        const rowsMarkup = includeRows
            ? `
                <tr><td><input type="checkbox" class="person-checkbox" value="101"></td></tr>
                <tr><td><input type="checkbox" class="person-checkbox" value="202"></td></tr>
              `
            : "";

        document.body.innerHTML = `
            <div
                data-controller="persons-index"
                data-persons-index-bulk-delete-url-value="/admin/persons/bulk-delete"
            >
                ${
                    includeBulkBar
                        ? '<div id="persons-bulk-action-bar" data-persons-index-target="bulkBar" style="display: none;"></div>'
                        : ""
                }
                ${
                    includeActionSelect
                        ? `<select id="bulk-action-select-persons" data-persons-index-target="actionSelect">
                        <option value="">Choose...</option>
                        <option value="delete">Delete</option>
                        <option value="archive">Archive</option>
                    </select>`
                        : ""
                }
                ${
                    includeBulkButton
                        ? '<button id="bulk-action-btn-persons" data-persons-index-target="bulkButton" disabled>Go</button>'
                        : ""
                }
                ${
                    includeSearchInput
                        ? '<input id="persons-search" data-persons-index-target="searchInput" />'
                        : ""
                }
                ${
                    includeTable
                        ? `<table
                        id="persons-table"
                        data-persons-index-target="table"
                        ${datatablesUrl ? `data-datatables-url="${datatablesUrl}"` : ""}
                    >
                        <tbody>${rowsMarkup}</tbody>
                    </table>`
                        : ""
                }
                ${
                    includeSelectAll
                        ? '<input type="checkbox" id="select-all-persons" data-persons-index-target="selectAll" />'
                        : ""
                }
                ${
                    includeBulkForm
                        ? '<form id="delete-form-persons-bulk" data-persons-index-target="bulkForm"></form>'
                        : ""
                }
            </div>
        `;

        window.__rhStimulusShowConfirmDelete = jest.fn();

        drawMock = jest.fn();
        dataTableApi = {
            destroy: jest.fn(),
            on: jest.fn(),
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
        };
        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        application = Application.start();
        application.register("persons-index", PersonsIndexController);

        const root = document.querySelector(
            '[data-controller="persons-index"]',
        );

        return {
            getController: async () => {
                for (let i = 0; i < 4; i += 1) {
                    const controller =
                        application.getControllerForElementAndIdentifier(
                            root,
                            "persons-index",
                        ) ||
                        application.controllers.find(
                            (item) => item.identifier === "persons-index",
                        );

                    if (controller) {
                        return controller;
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

        delete window.__rhStimulusShowConfirmDelete;
        delete window.jQuery;
        delete window.$;
        document.body.innerHTML = "";
    });

    test("disconnect timer and listener branches plus jQuery fallback handle", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        const clearTimeoutSpy = jest.spyOn(window, "clearTimeout");

        controller.searchDebounce = 10;
        controller.initTimer = 20;
        controller.dataTablesRetryTimer = 30;
        controller.disconnect();

        expect(clearTimeoutSpy).toHaveBeenCalledWith(10);
        expect(clearTimeoutSpy).toHaveBeenCalledWith(20);
        expect(clearTimeoutSpy).toHaveBeenCalledWith(30);

        const noOptionalMounted = startController({
            includeSearchInput: false,
            includeSelectAll: false,
            includeActionSelect: false,
            includeBulkButton: false,
        });
        jest.advanceTimersByTime(0);
        const noOptionalController = await noOptionalMounted.getController();

        expect(() => noOptionalController.disconnect()).not.toThrow();

        window.jQuery = null;
        window.$ = { marker: "fallback" };
        expect(noOptionalController.jQueryHandle()).toEqual({
            marker: "fallback",
        });

        clearTimeoutSpy.mockRestore();
    });

    test("DataTables availability and init retry/max/table-target guards", async () => {
        const noTableMounted = startController({ includeTable: false });
        jest.advanceTimersByTime(0);
        const noTableController = await noTableMounted.getController();

        const setTimeoutSpy = jest.spyOn(window, "setTimeout");
        noTableController.initDataTableWhenReady();
        expect(setTimeoutSpy).not.toHaveBeenCalled();

        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        window.jQuery = null;
        window.$ = null;
        expect(controller.isDataTablesAvailable()).toBe(false);

        window.$ = { fn: {} };
        expect(controller.isDataTablesAvailable()).toBe(false);

        window.$ = {
            fn: {
                DataTable: {},
            },
        };
        expect(controller.isDataTablesAvailable()).toBe(false);

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        const availSpy = jest
            .spyOn(controller, "isDataTablesAvailable")
            .mockReturnValue(false);

        setTimeoutSpy.mockClear();
        controller.dataTablesRetryCount = 0;
        controller.initDataTableWhenReady();
        expect(controller.dataTablesRetryCount).toBe(1);
        expect(setTimeoutSpy).toHaveBeenCalled();

        if (controller.dataTablesRetryTimer) {
            window.clearTimeout(controller.dataTablesRetryTimer);
            controller.dataTablesRetryTimer = null;
        }

        setTimeoutSpy.mockClear();
        controller.dataTablesRetryCount = 30;
        controller.initDataTableWhenReady();
        expect(setTimeoutSpy).not.toHaveBeenCalled();

        availSpy.mockRestore();
        setTimeoutSpy.mockRestore();
    });

    test("initDataTable handles jq/table guards, missing URL, existing table destroy, and on-listener guard", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        window.jQuery = null;
        window.$ = null;
        controller.initDataTable();

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        const noUrlMounted = startController({ datatablesUrl: "" });
        jest.advanceTimersByTime(0);
        const noUrlController = await noUrlMounted.getController();

        dataTableFactory.mockClear();
        noUrlController.initDataTable();
        expect(dataTableFactory).not.toHaveBeenCalled();

        const destroySpy = jest.spyOn(controller, "destroyTable");
        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => true);
        controller.initDataTable();
        expect(destroySpy).toHaveBeenCalled();

        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);
        dataTableApi.on = undefined;
        expect(() => controller.initDataTable()).not.toThrow();

        destroySpy.mockRestore();
    });

    test("search debounce covers guard, clear, and delayed guard branches", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        controller.dtInstance = null;
        expect(() => controller.onSearchInput()).not.toThrow();

        controller.dtInstance = dataTableApi;
        controller.searchDebounce = 55;
        const clearTimeoutSpy = jest.spyOn(window, "clearTimeout");

        controller.searchInputTarget.value = "Alpha";
        controller.onSearchInput();
        expect(clearTimeoutSpy).toHaveBeenCalledWith(55);

        jest.advanceTimersByTime(250);
        expect(dataTableApi.search).toHaveBeenCalledWith("Alpha");

        const delayedSearchApi = {
            search: jest.fn(() => ({ draw: jest.fn() })),
        };
        const delayedContext = {
            dtInstance: delayedSearchApi,
            hasSearchInputTarget: true,
            searchInputTarget: { value: "Gamma" },
            searchDebounce: null,
        };
        PersonsIndexController.prototype.onSearchInput.call(delayedContext);
        delayedContext.dtInstance = null;
        jest.advanceTimersByTime(250);
        expect(delayedSearchApi.search).not.toHaveBeenCalled();

        dataTableApi.search.mockClear();
        controller.dtInstance = dataTableApi;
        controller.searchInputTarget.value = "Beta";
        controller.onSearchInput();
        jest.advanceTimersByTime(250);
        expect(dataTableApi.search).toHaveBeenCalledWith("Beta");

        clearTimeoutSpy.mockRestore();
    });

    test("checkbox and select-all guards cover missing targets and empty table states", async () => {
        const noTableMounted = startController({ includeTable: false });
        jest.advanceTimersByTime(0);
        const noTableController = await noTableMounted.getController();

        expect(noTableController.checkedCheckboxes()).toEqual([]);

        const noSelectMounted = startController({ includeSelectAll: false });
        jest.advanceTimersByTime(0);
        const noSelectController = await noSelectMounted.getController();
        expect(() => noSelectController.syncSelectAllState()).not.toThrow();

        const emptyMounted = startController({ includeRows: false });
        jest.advanceTimersByTime(0);
        const emptyController = await emptyMounted.getController();
        emptyController.syncSelectAllState();
        expect(emptyController.selectAllTarget.checked).toBe(false);

        const noTableForSelectMounted = startController({
            includeTable: false,
        });
        jest.advanceTimersByTime(0);
        const noTableForSelectController =
            await noTableForSelectMounted.getController();
        expect(() =>
            noTableForSelectController.onSelectAllChange(),
        ).not.toThrow();
    });

    test("bulk button guard paths and fallback formId branch", async () => {
        const mounted = startController({ includeBulkForm: false });
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        controller.onBulkButtonClick();
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        const checkboxes =
            controller.tableTarget.querySelectorAll(".person-checkbox");
        checkboxes[0].checked = true;

        controller.actionSelectTarget.value = "archive";
        controller.onBulkButtonClick();
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        controller.actionSelectTarget.value = "delete";
        const originalBridge = window.__rhStimulusShowConfirmDelete;
        window.__rhStimulusShowConfirmDelete = undefined;
        controller.onBulkButtonClick();
        expect(originalBridge).not.toHaveBeenCalled();

        window.__rhStimulusShowConfirmDelete = jest.fn();
        controller.onBulkButtonClick();
        expect(window.__rhStimulusShowConfirmDelete).toHaveBeenCalledWith(
            expect.objectContaining({
                formId: "delete-form-persons-bulk",
                idsName: "person_ids[]",
                bulkAction: "delete",
            }),
        );
    });
});

function SEARCH_DEBOUNCE_MS() {
    return 250;
}
