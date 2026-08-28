/* global HTMLFormElement, afterEach, beforeEach, describe, expect, global, jest, test */

import { Application } from "@hotwired/stimulus";

import AdminConfirmDeleteController from "../controllers/admin_confirm_delete_controller.js";

describe("admin-confirm-delete controller", () => {
    let application;
    let showSpy;
    let requestSubmitMock;

    const getController = async () => {
        const root = document.getElementById("confirm-delete-modal");

        for (let i = 0; i < 4; i += 1) {
            const controller =
                application.getControllerForElementAndIdentifier(
                    root,
                    "admin-confirm-delete",
                ) ||
                application.controllers.find(
                    (item) => item.identifier === "admin-confirm-delete",
                );

            if (controller) {
                return controller;
            }

            await Promise.resolve();
        }

        return undefined;
    };

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
        application.register(
            "admin-confirm-delete",
            AdminConfirmDeleteController,
        );
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
        expect(
            tempForm.querySelectorAll('input[name="user_ids[]"]').length,
        ).toBe(2);
        expect(tempForm.querySelector('input[name="bulk_action"]').value).toBe(
            "delete",
        );
    });

    test("ignores onShow without related target and prefers deleteAssociated", async () => {
        const controller = await getController();
        const setContextSpy = jest.spyOn(controller, "setContext");
        const modal = document.getElementById("confirm-delete-modal");

        modal.dispatchEvent(new Event("show.bs.modal"));
        expect(setContextSpy).not.toHaveBeenCalled();

        const trigger = document.createElement("button");
        trigger.dataset.associated = '["Fallback"]';
        trigger.dataset.deleteAssociated = '["Preferred"]';
        trigger.dataset.deleteUrl = "/admin/anything/delete/1";
        const showEvent = new Event("show.bs.modal");
        showEvent.relatedTarget = trigger;
        modal.dispatchEvent(showEvent);

        expect(controller.modalContext.associated).toBe('["Preferred"]');

        setContextSpy.mockRestore();
    });

    test("open falls back to display block without bootstrap and defaults empty context", async () => {
        const controller = await getController();
        delete global.bootstrap;

        controller.element.style.display = "";
        controller.open();

        expect(controller.modalContext).toEqual({});
        expect(controller.element.style.display).toBe("block");
    });

    test("disconnect only removes bridge when handler matches", async () => {
        const controller = await getController();
        const otherHandler = () => {};

        window.__rhStimulusShowConfirmDelete = otherHandler;
        controller.disconnect();
        expect(window.__rhStimulusShowConfirmDelete).toBe(otherHandler);

        window.__rhStimulusShowConfirmDelete = controller.globalShowHandler;
        controller.disconnect();
        expect(window.__rhStimulusShowConfirmDelete).toBeUndefined();
    });

    test("renderAssociated handles object label, name, and JSON fallback values", async () => {
        const controller = await getController();

        controller.renderAssociated([
            { label: "Label value" },
            { name: "Name value" },
            { foo: "bar" },
            "Plain value",
        ]);

        const values = Array.from(controller.associatedTarget.children).map(
            (node) => node.textContent,
        );
        expect(values).toEqual([
            "Label value",
            "Name value",
            '{"foo":"bar"}',
            "Plain value",
        ]);

        expect(() =>
            AdminConfirmDeleteController.prototype.renderAssociated.call(
                { hasAssociatedTarget: false },
                ["unused"],
            ),
        ).not.toThrow();
    });

    test("parseAssociated and normalizeIds cover JSON, numeric, and fallback cases", async () => {
        const controller = await getController();

        expect(controller.parseAssociated(null)).toEqual([]);
        expect(controller.parseAssociated(["a", "b"])).toEqual(["a", "b"]);
        expect(controller.parseAssociated('{"id":1}')).toEqual([{ id: 1 }]);
        expect(controller.parseAssociated("not-json")).toEqual(["not-json"]);

        expect(controller.normalizeIds([1, 2])).toEqual([1, 2]);
        expect(controller.normalizeIds("[3,4]")).toEqual([3, 4]);
        expect(controller.normalizeIds('{"id":9}')).toEqual([{ id: 9 }]);
        expect(controller.normalizeIds("null")).toEqual([]);
        expect(controller.normalizeIds(" +42 ")).toEqual([42]);
        expect(controller.normalizeIds("abc")).toEqual([]);
        expect(controller.normalizeIds(5)).toEqual([5]);
        expect(controller.normalizeIds(0)).toEqual([]);
    });

    test("resolveSourceForm and resolvePostAction handle hidden target and catch fallback", async () => {
        const controller = await getController();
        const source = document.getElementById("delete-form-seasons-bulk");

        controller.modalContext = { formId: "delete-form-seasons-bulk" };
        expect(controller.resolveSourceForm()).toBe(source);

        controller.modalContext = {};
        expect(controller.resolveSourceForm()).toBeNull();
        expect(controller.resolvePostAction(null)).toBe("#");

        source.setAttribute("action", "/admin/seasons/custom-action");
        controller.modalContext = {
            deleteUrl: "/admin/fallback-action",
            formId: "delete-form-seasons-bulk",
        };
        expect(controller.resolvePostAction(source)).toContain(
            "/admin/seasons/custom-action",
        );

        const throwingSource = {
            getAttribute() {
                throw new Error("attr failure");
            },
        };
        expect(controller.resolvePostAction(throwingSource)).toBe(
            "/admin/fallback-action",
        );

        const noHidden =
            AdminConfirmDeleteController.prototype.resolveSourceForm.call({
                modalContext: {},
                hasHiddenFormTarget: false,
            });
        expect(noHidden).toBeNull();
    });

    test("submitSourceForm removes injected fields and uses submit fallback", async () => {
        const controller = await getController();
        const source = document.getElementById("delete-form-seasons-bulk");
        const stale = document.createElement("input");
        stale.type = "hidden";
        stale.name = "stale";
        stale.className = "injected-delete";
        source.appendChild(stale);

        const submitSpy = jest.fn();
        source.requestSubmit = undefined;
        source.submit = submitSpy;

        controller.submitSourceForm(source, "/admin/seasons/new-action", [
            { name: "season_ids[]", value: "7" },
        ]);

        expect(source.action).toContain("/admin/seasons/new-action");
        expect(source.querySelector('input[name="stale"]')).toBeNull();
        expect(source.querySelector('input[name="season_ids[]"]').value).toBe(
            "7",
        );
        expect(submitSpy).toHaveBeenCalledTimes(1);
    });

    test("submitTempForm clones hidden values and uses submit fallback when requestSubmit is unavailable", async () => {
        const controller = await getController();
        const hiddenForm = document.getElementById(
            "confirm-delete-modal-hidden-form",
        );
        const blankHidden = document.createElement("input");
        blankHidden.type = "hidden";
        blankHidden.name = "blank-token";
        blankHidden.value = "";
        hiddenForm.appendChild(blankHidden);

        const originalRequestSubmit = HTMLFormElement.prototype.requestSubmit;
        const originalSubmit = HTMLFormElement.prototype.submit;
        const submitSpy = jest.fn();

        Object.defineProperty(HTMLFormElement.prototype, "requestSubmit", {
            configurable: true,
            writable: true,
            value: undefined,
        });
        Object.defineProperty(HTMLFormElement.prototype, "submit", {
            configurable: true,
            writable: true,
            value: submitSpy,
        });

        controller.submitTempForm("/admin/temp-action", [
            { name: "user_ids[]", value: "13" },
        ]);

        const forms = Array.from(document.querySelectorAll("form"));
        const tempForm = forms[forms.length - 1];

        expect(tempForm.action).toContain("/admin/temp-action");
        expect(tempForm.querySelector('input[name="_csrfToken"]').value).toBe(
            "test-token",
        );
        expect(tempForm.querySelector('input[name="blank-token"]').value).toBe(
            "",
        );
        expect(tempForm.querySelector('input[name="user_ids[]"]').value).toBe(
            "13",
        );
        expect(submitSpy).toHaveBeenCalledTimes(1);

        Object.defineProperty(HTMLFormElement.prototype, "requestSubmit", {
            configurable: true,
            writable: true,
            value: originalRequestSubmit,
        });
        Object.defineProperty(HTMLFormElement.prototype, "submit", {
            configurable: true,
            writable: true,
            value: originalSubmit,
        });
    });

    test("buildExtraFields handles missing ids and filtered id payloads", async () => {
        const controller = await getController();

        controller.modalContext = { bulkAction: "archive" };
        expect(controller.buildExtraFields()).toEqual([
            { name: "bulk_action", value: "archive" },
        ]);

        controller.modalContext = {};
        expect(controller.buildExtraFields()).toEqual([]);

        controller.modalContext = {
            ids: '["", " 5 ", null, "slug-2"]',
            idsName: "season_ids[]",
            bulkAction: "delete",
        };

        expect(controller.buildExtraFields()).toEqual([
            { name: "season_ids[]", value: "5" },
            { name: "season_ids[]", value: "slug-2" },
            { name: "bulk_action", value: "delete" },
        ]);
    });

    test("single person delete submits hidden modal form to delete endpoint", async () => {
        await Promise.resolve();

        const hiddenForm = document.getElementById(
            "confirm-delete-modal-hidden-form",
        );
        hiddenForm.setAttribute("action", "/admin/persons");
        const tokenField = document.createElement("input");
        tokenField.type = "hidden";
        tokenField.name = "_Token[fields]";
        tokenField.value = "some-hash";
        hiddenForm.appendChild(tokenField);

        window.__rhStimulusShowConfirmDelete({
            deleteUrl: "/admin/persons/delete/1",
        });

        document.getElementById("confirm-delete-modal-delete-btn").click();

        expect(hiddenForm.action).toContain("/admin/persons");

        const forms = Array.from(document.querySelectorAll("form"));
        const tempForm = forms[forms.length - 1];
        expect(tempForm.action).toContain("/admin/persons/delete/1");
        expect(
            tempForm.querySelector('input[name="_Token[fields]"]'),
        ).toBeNull();
        expect(tempForm.querySelector('input[name="_csrfToken"]').value).toBe(
            "test-token",
        );
        expect(requestSubmitMock).toHaveBeenCalled();
    });

    test("single team season delete submits hidden modal form to delete endpoint", async () => {
        await Promise.resolve();

        const hiddenForm = document.getElementById(
            "confirm-delete-modal-hidden-form",
        );
        hiddenForm.setAttribute("action", "/admin/team-seasons");

        window.__rhStimulusShowConfirmDelete({
            deleteUrl: "/admin/team-seasons/delete/1",
        });

        document.getElementById("confirm-delete-modal-delete-btn").click();

        expect(hiddenForm.action).toContain("/admin/team-seasons");

        const forms = Array.from(document.querySelectorAll("form"));
        const tempForm = forms[forms.length - 1];
        expect(tempForm.action).toContain("/admin/team-seasons/delete/1");
        expect(requestSubmitMock).toHaveBeenCalled();
    });
});
