/** @jest-environment jsdom */

describe("admin parsing and fallback behaviors", () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div id="confirm-delete-modal">
                <ul id="confirm-delete-modal-assoc"></ul>
                <form id="confirm-delete-modal-hidden-form"></form>
            </div>
        `;
    });

    test("renderAssociated accepts JSON string and arrays", () => {
        const { __internals } = require("../../js/admin.js");
        const modal = document.getElementById("confirm-delete-modal");
        __internals.renderAssociated(modal, '["A","B"]');
        expect(modal.querySelectorAll("li").length).toBe(2);

        modal.querySelector("#confirm-delete-modal-assoc").innerHTML = "";
        __internals.renderAssociated(modal, ["C"]);
        expect(modal.querySelectorAll("li").length).toBe(1);
    });

    test("renderAssociated handles non-JSON string fallback", () => {
        const { __internals } = require("../../js/admin.js");
        const modal = document.getElementById("confirm-delete-modal");
        // non-json string should render as single item
        __internals.renderAssociated(modal, "not-json");
        expect(modal.querySelectorAll("li").length).toBe(1);
    });

    test("submitTempForm attaches extra hidden fields and submits (via requestSubmit)", () => {
        const { __internals } = require("../../js/admin.js");
        // create tokens source
        const tokens = document.createElement("form");
        const h = document.createElement("input");
        h.type = "hidden";
        h.name = "csrf";
        h.value = "tok";
        tokens.appendChild(h);
        document.body.appendChild(tokens);

        // stub requestSubmit to record call
        HTMLFormElement.prototype.requestSubmit = function () {
            this.__submitted = true;
        };

        __internals.submitTempForm("/x", tokens, [
            { name: "ids[]", value: "5" },
        ]);
        const temp = document.querySelector('form[action="/x"]');
        expect(temp).toBeTruthy();
        expect(temp.querySelector('input[name="csrf"]').value).toBe("tok");
        expect(temp.querySelector('input[name="ids[]"]').value).toBe("5");
        expect(temp.__submitted).toBe(true);
    });
});
