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

        window.showConfirmDelete = jest.fn();

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

        delete window.showConfirmDelete;
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

        expect(window.showConfirmDelete).toHaveBeenCalledTimes(1);
        expect(window.showConfirmDelete).toHaveBeenCalledWith(
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
});

function SEARCH_DEBOUNCE_MS() {
    return 250;
}
