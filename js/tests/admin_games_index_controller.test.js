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

        window.showConfirmDelete = jest.fn();

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

        delete window.showConfirmDelete;
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

        expect(window.showConfirmDelete).toHaveBeenCalledTimes(1);
        expect(window.showConfirmDelete).toHaveBeenCalledWith(
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
