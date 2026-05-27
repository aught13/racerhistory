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
