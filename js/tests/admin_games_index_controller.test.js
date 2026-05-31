/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import AdminGamesIndexController from "../controllers/admin_games_index_controller.js";

describe("admin-games-index controller", () => {
    let application;
    let dataTableApi;
    let dataTableFactory;
    let jQueryMock;

    beforeEach(() => {
        jest.useFakeTimers();

        document.body.innerHTML = `
            <div
                data-controller="admin-games-index"
                data-admin-games-index-ajax-url-value="/admin/games/ajaxList"
                data-admin-games-index-bulk-delete-url-value="/admin/games/bulk"
                data-admin-games-index-delete-form-id-value="delete-form-games-bulk"
            >
                <form id="bulk-action-form-games" data-admin-games-index-target="bulkForm">
                    <select id="bulk-action-select" data-admin-games-index-target="actionSelect">
                        <option value="">Choose...</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button id="bulk-action-btn" data-admin-games-index-target="actionButton" disabled type="submit">
                        Go
                    </button>
                </form>

                <table id="games-table" data-admin-games-index-target="table">
                    <thead>
                        <tr>
                            <th>
                                <input
                                    id="select-all-games"
                                    data-admin-games-index-target="selectAll"
                                    type="checkbox"
                                />
                            </th>
                            <th>Date</th>
                            <th>Team Season</th>
                            <th>H/R/N</th>
                            <th>Opponent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input class="game-checkbox" type="checkbox" value="1" /></td>
                            <td>2024-01-10</td>
                            <td>Racers 2023-2024</td>
                            <td>H</td>
                            <td>Belmont</td>
                        </tr>
                    </tbody>
                </table>

                <form id="delete-form-games-bulk"></form>
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
            dataTable: {
                SearchBuilder: function () {
                    return null;
                },
            },
        };
        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;

        application = Application.start();
        application.register("admin-games-index", AdminGamesIndexController);
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

    test("initializes DataTable with SearchBuilder options", () => {
        jest.advanceTimersByTime(0);
        expect(dataTableFactory).toHaveBeenCalledTimes(1);

        const config = dataTableFactory.mock.calls[0][0];
        expect(config.serverSide).toBe(true);
        expect(config.ajax.url).toBe("/admin/games/ajaxList");
        expect(config.dom).toBe("Qlfrtip");
        expect(config.searchBuilder.depthLimit).toBe(2);
    });

    test("updates bulk action state and handles select all", () => {
        jest.advanceTimersByTime(0);
        const selectAll = document.getElementById("select-all-games");
        const actionSelect = document.getElementById("bulk-action-select");
        const actionButton = document.getElementById("bulk-action-btn");

        actionSelect.value = "delete";
        actionSelect.dispatchEvent(new Event("change", { bubbles: true }));

        expect(actionButton.disabled).toBe(true);

        selectAll.checked = true;
        selectAll.dispatchEvent(new Event("change", { bubbles: true }));

        expect(actionButton.disabled).toBe(false);
    });

    test("opens confirm modal for bulk delete", () => {
        jest.advanceTimersByTime(0);
        const rowCheckbox = document.querySelector(".game-checkbox");
        const actionSelect = document.getElementById("bulk-action-select");
        const bulkForm = document.getElementById("bulk-action-form-games");

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
                deleteUrl: "/admin/games/bulk",
                itemType: "games (bulk)",
                ids: ["1"],
                associated: ["2024-01-10 vs Belmont"],
                idsName: "game_ids[]",
                formId: "delete-form-games-bulk",
                bulkAction: "delete",
            }),
        );
    });

    test("does not destroy DataTable on turbo:before-cache", () => {
        jest.advanceTimersByTime(0);
        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(dataTableApi.destroy).not.toHaveBeenCalled();
    });
});

describe("admin-games-index controller branch coverage", () => {
    let application;
    let jQueryMock;
    let dataTableFactory;
    let dataTableApi;

    const renderFixture = ({
        includeTableTarget = true,
        includeBulkFormTarget = true,
        includeSelectAllTarget = true,
        includeActionTargets = true,
    } = {}) => {
        document.body.innerHTML = `
            <div
                data-controller="admin-games-index"
                data-admin-games-index-ajax-url-value="/admin/games/ajaxList"
                data-admin-games-index-bulk-delete-url-value="/admin/games/bulk"
                data-admin-games-index-delete-form-id-value="delete-form-games-bulk"
            >
                <form id="bulk-action-form-games" ${includeBulkFormTarget ? 'data-admin-games-index-target="bulkForm"' : ""}>
                    <select id="bulk-action-select" ${includeActionTargets ? 'data-admin-games-index-target="actionSelect"' : ""}>
                        <option value="">Choose...</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button id="bulk-action-btn" ${includeActionTargets ? 'data-admin-games-index-target="actionButton"' : ""} disabled type="submit">
                        Go
                    </button>
                </form>

                <table id="games-table" ${includeTableTarget ? 'data-admin-games-index-target="table"' : ""}>
                    <thead>
                        <tr>
                            <th>
                                <input
                                    id="select-all-games"
                                    ${includeSelectAllTarget ? 'data-admin-games-index-target="selectAll"' : ""}
                                    type="checkbox"
                                />
                            </th>
                            <th>Date</th>
                            <th>Team Season</th>
                            <th>H/R/N</th>
                            <th>Opponent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input class="game-checkbox" type="checkbox" value="1" /></td>
                            <td>2024-01-10</td>
                            <td>Racers 2023-2024</td>
                            <td>H</td>
                            <td>Belmont</td>
                        </tr>
                    </tbody>
                </table>

                <form id="delete-form-games-bulk"></form>
            </div>
        `;
    };

    const startController = async (options = {}) => {
        if (application) {
            application.stop();
            application = null;
        }

        renderFixture(options);

        application = Application.start();
        application.register("admin-games-index", AdminGamesIndexController);

        const root = document.querySelector(
            '[data-controller="admin-games-index"]',
        );
        for (let i = 0; i < 4; i += 1) {
            const controller =
                application.getControllerForElementAndIdentifier(
                    root,
                    "admin-games-index",
                ) ||
                application.controllers.find(
                    (item) => item.identifier === "admin-games-index",
                );
            if (controller) {
                return controller;
            }

            await Promise.resolve();
        }

        return undefined;
    };

    beforeEach(() => {
        jest.useFakeTimers();

        dataTableApi = {
            settings: jest.fn(() => [
                { jqXHR: { readyState: 1, abort: jest.fn() } },
            ]),
            destroy: jest.fn(),
        };
        dataTableFactory = jest.fn(() => dataTableApi);

        jQueryMock = jest.fn(() => ({
            DataTable: dataTableFactory,
        }));
        jQueryMock.fn = {
            DataTable: function DataTable() {
                return null;
            },
            dataTable: {
                SearchBuilder: function SearchBuilder() {
                    return null;
                },
            },
        };
        jQueryMock.fn.DataTable.isDataTable = jest.fn(() => false);

        window.jQuery = jQueryMock;
        window.$ = jQueryMock;
        window.__rhStimulusShowConfirmDelete = jest.fn();
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.__rhStimulusShowConfirmDelete;
        delete window.jQuery;
        delete window.$;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
        jest.useRealTimers();
    });

    test("retry/disconnect branches and helper fallbacks", async () => {
        const controller = await startController({ includeTableTarget: false });

        expect(controller.jQueryHandle()).toBe(jQueryMock);
        delete window.jQuery;
        expect(controller.jQueryHandle()).toBe(jQueryMock);
        delete window.$;
        expect(controller.jQueryHandle()).toBeNull();
        expect(controller.isDataTablesAvailable()).toBe(false);
        expect(controller.hasRequiredExtensions()).toBe(false);

        window.$ = jQueryMock;
        controller.retryCount = 60;
        controller.initWhenReady();
        expect(controller.retryTimer).toBeNull();

        controller.retryCount = 0;
        controller.initWhenReady();
        expect(controller.retryCount).toBe(1);
        expect(controller.retryTimer).not.toBeNull();

        const earlyController = await startController();
        expect(earlyController.initTimer).not.toBeNull();
        earlyController.disconnect();
        expect(earlyController.initTimer).toBeNull();
    });

    test("initTable existing instance and missing dependency guards", async () => {
        const controller = await startController();
        jest.advanceTimersByTime(0);

        jQueryMock.fn.DataTable.isDataTable.mockReturnValue(true);
        controller.initTable();
        expect(dataTableFactory).toHaveBeenCalled();

        delete window.jQuery;
        delete window.$;
        expect(() => controller.initTable()).not.toThrow();

        const noTableController = await startController({
            includeTableTarget: false,
        });
        expect(() => noTableController.initTable()).not.toThrow();
    });

    test("destroyTable handles abort/no-abort and stale instance exceptions", async () => {
        const controller = await startController();

        controller.dtInstance = null;
        controller.destroyTable();

        const pendingAbort = jest.fn();
        controller.dtInstance = {
            settings: jest.fn(() => [
                { jqXHR: { readyState: 1, abort: pendingAbort } },
            ]),
            destroy: jest.fn(),
        };
        controller.destroyTable();
        expect(pendingAbort).toHaveBeenCalledTimes(1);

        const finalizedAbort = jest.fn();
        const finalizedDestroy = jest.fn();
        controller.dtInstance = {
            settings: jest.fn(() => [
                { jqXHR: { readyState: 4, abort: finalizedAbort } },
            ]),
            destroy: finalizedDestroy,
        };
        controller.destroyTable();
        expect(finalizedAbort).not.toHaveBeenCalled();
        expect(finalizedDestroy).toHaveBeenCalledWith(false);

        controller.dtInstance = {
            settings: jest.fn(() => {
                throw new Error("stale settings");
            }),
            destroy: jest.fn(() => {
                throw new Error("stale destroy");
            }),
        };
        expect(() => controller.destroyTable()).not.toThrow();
        expect(controller.dtInstance).toBeNull();
    });

    test("onChange/onSubmit/update state guard matrix", async () => {
        const controller = await startController();
        jest.advanceTimersByTime(0);

        const updateSpy = jest.spyOn(controller, "updateBulkButtonState");
        controller.onChange({ target: document.createElement("div") });
        expect(updateSpy).not.toHaveBeenCalled();

        const actionSelect = document.getElementById("bulk-action-select");
        controller.onChange({ target: actionSelect });
        expect(updateSpy).toHaveBeenCalled();

        const submitEvent = {
            preventDefault: jest.fn(),
        };

        actionSelect.value = "";
        controller.onSubmit(submitEvent);
        expect(submitEvent.preventDefault).toHaveBeenCalled();
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        actionSelect.value = "delete";
        controller.onSubmit(submitEvent);
        expect(window.__rhStimulusShowConfirmDelete).not.toHaveBeenCalled();

        const rowCheckbox = document.querySelector(".game-checkbox");
        rowCheckbox.checked = true;

        const savedConfirm = window.__rhStimulusShowConfirmDelete;
        delete window.__rhStimulusShowConfirmDelete;
        controller.onSubmit(submitEvent);
        expect(savedConfirm).not.toHaveBeenCalled();
        window.__rhStimulusShowConfirmDelete = savedConfirm;

        const detached = document.createElement("input");
        detached.className = "game-checkbox";
        detached.type = "checkbox";
        detached.value = "44";
        detached.checked = true;
        controller.element.appendChild(detached);
        controller.onSubmit(submitEvent);
        expect(window.__rhStimulusShowConfirmDelete).toHaveBeenCalledWith(
            expect.objectContaining({
                ids: expect.arrayContaining(["44"]),
                associated: expect.arrayContaining([""]),
            }),
        );

        const noActionTargetsController = await startController({
            includeActionTargets: false,
        });
        expect(() =>
            noActionTargetsController.updateBulkButtonState(),
        ).not.toThrow();

        expect(() =>
            noActionTargetsController.onSubmit({ preventDefault: jest.fn() }),
        ).not.toThrow();
    });
});
