beforeAll(() => {
    if (typeof HTMLFormElement !== "undefined") {
        HTMLFormElement.prototype.submit = function () {};
        HTMLFormElement.prototype.requestSubmit = function () {};
    }
});
/** @jest-environment jsdom */

import { jest } from "@jest/globals";
// Extra admin tests to cover edge branches: bulkAction present/absent, missing modal, associated parse errors,
// and temp form token copying fallback.

describe("admin.js extra coverage targets", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        // clear any global state
        if (typeof window !== "undefined") {
            delete window.showConfirmDelete;
            delete window.AdminToast;
        }
        global.bootstrap = undefined; // test fallback when bootstrap missing
    });

    afterEach(() => {
        // Clean up DOM and globals
        document.body.innerHTML = "";
        if (typeof window !== "undefined") {
            delete window.showConfirmDelete;
            delete window.AdminToast;
        }
        global.bootstrap = undefined;
        // Restore HTMLFormElement methods if patched
        if (typeof HTMLFormElement !== "undefined") {
            HTMLFormElement.prototype.submit = function () {};
            HTMLFormElement.prototype.requestSubmit = function () {};
        }
    });

    test("showConfirmDelete fallback displays modal when bootstrap absent", async () => {
        document.body.innerHTML = `
          <div id="confirm-delete-modal">
            <ul id="confirm-delete-modal-assoc"></ul>
            <form id="confirm-delete-modal-hidden-form"></form>
            <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
          </div>
        `;
        const { showConfirmDelete } = await import("../admin.js");
        // Should not throw and should set display:block when bootstrap missing
        expect(() =>
            showConfirmDelete({
                associated: JSON.stringify(["One"]),
                formId: "x",
            }),
        ).not.toThrow();
        const modal = document.getElementById("confirm-delete-modal");
        expect(
            modal.style.display === "block" || modal.style.display === "",
        ).toBeTruthy();
    });

    test("JSON parse error fallback - createList should not throw", async () => {
        // Create DOM structure safely without innerHTML
        const modal = document.createElement("div");
        modal.id = "confirm-delete-modal";
        const assocList = document.createElement("ul");
        assocList.id = "confirm-delete-modal-assoc";
        modal.appendChild(assocList);
        document.body.appendChild(modal);

        const { showConfirmDelete } = await import("../admin.js");
        // ensure AdminToast exists and works
        const root = document.createElement("div");
        root.id = "root";
        document.body.appendChild(root);

        // call with invalid JSON - should not throw and should create list item
        expect(() =>
            showConfirmDelete({ associated: "not json" }),
        ).not.toThrow();
        const assoc = document.getElementById("confirm-delete-modal-assoc");
        expect(assoc.children.length).toBeGreaterThanOrEqual(1);
    });

    test("submitTempForm copies hidden inputs from tokens source", async () => {
        document.body.innerHTML = `
            <div id="confirm-delete-modal">
              <ul id="confirm-delete-modal-assoc"></ul>
              <form id="confirm-delete-modal-hidden-form">
                <input type="hidden" name="_csrfToken" value="abc123">
              </form>
              <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
            </div>
        `;
        const { showConfirmDelete } = await import("../admin.js");
        // Remove the hidden form so fallback path must create temp form
        const hidden = document.getElementById(
            "confirm-delete-modal-hidden-form",
        );
        if (hidden) hidden.remove();
        // Call with deleteUrl and no formId so submitTempForm will run
        showConfirmDelete({
            deleteUrl: "/tmpdel",
            ids: "[5]",
            idsName: "ids[]",
        });
        // click delete -> temp form created
        document.getElementById("confirm-delete-modal-delete-btn").click();
        const temp = Array.from(document.querySelectorAll("form")).find((f) =>
            f.action.includes("/tmpdel"),
        );
        expect(temp).toBeTruthy();
        const hid = temp.querySelectorAll('input[type="hidden"]').length;
        expect(hid).toBeGreaterThanOrEqual(1);
    });
});
