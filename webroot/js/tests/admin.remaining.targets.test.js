let origSubmit, origRequestSubmit;
/** @jest-environment jsdom */

describe("admin.js remaining branch targets", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        if (typeof window !== "undefined") {
            delete window.showConfirmDelete;
            delete window.AdminToast;
        }
        global.bootstrap = undefined;
        // Save and patch HTMLFormElement.prototype
        if (typeof HTMLFormElement !== "undefined") {
            origSubmit = Object.getOwnPropertyDescriptor(
                HTMLFormElement.prototype,
                "submit",
            );
            origRequestSubmit = Object.getOwnPropertyDescriptor(
                HTMLFormElement.prototype,
                "requestSubmit",
            );
            Object.defineProperty(HTMLFormElement.prototype, "submit", {
                value: function () {},
                configurable: true,
                writable: true,
            });
            Object.defineProperty(HTMLFormElement.prototype, "requestSubmit", {
                value: function () {},
                configurable: true,
                writable: true,
            });
        }
    });

    afterEach(() => {
        // Clean up DOM and globals
        document.body.innerHTML = "";
        if (typeof window !== "undefined") {
            delete window.showConfirmDelete;
            delete window.AdminToast;
        }
        global.bootstrap = undefined;
        // Restore HTMLFormElement methods
        if (typeof HTMLFormElement !== "undefined") {
            if (origSubmit) {
                Object.defineProperty(
                    HTMLFormElement.prototype,
                    "submit",
                    origSubmit,
                );
            } else {
                delete HTMLFormElement.prototype.submit;
            }
            if (origRequestSubmit) {
                Object.defineProperty(
                    HTMLFormElement.prototype,
                    "requestSubmit",
                    origRequestSubmit,
                );
            } else {
                delete HTMLFormElement.prototype.requestSubmit;
            }
        }
    });

    test("delegated trigger click sets context from data attributes and renders associated", () => {
        document.body.innerHTML = `
          <div id="confirm-delete-modal"><ul id="confirm-delete-modal-assoc"></ul></div>
          <button id="t1" data-bs-target="#confirm-delete-modal" data-delete-url="/del" data-associated='["a","b"]' data-ids='[1,2]' data-ids-name='ids[]' data-form-id='f1' data-bulk-action='doit'></button>
        `;
        const btn = document.getElementById("t1");
        const mod = document.getElementById("confirm-delete-modal");
        require("../admin.js");
        // clicking the trigger should call setContext and render associated list
        btn.click();
        const assoc = mod.querySelectorAll("#confirm-delete-modal-assoc li");
        expect(assoc.length).toBeGreaterThanOrEqual(2);
    });

    test("source submit uses submit() path when requestSubmit missing", () => {
        document.body.innerHTML = `
          <form id="srcNoReq" action="/act"></form>
          <div id="confirm-delete-modal"><ul id="confirm-delete-modal-assoc"></ul><button id="confirm-delete-modal-delete-btn" type="button">Delete</button></div>
        `;
        const src = document.getElementById("srcNoReq");
        let submitted = false;
        src.submit = function () {
            submitted = true;
        };
        // ensure no requestSubmit defined
        src.requestSubmit = undefined;
        const { showConfirmDelete } = require("../admin.js");
        showConfirmDelete({
            ids: "[11]",
            idsName: "ids[]",
            formId: "srcNoReq",
            deleteUrl: "/x",
        });
        document.getElementById("confirm-delete-modal-delete-btn").click();
        expect(submitted).toBe(true);
        expect(src.action).toContain("/act");
    });

    test("parseInt fallback catch triggered when parseInt throws", () => {
        document.body.innerHTML = `
          <div id="confirm-delete-modal"><ul id="confirm-delete-modal-assoc"></ul><form id="confirm-delete-modal-hidden-form"></form><button id="confirm-delete-modal-delete-btn" type="button">Delete</button></div>
        `;
        const origParse = global.parseInt;
        try {
            global.parseInt = function () {
                throw new Error("boom parse");
            };
            const { showConfirmDelete } = require("../admin.js");
            // Use a string that JSON.parse will throw on (e.g., '+42'), but regex matches
            showConfirmDelete({
                deleteUrl: "/pz",
                ids: "+42",
                idsName: "ids[]",
            });
            // click should not throw even though parseInt throws
            expect(() =>
                document
                    .getElementById("confirm-delete-modal-delete-btn")
                    .click(),
            ).not.toThrow();
            const temp = Array.from(document.querySelectorAll("form")).find(
                (f) => f.action && f.action.includes("/pz"),
            );
            expect(temp).toBeTruthy();
        } finally {
            global.parseInt = origParse;
        }
    });

    test("normalization error path caught when Array.filter throws", () => {
        document.body.innerHTML = `
          <div id="confirm-delete-modal"><ul id="confirm-delete-modal-assoc"></ul><form id="confirm-delete-modal-hidden-form"></form><button id="confirm-delete-modal-delete-btn" type="button">Delete</button></div>
        `;
        const origFilter = Array.prototype.filter;
        try {
            Array.prototype.filter = function () {
                throw new Error("filter boom");
            };
            const { showConfirmDelete } = require("../admin.js");
            showConfirmDelete({
                deleteUrl: "/nz",
                ids: "[1,2]",
                idsName: "ids[]",
            });
            expect(() =>
                document
                    .getElementById("confirm-delete-modal-delete-btn")
                    .click(),
            ).not.toThrow();
        } finally {
            Array.prototype.filter = origFilter;
        }
    });
});
