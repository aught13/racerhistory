describe("admin.js additional branches", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        jest.resetModules();
    });

    test("click delete falls back to temp form when source.getAttribute throws", () => {
        // Create modal and delete button expected by the handler
        document.body.innerHTML = `
      <div id="confirm-delete-modal">
        <ul id="confirm-delete-modal-assoc"></ul>
        <button id="confirm-delete-modal-delete-btn">Delete</button>
        <form id="confirm-delete-modal-hidden-form"></form>
      </div>
    `;

        // Create a broken source form whose getAttribute throws
        const source = document.createElement("form");
        source.id = "broken-source";
        source.getAttribute = function () {
            throw new Error("attr error");
        };
        document.body.appendChild(source);

        // require module after DOM is set so IIFE runs and event listeners attach
        const admin = require("../../js/admin");
        const internals = admin.__internals || {};

        // set context to reference our broken source via formId
        internals.setContext({
            formId: "broken-source",
            deleteUrl: "/do-delete",
            ids: "[1]",
            idsName: "id",
        });

        // dispatch click on delete button
        const btn = document.getElementById("confirm-delete-modal-delete-btn");
        btn.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        // A temp form should have been appended to the body with action matching deleteUrl
        const forms = Array.from(document.getElementsByTagName("form"));
        const temp = forms.find(
            (f) => f.action && f.action.indexOf("/do-delete") !== -1,
        );
        expect(temp).toBeTruthy();
    });

    test("submitTempForm handles tokensSource.querySelectorAll throwing gracefully", () => {
        document.body.innerHTML = `
      <div id="confirm-delete-modal">
        <ul id="confirm-delete-modal-assoc"></ul>
      </div>
    `;

        // Create a tokensSource whose querySelectorAll returns an empty list
        const tokensSource = document.createElement("div");
        tokensSource.querySelectorAll = function () {
            return [];
        };
        document.body.appendChild(tokensSource);

        const admin = require("../../js/admin");
        const internals = admin.__internals || {};

        expect(() =>
            internals.submitTempForm("/x", tokensSource, [
                { name: "id", value: "1" },
            ]),
        ).not.toThrow();
    });
});
