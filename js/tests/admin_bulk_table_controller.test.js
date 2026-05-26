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

        delete window.showConfirmDelete;
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

        expect(window.showConfirmDelete).toHaveBeenCalledTimes(1);
        expect(window.showConfirmDelete).toHaveBeenCalledWith(
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
