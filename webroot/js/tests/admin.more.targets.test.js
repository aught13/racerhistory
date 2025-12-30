beforeAll(() => {
    if (typeof HTMLFormElement !== "undefined") {
        HTMLFormElement.prototype.submit = function () {};
        HTMLFormElement.prototype.requestSubmit = function () {};
    }
});
/** @jest-environment jsdom */

describe("admin.js additional targeted branches", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        if (typeof window !== "undefined") {
            delete window.showConfirmDelete;
            delete window.AdminToast;
        }
        global.bootstrap = undefined;
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

    test("JSON numeric string ids parsed as number and added", () => {
        document.body.innerHTML = `
          <div id="confirm-delete-modal">
            <ul id="confirm-delete-modal-assoc"></ul>
            <form id="confirm-delete-modal-hidden-form"></form>
            <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
          </div>
        `;
        const { showConfirmDelete } = require("../admin.js");
        // JSON.parse('7') -> 7, should result in ids[] value '7'
        showConfirmDelete({ deleteUrl: "/num", ids: "7", idsName: "ids[]" });
        document.getElementById("confirm-delete-modal-delete-btn").click();
        const temp = Array.from(document.querySelectorAll("form")).find((f) =>
            f.action.includes("/num"),
        );
        expect(temp).toBeTruthy();
        const inputs = Array.from(
            temp.querySelectorAll('input[type="hidden"][name="ids[]"]'),
        );
        expect(inputs.length).toBeGreaterThanOrEqual(1);
        expect(inputs[0].value).toBe("7");
    });

    test("source form without action uses context.deleteUrl and is submitted", () => {
        document.body.innerHTML = `
          <div>
            <form id="srcNoAction"></form>
            <div id="confirm-delete-modal">
              <ul id="confirm-delete-modal-assoc"></ul>
              <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
            </div>
          </div>
        `;
        const { showConfirmDelete } = require("../admin.js");
        showConfirmDelete({
            ids: "[1]",
            idsName: "ids[]",
            formId: "srcNoAction",
            deleteUrl: "/useme",
        });
        const src = document.getElementById("srcNoAction");
        let submitted = false;
        src.submit = function () {
            submitted = true;
        };
        src.requestSubmit = function () {
            submitted = true;
        };
        document.getElementById("confirm-delete-modal-delete-btn").click();
        expect(submitted).toBe(true);
        expect(src.action).toContain("/useme");
    });

    test("existing injected-delete inputs are removed before new ones are added", () => {
        document.body.innerHTML = `
          <div>
            <form id="srcWithInjected" action="/a">
              <input class="injected-delete" type="hidden" name="x" value="old">
            </form>
            <div id="confirm-delete-modal">
              <ul id="confirm-delete-modal-assoc"></ul>
              <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
            </div>
          </div>
        `;
        const { showConfirmDelete } = require("../admin.js");
        showConfirmDelete({
            ids: "[9]",
            idsName: "ids[]",
            formId: "srcWithInjected",
            deleteUrl: "/a",
        });
        const src = document.getElementById("srcWithInjected");
        // ensure there was one injected-delete initially
        expect(src.querySelectorAll(".injected-delete").length).toBe(1);
        let submitted = false;
        src.submit = function () {
            submitted = true;
        };
        src.requestSubmit = function () {
            submitted = true;
        };
        document.getElementById("confirm-delete-modal-delete-btn").click();
        expect(submitted).toBe(true);
        // After submit, the old injected should be removed and new ones added
        const injected = Array.from(
            src.querySelectorAll(".injected-delete"),
        ).map((i) => i.name);
        expect(injected).toContain("ids[]");
        expect(injected).not.toContain("x");
    });

    test("non-numeric, non-json ids fallback results in no ids injected", () => {
        document.body.innerHTML = `
          <div id="confirm-delete-modal">
            <ul id="confirm-delete-modal-assoc"></ul>
            <form id="confirm-delete-modal-hidden-form"></form>
            <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
          </div>
        `;
        const { showConfirmDelete } = require("../admin.js");
        // If this test ever overrides any global/prototype, use try/finally for restoration (none here).
        showConfirmDelete({
            deleteUrl: "/none",
            ids: "not-a-number",
            idsName: "ids[]",
        });
        document.getElementById("confirm-delete-modal-delete-btn").click();
        // The code may inject into an existing hidden form; check both possibilities.
        let temp = Array.from(document.querySelectorAll("form")).find(
            (f) => f.action && f.action.includes("/none"),
        );
        if (!temp)
            temp = document.getElementById("confirm-delete-modal-hidden-form");
        expect(temp).toBeTruthy();
        // Should exist but not have ids[] fields
        const ids = temp.querySelectorAll('input[name="ids[]"]');
        expect(ids.length).toBe(0);
    });
});
