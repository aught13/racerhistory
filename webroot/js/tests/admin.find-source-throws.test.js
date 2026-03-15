import { jest } from "@jest/globals";

describe("js source-getElementById throws handling", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
    });

    test("falls back to temp form when getElementById throws", async () => {
        const adminPath = "../admin.js";

        // minimal modal and delete button
        document.body.innerHTML = `
      <div id="confirm-delete-modal">
        <div id="confirm-delete-modal-assoc"></div>
        <form id="confirm-delete-modal-hidden-form"></form>
      </div>
      <button id="confirm-delete-modal-delete-btn">Delete</button>
    `;

        // make requestSubmit available so jsdom doesn't throw
        const origReq = HTMLFormElement.prototype.requestSubmit;
        HTMLFormElement.prototype.requestSubmit = jest.fn();

        const { __internals } = await import(adminPath);

        // set context referring to a source form id that will cause getElementById to throw
        __internals.setContext({
            formId: "sourceForm",
            deleteUrl: "/x",
            ids: "1",
            idsName: "id",
        });

        // override document.getElementById to throw when the requested id is 'sourceForm'
        const origGet = document.getElementById;
        document.getElementById = function (id) {
            if (id === "sourceForm") throw new Error("boom getElementById");
            return origGet.call(document, id);
        };

        // click delete button to trigger delegated handler
        const btn = document.getElementById("confirm-delete-modal-delete-btn");
        btn.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        // fallback path should call requestSubmit on the temporary form (via prototype)
        expect(HTMLFormElement.prototype.requestSubmit).toHaveBeenCalled();

        // restore
        document.getElementById = origGet;
        HTMLFormElement.prototype.requestSubmit = origReq;
    });
});
