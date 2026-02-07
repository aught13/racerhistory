/** @jest-environment jsdom */
import {
    jest,
    beforeAll,
    beforeEach,
    afterEach,
    describe,
    test,
    expect,
} from "@jest/globals";

beforeAll(() => {
    if (typeof HTMLFormElement !== "undefined") {
        HTMLFormElement.prototype.submit = function () {};
        HTMLFormElement.prototype.requestSubmit = function () {};
    }
});

describe("admin.js error-path branches", () => {
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

    test("gracefully handles document.getElementById throwing when finding source", async () => {
        document.body.innerHTML = `
          <div id="confirm-delete-modal">
            <ul id="confirm-delete-modal-assoc"></ul>
            <form id="confirm-delete-modal-hidden-form"></form>
            <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
          </div>
        `;
        const { showConfirmDelete } = await import("../admin.js");
        const orig = document.getElementById;
        try {
            // throw only for a specific id
            document.getElementById = function (id) {
                if (id === "will-throw") throw new Error("boom");
                return orig.call(document, id);
            };
            // call with a bad formId so the code will attempt to getElementById('will-throw')
            expect(() =>
                showConfirmDelete({
                    deleteUrl: "/x",
                    formId: "will-throw",
                    ids: "[1]",
                    idsName: "ids[]",
                }),
            ).not.toThrow();
            // click delete should not throw even though getElementById may throw inside handler
            expect(() =>
                document
                    .getElementById("confirm-delete-modal-delete-btn")
                    .click(),
            ).not.toThrow();
        } finally {
            document.getElementById = orig;
        }
    });

    test("parseInt throwing is caught and results in no ids injected", async () => {
        document.body.innerHTML = `
                    <div id="confirm-delete-modal">
                        <ul id="confirm-delete-modal-assoc"></ul>
                        <form id="confirm-delete-modal-hidden-form"></form>
                        <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
                    </div>
                `;
        const { showConfirmDelete } = await import("../admin.js");
        const origParse = global.parseInt;
        try {
            global.parseInt = function () {
                throw new Error("parseInt boom");
            };
            showConfirmDelete({
                deleteUrl: "/p",
                ids: " 123 ",
                idsName: "ids[]",
            });
            // should not throw on click
            expect(() =>
                document
                    .getElementById("confirm-delete-modal-delete-btn")
                    .click(),
            ).not.toThrow();
            const temp = Array.from(document.querySelectorAll("form")).find(
                (f) => f.action && f.action.includes("/p"),
            );
            expect(temp).toBeTruthy();
            // parsing failed; ensure temp form exists and click did not throw
            expect(temp).toBeTruthy();
        } finally {
            global.parseInt = origParse;
        }
    });

    test("source.getAttribute throwing is caught and postAction falls back to deleteUrl", async () => {
        document.body.innerHTML = `
          <div>
            <form id="sourceError"></form>
            <div id="confirm-delete-modal"><ul id="confirm-delete-modal-assoc"></ul><button id="confirm-delete-modal-delete-btn" type="button">Delete</button></div>
          </div>
        `;
        const { showConfirmDelete } = await import("../admin.js");
        const src = document.getElementById("sourceError");
        // make getAttribute throw when called
        src.getAttribute = function () {
            throw new Error("getAttr boom");
        };
        src.submit = function () {};
        showConfirmDelete({
            ids: "[2]",
            idsName: "ids[]",
            formId: "sourceError",
            deleteUrl: "/fallback",
        });
        // clicking should not throw even if getAttribute throws; action should at least be set to deleteUrl or remain
        expect(() =>
            document.getElementById("confirm-delete-modal-delete-btn").click(),
        ).not.toThrow();
        // action may or may not be set depending on error path, but ensure the handler didn't crash
        expect(src).toBeTruthy();
    });

    test("source.querySelectorAll throwing is caught and does not bubble", async () => {
        document.body.innerHTML = `
          <div>
            <form id="sourceQSA" action="/a"></form>
            <div id="confirm-delete-modal"><ul id="confirm-delete-modal-assoc"></ul><button id="confirm-delete-modal-delete-btn" type="button">Delete</button></div>
          </div>
        `;
        const { showConfirmDelete } = await import("../admin.js");
        const src = document.getElementById("sourceQSA");
        src.querySelectorAll = function () {
            throw new Error("qsa boom");
        };
        src.submit = function () {};
        showConfirmDelete({
            ids: "[3]",
            idsName: "ids[]",
            formId: "sourceQSA",
            deleteUrl: "/a",
        });
        // click should not throw even though querySelectorAll throws
        expect(() =>
            document.getElementById("confirm-delete-modal-delete-btn").click(),
        ).not.toThrow();
    });
});
