/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import AdminUsersIndexController from "../controllers/admin_users_index_controller.js";

describe("admin-users-index controller", () => {
    let application;
    let dataTableApi;
    let dataTableFactory;
    let jQueryMock;
    let bulkForm;

    beforeEach(() => {
        jest.useFakeTimers();

        document.body.innerHTML = `
            <div
                data-controller="admin-users-index"
                data-admin-users-index-bulk-activate-url-value="/admin/users/bulkActivate"
                data-admin-users-index-bulk-delete-url-value="/admin/users/bulkDelete"
                data-admin-users-index-delete-form-id-value="delete-form-users-bulk"
            >
                <form id="bulk-action-form" data-admin-users-index-target="bulkForm">
                    <select id="bulk-action-select" data-admin-users-index-target="actionSelect">
                        <option value="">Choose...</option>
                        <option value="approve">Approve</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button
                        id="bulk-action-btn"
                        data-admin-users-index-target="actionButton"
                        disabled
                        type="submit"
                    >
                        Go
                    </button>
                </form>

                <table id="users-table" data-admin-users-index-target="pendingTable">
                    <thead>
                        <tr>
                            <th>
                                <input id="select-all-users" data-admin-users-index-target="selectAll" type="checkbox" />
                            </th>
                            <th>Username</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input class="user-checkbox" data-admin-users-index-role="row-checkbox" name="user_ids[]" type="checkbox" value="2" /></td>
                            <td>new_user</td>
                        </tr>
                    </tbody>
                </table>

                <table id="search-users-table" data-admin-users-index-target="searchTable">
                    <tbody>
                        <tr><td>admin</td></tr>
                    </tbody>
                </table>

                <form id="delete-form-users-bulk"></form>
            </div>
        `;

        bulkForm = document.getElementById("bulk-action-form");
        bulkForm.submit = jest.fn();
        bulkForm.requestSubmit = undefined;

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
        application.register("admin-users-index", AdminUsersIndexController);
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

    test("initializes pending and search users tables", () => {
        jest.advanceTimersByTime(0);
        expect(dataTableFactory).toHaveBeenCalledTimes(2);

        const pendingConfig = dataTableFactory.mock.calls[0][0];
        const searchConfig = dataTableFactory.mock.calls[1][0];
        expect(pendingConfig.pagingType).toBe("simple_numbers");
        expect(searchConfig.pagingType).toBe("simple_numbers");
    });

    test("submits bulk form for approve action", () => {
        jest.advanceTimersByTime(0);
        const rowCheckbox = document.querySelector(
            "[data-admin-users-index-role='row-checkbox']",
        );
        const actionSelect = document.getElementById("bulk-action-select");
        const actionButton = document.getElementById("bulk-action-btn");

        rowCheckbox.checked = true;
        rowCheckbox.dispatchEvent(new Event("change", { bubbles: true }));

        actionSelect.value = "approve";
        actionSelect.dispatchEvent(new Event("change", { bubbles: true }));

        expect(actionButton.disabled).toBe(false);

        bulkForm.dispatchEvent(
            new Event("submit", { bubbles: true, cancelable: true }),
        );

        expect(bulkForm.action).toContain("/admin/users/bulkActivate");
        expect(bulkForm.submit).toHaveBeenCalledTimes(1);
    });

    test("opens confirm modal for delete action", () => {
        jest.advanceTimersByTime(0);
        const rowCheckbox = document.querySelector(
            "[data-admin-users-index-role='row-checkbox']",
        );
        const actionSelect = document.getElementById("bulk-action-select");

        rowCheckbox.checked = true;
        rowCheckbox.dispatchEvent(new Event("change", { bubbles: true }));

        actionSelect.value = "delete";
        actionSelect.dispatchEvent(new Event("change", { bubbles: true }));

        bulkForm.dispatchEvent(
            new Event("submit", { bubbles: true, cancelable: true }),
        );

        expect(window.__rhStimulusShowConfirmDelete).toHaveBeenCalledTimes(1);
        expect(window.__rhStimulusShowConfirmDelete).toHaveBeenCalledWith(
            expect.objectContaining({
                deleteUrl: "/admin/users/bulkDelete",
                itemType: "users (bulk)",
                ids: ["2"],
                associated: ["new_user"],
                idsName: "user_ids[]",
                formId: "delete-form-users-bulk",
                bulkAction: "delete",
            }),
        );
    });

    test("does not destroy DataTables on turbo:before-cache", () => {
        jest.advanceTimersByTime(0);
        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(dataTableApi.destroy).not.toHaveBeenCalled();
    });
});

describe("admin-users-index controller branch coverage", () => {
    let application;
    let dataTableApi;
    let dataTableFactory;
    let jQueryMock;
    let pagination;
    let capturedOptions;

    const startController = ({
        includeBulkForm = true,
        includePendingTable = true,
        includeSearchTable = true,
        includeActionSelect = true,
        includeActionButton = true,
        includeSelectAll = true,
        includeRows = true,
    } = {}) => {
        const selectAllCell = includeSelectAll
            ? '<input id="select-all-users" data-admin-users-index-target="selectAll" type="checkbox" />'
            : "";
        const rowMarkup = includeRows
            ? `
                <tr>
                    <td><input class="user-checkbox" data-admin-users-index-role="row-checkbox" name="user_ids[]" type="checkbox" value="2" /></td>
                    <td>new_user</td>
                </tr>
                <tr>
                    <td><input class="user-checkbox" data-admin-users-index-role="row-checkbox" name="user_ids[]" type="checkbox" value="9" /></td>
                </tr>
              `
            : "";

        document.body.innerHTML = `
            <div
                data-controller="admin-users-index"
                data-admin-users-index-bulk-activate-url-value="/admin/users/bulkActivate"
                data-admin-users-index-bulk-delete-url-value="/admin/users/bulkDelete"
                data-admin-users-index-delete-form-id-value="delete-form-users-bulk"
            >
                ${
                    includeBulkForm
                        ? `<form id="bulk-action-form" data-admin-users-index-target="bulkForm">
                    ${
                        includeActionSelect
                            ? `<select id="bulk-action-select" data-admin-users-index-target="actionSelect">
                                <option value="">Choose...</option>
                                <option value="approve">Approve</option>
                                <option value="delete">Delete</option>
                                <option value="noop">No-op</option>
                            </select>`
                            : ""
                    }
                    ${
                        includeActionButton
                            ? '<button id="bulk-action-btn" data-admin-users-index-target="actionButton" disabled type="submit">Go</button>'
                            : ""
                    }
                </form>`
                        : ""
                }

                ${
                    includePendingTable
                        ? `<table id="users-table" data-admin-users-index-target="pendingTable">
                    <thead>
                        <tr>
                            <th>${selectAllCell}</th>
                            <th>Username</th>
                        </tr>
                    </thead>
                    <tbody>${rowMarkup}</tbody>
                </table>`
                        : ""
                }

                ${
                    includeSearchTable
                        ? `<table id="search-users-table" data-admin-users-index-target="searchTable"><tbody><tr><td>admin</td></tr></tbody></table>`
                        : ""
                }
            </div>
        `;

        dataTableApi = {
            destroy: jest.fn(),
            search: jest.fn(() => dataTableApi),
            draw: jest.fn(),
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

        window.__rhStimulusShowConfirmDelete = jest.fn();
        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        application = Application.start();
        application.register("admin-users-index", AdminUsersIndexController);

        const root = document.querySelector(
            '[data-controller="admin-users-index"]',
        );

        return {
            getController: async () => {
                for (let i = 0; i < 4; i += 1) {
                    const controller =
                        application.getControllerForElementAndIdentifier(
                            root,
                            "admin-users-index",
                        ) ||
                        application.controllers.find(
                            (item) => item.identifier === "admin-users-index",
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

    test("connect/disconnect and DataTables readiness retries cover guard branches", async () => {
        const mounted = startController({ includeBulkForm: false });
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
        window.$.fn.DataTable.isDataTable = () => false;
        expect(controller.jQueryHandle()).toBe(window.$);
        expect(controller.isDataTablesAvailable()).toBe(true);

        const setTimeoutSpy = jest.spyOn(window, "setTimeout");
        const availableSpy = jest
            .spyOn(controller, "isDataTablesAvailable")
            .mockReturnValue(false);

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

        availableSpy.mockRestore();
        setTimeoutSpy.mockRestore();

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        const clearTimeoutSpy = jest.spyOn(window, "clearTimeout");
        controller.initTimer = 101;
        controller.retryTimer = 202;
        controller.disconnect();

        expect(clearTimeoutSpy).toHaveBeenCalledWith(101);
        expect(clearTimeoutSpy).toHaveBeenCalledWith(202);
        expect(controller.initTimer).toBeNull();
        expect(controller.retryTimer).toBeNull();

        clearTimeoutSpy.mockRestore();
    });

    test("initTables and initTable cover jq guards, existing table, and draw callback pagination", async () => {
        const mounted = startController({
            includePendingTable: false,
            includeSearchTable: false,
        });
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        const initTableSpy = jest.spyOn(controller, "initTable");

        window.jQuery = null;
        window.$ = null;
        controller.initTables();

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;
        controller.initTables();
        expect(initTableSpy).not.toHaveBeenCalled();

        const table = document.createElement("table");
        const existing = { id: "existing" };
        dataTableFactory.mockImplementationOnce(() => existing);
        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => true);
        expect(controller.initTable(table, jQueryMock)).toBe(existing);

        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);
        controller.initTable(table, jQueryMock);
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

        initTableSpy.mockRestore();
    });

    test("destroyTables handles null and stale instances safely", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        controller.pendingDt = null;
        controller.searchDt = {
            destroy: jest.fn(() => {
                throw new Error("stale table");
            }),
        };

        expect(() => controller.destroyTables()).not.toThrow();
        expect(controller.pendingDt).toBeNull();
        expect(controller.searchDt).toBeNull();
    });

    test("onChange covers select-all, action-select, row checkbox, and no bulk form guards", async () => {
        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        const updateSpy = jest.spyOn(controller, "updateBulkButtonState");
        const rowCheckboxes = controller.rowCheckboxes();

        controller.selectAllTarget.checked = true;
        controller.onChange({ target: controller.selectAllTarget });
        expect(rowCheckboxes.every((checkbox) => checkbox.checked)).toBe(true);

        controller.onChange({ target: controller.actionSelectTarget });
        controller.onChange({ target: rowCheckboxes[0] });

        const unrelated = document.createElement("button");
        controller.onChange({ target: unrelated });

        expect(updateSpy).toHaveBeenCalledTimes(3);
        updateSpy.mockRestore();

        const noBulkMounted = startController({ includeBulkForm: false });
        jest.advanceTimersByTime(0);
        const noBulkController = await noBulkMounted.getController();
        const noBulkUpdateSpy = jest.spyOn(
            noBulkController,
            "updateBulkButtonState",
        );

        noBulkController.onChange({ target: document.createElement("div") });
        expect(noBulkUpdateSpy).not.toHaveBeenCalled();

        noBulkUpdateSpy.mockRestore();
    });

    test("onSubmit covers missing target guards and approve/delete/noop branches", async () => {
        const guardMounted = startController({ includeBulkForm: false });
        jest.advanceTimersByTime(0);
        const guardController = await guardMounted.getController();
        const guardEvent = { preventDefault: jest.fn() };
        guardController.onSubmit(guardEvent);
        expect(guardEvent.preventDefault).toHaveBeenCalled();

        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();
        const event = { preventDefault: jest.fn() };

        controller.actionSelectTarget.value = "";
        controller.onSubmit(event);
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        controller.actionSelectTarget.value = "approve";
        controller.bulkFormTarget.requestSubmit = jest.fn();
        controller.bulkFormTarget.submit = jest.fn();
        controller.onSubmit(event);
        expect(controller.bulkFormTarget.requestSubmit).toHaveBeenCalledTimes(
            1,
        );

        controller.bulkFormTarget.requestSubmit = undefined;
        controller.onSubmit(event);
        expect(controller.bulkFormTarget.submit).toHaveBeenCalledTimes(1);

        controller.actionSelectTarget.value = "noop";
        controller.onSubmit(event);
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        controller.actionSelectTarget.value = "delete";
        controller.rowCheckboxes().forEach((checkbox) => {
            checkbox.checked = false;
        });
        controller.onSubmit(event);
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        controller.rowCheckboxes().forEach((checkbox) => {
            checkbox.checked = true;
        });
        const originalDeleteBridge = window.__rhStimulusShowConfirmDelete;
        window.__rhStimulusShowConfirmDelete = undefined;
        controller.onSubmit(event);
        expect(originalDeleteBridge).not.toHaveBeenCalled();

        window.__rhStimulusShowConfirmDelete = jest.fn();
        controller.onSubmit(event);
        expect(window.__rhStimulusShowConfirmDelete).toHaveBeenCalledWith(
            expect.objectContaining({
                ids: ["2", "9"],
                associated: ["new_user", ""],
                idsName: "user_ids[]",
                bulkAction: "delete",
            }),
        );
    });

    test("updateBulkButtonState handles guard and enabled/disabled transitions", async () => {
        const noButtonMounted = startController({ includeActionButton: false });
        jest.advanceTimersByTime(0);
        const noButtonController = await noButtonMounted.getController();
        expect(() => noButtonController.updateBulkButtonState()).not.toThrow();

        const noRowsMounted = startController({ includeRows: false });
        jest.advanceTimersByTime(0);
        const noRowsController = await noRowsMounted.getController();
        expect(noRowsController.rowCheckboxes()).toEqual([]);

        const mounted = startController();
        jest.advanceTimersByTime(0);
        const controller = await mounted.getController();

        controller.actionSelectTarget.value = "";
        controller.updateBulkButtonState();
        expect(controller.actionButtonTarget.disabled).toBe(true);

        controller.rowCheckboxes()[0].checked = true;
        controller.actionSelectTarget.value = "approve";
        controller.updateBulkButtonState();
        expect(controller.actionButtonTarget.disabled).toBe(false);
    });
});
