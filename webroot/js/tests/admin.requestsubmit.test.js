const path = require("path");

describe("admin.js requestSubmit handling", () => {
    beforeEach(() => {
        jest.resetModules();
        // clear DOM
        document.body.innerHTML = "";
    });

    test("submitTempForm uses requestSubmit when available", () => {
        // ensure admin attaches after DOM setup
        const admin = require(path.resolve(__dirname, "../admin.js"));
        // monkeypatch prototype so jsdom won't throw
        const orig = HTMLFormElement.prototype.requestSubmit;
        HTMLFormElement.prototype.requestSubmit = jest.fn();

        // create a tokens source with a hidden input to copy
        const tokens = document.createElement("div");
        const hid = document.createElement("input");
        hid.type = "hidden";
        hid.name = "csrf";
        hid.value = "tok";
        tokens.appendChild(hid);
        document.body.appendChild(tokens);

        // call internals.submitTempForm
        admin.__internals.submitTempForm("/do-it", tokens, [
            { name: "x", value: "y" },
        ]);

        // a temp form should have been appended
        const forms = document.body.querySelectorAll("form");
        expect(forms.length).toBeGreaterThan(0);
        const temp = forms[forms.length - 1];
        expect(temp.action).toContain("/do-it");
        // requestSubmit should have been called (via prototype)
        expect(HTMLFormElement.prototype.requestSubmit).toHaveBeenCalled();

        // restore
        HTMLFormElement.prototype.requestSubmit = orig;
    });

    test("submitTempForm falls back to submit when requestSubmit missing", () => {
        const admin = require(path.resolve(__dirname, "../admin.js"));
        const origReq = HTMLFormElement.prototype.requestSubmit;
        const origSubmit = HTMLFormElement.prototype.submit;
        // remove requestSubmit and spy on submit
        try {
            delete HTMLFormElement.prototype.requestSubmit;
        } catch {
            HTMLFormElement.prototype.requestSubmit = undefined;
        }
        HTMLFormElement.prototype.submit = jest.fn();

        admin.__internals.submitTempForm("/fallback", null, []);

        const forms = document.body.querySelectorAll("form");
        expect(forms.length).toBeGreaterThan(0);
        // submit fallback should have been invoked
        expect(HTMLFormElement.prototype.submit).toHaveBeenCalled();

        // restore
        HTMLFormElement.prototype.submit = origSubmit;
        HTMLFormElement.prototype.requestSubmit = origReq;
    });

    test("delete button uses source.form requestSubmit when present", () => {
        // prepare DOM: source form and delete button
        document.body.innerHTML = `
      <form id="sourceForm" action="/fromsource"></form>
      <button id="confirm-delete-modal-delete-btn">Delete</button>
    `;

        // require admin after DOM is present so event handlers attach
        const admin = require(path.resolve(__dirname, "../admin.js"));

        const source = document.getElementById("sourceForm");
        // make requestSubmit available on the source form
        source.requestSubmit = jest.fn();

        // set context so code will use sourceForm
        admin.__internals.setContext({
            formId: "sourceForm",
            deleteUrl: "/will-not-use",
            ids: "7",
            idsName: "id",
        });

        // dispatch click on the delete button
        const btn = document.getElementById("confirm-delete-modal-delete-btn");
        btn.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        // source.requestSubmit should have been called
        expect(source.requestSubmit).toHaveBeenCalled();

        // injected inputs should exist
        const injected = source.querySelectorAll(".injected-delete");
        expect(injected.length).toBeGreaterThan(0);
    });
});
