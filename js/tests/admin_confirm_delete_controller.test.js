/* global HTMLFormElement, afterEach, beforeEach, describe, expect, global, jest, test */

import { Application } from "@hotwired/stimulus";

import AdminConfirmDeleteController from "../controllers/admin_confirm_delete_controller.js";

describe("admin-confirm-delete controller", () => {
    let application;
    let showSpy;
    let requestSubmitMock;

    beforeEach(() => {
        document.body.innerHTML = `
            <div
                id="confirm-delete-modal"
                data-controller="admin-confirm-delete"
                data-action="show.bs.modal->admin-confirm-delete#onShow"
            >
                <ul id="confirm-delete-modal-assoc" data-admin-confirm-delete-target="associated"></ul>
                <form id="confirm-delete-modal-hidden-form" data-admin-confirm-delete-target="hiddenForm">
                    <input type="hidden" name="_csrfToken" value="test-token" />
                </form>
                <button id="confirm-delete-modal-delete-btn" data-action="admin-confirm-delete#confirmDelete" type="button">Delete</button>
            </div>
            <form id="delete-form-seasons-bulk" action="/admin/seasons/source-action"></form>
        `;

        showSpy = jest.fn();
        global.bootstrap = {
            Modal: {
                getOrCreateInstance: jest.fn(() => ({ show: showSpy })),
            },
        };

        requestSubmitMock = jest.fn();
        Object.defineProperty(HTMLFormElement.prototype, "requestSubmit", {
            configurable: true,
            writable: true,
            value: requestSubmitMock,
        });

        application = Application.start();
        application.register("admin-confirm-delete", AdminConfirmDeleteController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete global.bootstrap;
        delete window.__rhStimulusShowConfirmDelete;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("populates associated list from bootstrap trigger dataset", async () => {
        const modal = document.getElementById("confirm-delete-modal");
        const trigger = document.createElement("button");
        trigger.dataset.deleteUrl = "/admin/seasons/delete/10";
        trigger.dataset.associated = '["Season 2023-2024"]';

        await Promise.resolve();
        const event = new Event("show.bs.modal");
        event.relatedTarget = trigger;
        modal.dispatchEvent(event);

        const list = document.getElementById("confirm-delete-modal-assoc");
        expect(list.children).toHaveLength(1);
        expect(list.children[0].textContent).toBe("Season 2023-2024");
    });

    test("exposes the Stimulus confirm-delete bridge", async () => {
        await Promise.resolve();

        expect(typeof window.__rhStimulusShowConfirmDelete).toBe("function");
        window.__rhStimulusShowConfirmDelete({
            deleteUrl: "/admin/teams/delete/1",
            associated: ["Team A"],
        });

        expect(showSpy).toHaveBeenCalledTimes(1);
        const list = document.getElementById("confirm-delete-modal-assoc");
        expect(list.children).toHaveLength(1);
        expect(list.children[0].textContent).toBe("Team A");
    });

    test("submits source form when formId is provided", async () => {
        await Promise.resolve();

        window.__rhStimulusShowConfirmDelete({
            deleteUrl: "/admin/seasons/delete/bulk",
            ids: [10, 11],
            idsName: "season_ids[]",
            formId: "delete-form-seasons-bulk",
            bulkAction: "delete",
        });

        document.getElementById("confirm-delete-modal-delete-btn").click();

        const sourceForm = document.getElementById("delete-form-seasons-bulk");
        expect(sourceForm.action).toContain("/admin/seasons/source-action");
        expect(requestSubmitMock).toHaveBeenCalled();
        expect(
            sourceForm.querySelectorAll('input[name="season_ids[]"]').length,
        ).toBe(2);
        expect(
            sourceForm.querySelector('input[name="bulk_action"]').value,
        ).toBe("delete");
    });

    test("falls back to temporary form with tokens when source form is missing", async () => {
        await Promise.resolve();

        window.__rhStimulusShowConfirmDelete({
            deleteUrl: "/admin/users/bulk",
            ids: "[7,8]",
            idsName: "user_ids[]",
            bulkAction: "delete",
            formId: "missing-form",
        });

        document.getElementById("confirm-delete-modal-delete-btn").click();

        const forms = Array.from(document.querySelectorAll("form"));
        const tempForm = forms[forms.length - 1];
        expect(tempForm.action).toContain("/admin/users/bulk");
        expect(tempForm.querySelector('input[name="_csrfToken"]').value).toBe(
            "test-token",
        );
        expect(tempForm.querySelectorAll('input[name="user_ids[]"]').length).toBe(
            2,
        );
        expect(tempForm.querySelector('input[name="bulk_action"]').value).toBe(
            "delete",
        );
    });
});
