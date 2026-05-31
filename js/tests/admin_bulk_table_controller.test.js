/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import AdminBulkTableController from "../controllers/admin_bulk_table_controller.js";

describe("admin-bulk-table controller", () => {
    let application;
    let dataTableApi;
    let dataTableFactory;
    let jQueryMock;

    beforeEach(() => {
        jest.useFakeTimers();

        document.body.innerHTML = `
            <div
                data-controller="admin-bulk-table"
                data-admin-bulk-table-bulk-delete-url-value="/admin/seasons/bulk"
                data-admin-bulk-table-item-type-value="seasons (bulk)"
                data-admin-bulk-table-ids-name-value="season_ids[]"
                data-admin-bulk-table-form-id-value="delete-form-seasons-bulk"
                data-admin-bulk-table-name-column-value="4"
                data-admin-bulk-table-order-column-value="1"
                data-admin-bulk-table-order-direction-value="desc"
            >
                <form id="bulk-action-form" data-admin-bulk-table-target="bulkForm">
                    <select id="bulk-action-select" data-admin-bulk-table-target="actionSelect">
                        <option value="">Choose...</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button
                        id="bulk-action-btn"
                        data-admin-bulk-table-target="actionButton"
                        type="submit"
                        disabled
                    >
                        Go
                    </button>
                </form>

                <table id="seasons-table" data-admin-bulk-table-target="table">
                    <tbody>
                        <tr>
                            <td><input type="checkbox" data-admin-bulk-table-target="selectAll" id="select-all-seasons" /></td>
                            <td>2024</td>
                            <td>2025</td>
                            <td>2024-2025</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="season_ids[]" value="10" data-admin-bulk-table-role="row-checkbox" /></td>
                            <td>2023</td>
                            <td>2024</td>
                            <td>2023-2024</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="season_ids[]" value="11" data-admin-bulk-table-role="row-checkbox" /></td>
                            <td>2022</td>
                            <td>2023</td>
                            <td>2022-2023</td>
                        </tr>
                    </tbody>
                </table>

                <form id="delete-form-seasons-bulk"></form>
            </div>
        `;

        window.__rhStimulusShowConfirmDelete = jest.fn();

        dataTableApi = {
            destroy: jest.fn(),
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
        application.register("admin-bulk-table", AdminBulkTableController);
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

    test("initializes DataTable with configured sort", () => {
        jest.advanceTimersByTime(0);
        expect(dataTableFactory).toHaveBeenCalledTimes(1);

        const config = dataTableFactory.mock.calls[0][0];
        expect(config.pagingType).toBe("simple_numbers");
        expect(config.order).toEqual([[1, "desc"]]);
    });

    test("enables bulk action and opens confirm modal", () => {
        jest.advanceTimersByTime(0);
        const rowCheckboxes = document.querySelectorAll(
            "[data-admin-bulk-table-role='row-checkbox']",
        );
        rowCheckboxes[0].checked = true;
        rowCheckboxes[0].dispatchEvent(new Event("change", { bubbles: true }));

        const actionSelect = document.getElementById("bulk-action-select");
        const actionButton = document.getElementById("bulk-action-btn");

        expect(actionButton.disabled).toBe(true);

        actionSelect.value = "delete";
        actionSelect.dispatchEvent(new Event("change", { bubbles: true }));

        expect(actionButton.disabled).toBe(false);

        document
            .getElementById("bulk-action-form")
            .dispatchEvent(
                new Event("submit", { bubbles: true, cancelable: true }),
            );

        expect(window.__rhStimulusShowConfirmDelete).toHaveBeenCalledTimes(1);
        expect(window.__rhStimulusShowConfirmDelete).toHaveBeenCalledWith(
            expect.objectContaining({
                deleteUrl: "/admin/seasons/bulk",
                itemType: "seasons (bulk)",
                idsName: "season_ids[]",
                formId: "delete-form-seasons-bulk",
                bulkAction: "delete",
                ids: ["10"],
                associated: ["2023-2024"],
            }),
        );
    });

    test("does not destroy DataTable on turbo:before-cache", () => {
        jest.advanceTimersByTime(0);
        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(dataTableApi.destroy).not.toHaveBeenCalled();
    });
});

describe("admin-bulk-table controller branch coverage", () => {
    let application;
    let dataTableApi;
    let dataTableFactory;
    let jQueryMock;
    let pagination;
    let capturedOptions;

    const startController = ({
        includeTable = true,
        includeBulkForm = true,
        includeSelectAll = true,
        includeActionSelect = true,
        includeActionButton = true,
        includeRows = true,
        includeNameColumnValue = true,
        includeOrderColumnValue = true,
        includeOrderDirectionValue = true,
    } = {}) => {
        const rowMarkup = includeRows
            ? `
                <tr>
                    <td><input type="checkbox" name="season_ids[]" value="10" data-admin-bulk-table-role="row-checkbox" /></td>
                    <td>2023</td>
                    <td>2024</td>
                    <td>2023-2024</td>
                </tr>
                <tr>
                    <td><input type="checkbox" name="season_ids[]" value="11" data-admin-bulk-table-role="row-checkbox" /></td>
                    <td>2022</td>
                    <td>2023</td>
                    <td>2022-2023</td>
                </tr>
              `
            : "";

        document.body.innerHTML = `
            <div
                data-controller="admin-bulk-table"
                data-admin-bulk-table-bulk-delete-url-value="/admin/seasons/bulk"
                data-admin-bulk-table-item-type-value="seasons (bulk)"
                data-admin-bulk-table-ids-name-value="season_ids[]"
                data-admin-bulk-table-form-id-value="delete-form-seasons-bulk"
                ${includeNameColumnValue ? 'data-admin-bulk-table-name-column-value="4"' : ""}
                ${includeOrderColumnValue ? 'data-admin-bulk-table-order-column-value="1"' : ""}
                ${includeOrderDirectionValue ? 'data-admin-bulk-table-order-direction-value="desc"' : ""}
            >
                ${
                    includeBulkForm
                        ? `<form id="bulk-action-form" data-admin-bulk-table-target="bulkForm">
                        ${
                            includeActionSelect
                                ? `<select id="bulk-action-select" data-admin-bulk-table-target="actionSelect">
                                <option value="">Choose...</option>
                                <option value="delete">Delete</option>
                                <option value="archive">Archive</option>
                            </select>`
                                : ""
                        }
                        ${
                            includeActionButton
                                ? '<button id="bulk-action-btn" data-admin-bulk-table-target="actionButton" type="submit" disabled>Go</button>'
                                : ""
                        }
                    </form>`
                        : ""
                }

                ${
                    includeTable
                        ? `<table id="seasons-table" data-admin-bulk-table-target="table">
                        <tbody>
                            ${
                                includeSelectAll
                                    ? '<tr><td><input type="checkbox" data-admin-bulk-table-target="selectAll" id="select-all-seasons" /></td><td>2024</td><td>2025</td><td>2024-2025</td></tr>'
                                    : ""
                            }
                            ${rowMarkup}
                        </tbody>
                    </table>`
                        : ""
                }
            </div>
        `;

        window.__rhStimulusShowConfirmDelete = jest.fn();

        dataTableApi = {
            destroy: jest.fn(),
        };
        dataTableFactory = jest.fn((options) => {
            if (options && typeof options.drawCallback === "function") {
                capturedOptions = options;
            }
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
        };
        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        application = Application.start();
        application.register("admin-bulk-table", AdminBulkTableController);

        const root = document.querySelector(
            '[data-controller="admin-bulk-table"]',
        );

        return {
            getController: async () => {
                for (let i = 0; i < 4; i += 1) {
                    const controller =
                        application.getControllerForElementAndIdentifier(
                            root,
                            "admin-bulk-table",
                        ) ||
                        application.controllers.find(
                            (item) => item.identifier === "admin-bulk-table",
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
        capturedOptions = null;
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

    test("disconnect and jQuery fallback branches handle optional targets and timers", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        const clearTimeoutSpy = jest.spyOn(window, "clearTimeout");
        controller.initTimer = 11;
        controller.retryTimer = 22;
        controller.disconnect();

        expect(clearTimeoutSpy).toHaveBeenCalledWith(11);
        expect(clearTimeoutSpy).toHaveBeenCalledWith(22);

        const noBulkFormMounted = startController({ includeBulkForm: false });
        jest.advanceTimersByTime(0);
        const noBulkFormController = await noBulkFormMounted.getController();
        noBulkFormController.initTimer = null;
        noBulkFormController.retryTimer = null;
        expect(() => noBulkFormController.disconnect()).not.toThrow();

        window.jQuery = null;
        window.$ = { marker: "fallback" };
        expect(noBulkFormController.jQueryHandle()).toEqual({
            marker: "fallback",
        });

        clearTimeoutSpy.mockRestore();
    });

    test("initWhenReady covers no-table, retries, max guard, and available path", async () => {
        const noTableMounted = startController({ includeTable: false });
        jest.advanceTimersByTime(0);
        const noTableController = await noTableMounted.getController();

        const setTimeoutSpy = jest.spyOn(window, "setTimeout");
        noTableController.initWhenReady();
        expect(setTimeoutSpy).not.toHaveBeenCalled();

        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        const availSpy = jest
            .spyOn(controller, "isDataTablesAvailable")
            .mockReturnValue(false);

        setTimeoutSpy.mockClear();
        controller.retryCount = 0;
        controller.initWhenReady();
        expect(controller.retryCount).toBe(1);
        expect(setTimeoutSpy).toHaveBeenCalled();

        if (controller.retryTimer) {
            window.clearTimeout(controller.retryTimer);
            controller.retryTimer = null;
        }

        setTimeoutSpy.mockClear();
        controller.retryCount = 60;
        controller.initWhenReady();
        expect(setTimeoutSpy).not.toHaveBeenCalled();

        availSpy.mockRestore();
        setTimeoutSpy.mockRestore();
    });

    test("initTable covers guard returns, existing DataTable, draw callback pagination, and order fallbacks", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        window.jQuery = null;
        window.$ = null;
        expect(() => controller.initTable()).not.toThrow();

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        const existing = { id: "existing" };
        dataTableFactory.mockImplementationOnce(() => existing);
        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => true);
        controller.initTable();
        expect(controller.dtInstance).toBe(existing);

        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);
        controller.initTable();
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
                page: { info: () => ({ pages: 3 }) },
                table: () => ({ container: () => containerRef }),
            }),
        });
        expect(pagination.show).toHaveBeenCalled();

        const noOrderMounted = startController({
            includeOrderColumnValue: false,
        });
        jest.advanceTimersByTime(0);
        const noOrderController = await noOrderMounted.getController();
        dataTableFactory.mockClear();
        noOrderController.initTable();
        expect(dataTableFactory.mock.calls[0][0].order).toBeUndefined();

        const noDirectionMounted = startController({
            includeOrderDirectionValue: false,
        });
        jest.advanceTimersByTime(0);
        const noDirectionController = await noDirectionMounted.getController();
        dataTableFactory.mockClear();
        noDirectionController.initTable();
        expect(dataTableFactory.mock.calls[0][0].order).toEqual([[1, "asc"]]);
    });

    test("destroyTable handles null, successful destroy, and destroy exceptions", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        controller.dtInstance = null;
        expect(() => controller.destroyTable()).not.toThrow();

        controller.dtInstance = {
            destroy: jest.fn(),
        };
        controller.destroyTable();
        expect(controller.dtInstance).toBeNull();

        controller.dtInstance = {
            destroy: jest.fn(() => {
                throw new Error("stale");
            }),
        };
        expect(() => controller.destroyTable()).not.toThrow();
        expect(controller.dtInstance).toBeNull();
    });

    test("onChange and updateBulkButtonState cover guard and select-all branches", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        const updateSpy = jest.spyOn(controller, "updateBulkButtonState");
        controller.selectAllTarget.checked = true;
        controller.onChange({ target: controller.selectAllTarget });
        expect(
            controller
                .rowCheckboxes()
                .every((checkbox) => checkbox.checked === true),
        ).toBe(true);

        controller.onChange({ target: controller.actionSelectTarget });
        controller.onChange({ target: controller.rowCheckboxes()[0] });
        controller.onChange({ target: document.createElement("button") });

        expect(updateSpy).toHaveBeenCalledTimes(3);
        updateSpy.mockRestore();

        const noButtonMounted = startController({ includeActionButton: false });
        jest.advanceTimersByTime(0);
        const noButtonController = await noButtonMounted.getController();
        expect(() => noButtonController.updateBulkButtonState()).not.toThrow();
    });

    test("onSubmit guards and extractRowName branches include helper availability and missing row/cell", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        const event = { preventDefault: jest.fn() };
        controller.actionSelectTarget.value = "";
        controller.onSubmit(event);
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        controller.actionSelectTarget.value = "archive";
        controller.onSubmit(event);
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        controller.actionSelectTarget.value = "delete";
        controller.onSubmit(event);
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        controller.rowCheckboxes()[0].checked = true;
        const originalBridge = window.__rhStimulusShowConfirmDelete;
        window.__rhStimulusShowConfirmDelete = undefined;
        controller.onSubmit(event);
        expect(originalBridge).not.toHaveBeenCalled();

        window.__rhStimulusShowConfirmDelete = jest.fn();
        controller.onSubmit(event);
        expect(window.__rhStimulusShowConfirmDelete).toHaveBeenCalledWith(
            expect.objectContaining({
                ids: ["10"],
                associated: ["2023-2024"],
            }),
        );

        const orphanCheckbox = document.createElement("input");
        orphanCheckbox.type = "checkbox";
        orphanCheckbox.value = "999";
        expect(controller.extractRowName(orphanCheckbox)).toBe("");

        const row = document.createElement("tr");
        const firstCell = document.createElement("td");
        firstCell.textContent = "Ignore";
        const secondCell = document.createElement("td");
        secondCell.textContent = "Name Value";
        row.appendChild(firstCell);
        row.appendChild(secondCell);
        const nestedCheckbox = document.createElement("input");
        row.appendChild(nestedCheckbox);

        const noNameValue =
            AdminBulkTableController.prototype.extractRowName.call(
                {
                    hasNameColumnValue: false,
                },
                nestedCheckbox,
            );
        expect(noNameValue).toBe("Name Value");

        const missingCell =
            AdminBulkTableController.prototype.extractRowName.call(
                {
                    hasNameColumnValue: true,
                    nameColumnValue: 5,
                },
                nestedCheckbox,
            );
        expect(missingCell).toBe("");
    });
});
