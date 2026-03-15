beforeAll(() => {
    if (typeof HTMLFormElement !== "undefined") {
        HTMLFormElement.prototype.submit = function () {};
        HTMLFormElement.prototype.requestSubmit = function () {};
    }
});
/** @jest-environment jsdom */

import { jest } from "@jest/globals";
describe("admin.js internals coverage", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        if (typeof window !== "undefined") {
            delete window.showConfirmDelete;
            delete window.AdminToast;
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
        // Restore HTMLFormElement methods if patched
        if (typeof HTMLFormElement !== "undefined") {
            HTMLFormElement.prototype.submit = function () {};
            HTMLFormElement.prototype.requestSubmit = function () {};
        }
    });

    test("renderAssociated with object array shows fallback JSON for unknown shape", async () => {
        document.body.innerHTML =
            '<div id="confirm-delete-modal"><ul id="confirm-delete-modal-assoc"></ul></div>';
        const { __internals } = await import("../admin.js");
        const modal = __internals.findModal();
        const odd = JSON.stringify([{ neither: "X" }]);
        // call renderAssociated directly to hit fallback JSON.stringify branch
        __internals.renderAssociated(modal, odd);
        const list = document.getElementById("confirm-delete-modal-assoc");
        expect(list.children.length).toBe(1);
        expect(list.children[0].textContent).toContain("neither");
    });

    test("renderAssociated returns early when modal missing", async () => {
        const { __internals } = await import("../admin.js");
        expect(() => __internals.renderAssociated(null, ["one"])).not.toThrow();
    });

    test("submitTempForm attaches provided extra fields and tokens", async () => {
        document.body.innerHTML = `
            <div>
              <form id="source"><input type="hidden" name="_csrf" value="tok"></form>
            </div>
        `;
        const { __internals } = await import("../admin.js");
        // Create extra fields
        const extra = [{ name: "foo", value: "bar" }];
        // call submitTempForm which will append a hidden form to body
        __internals.submitTempForm(
            "/x",
            document.getElementById("source"),
            extra,
        );
        const temp = Array.from(document.querySelectorAll("form")).find((f) =>
            f.action.includes("/x"),
        );
        expect(temp).toBeTruthy();
        const names = Array.from(
            temp.querySelectorAll('input[type="hidden"]'),
        ).map((i) => i.name);
        expect(names).toContain("_csrf");
        expect(names).toContain("foo");
    });
});
