const admin = require("../admin");

describe("admin.js branch/error handling", () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div id="confirm-delete-modal">
                <ul id="confirm-delete-modal-assoc"></ul>
                <form id="confirm-delete-modal-hidden-form"></form>
            </div>
            <button id="confirm-delete-modal-delete-btn">Delete</button>
        `;
        // ensure event listeners in admin.js are attached by requiring the module
        jest.clearAllMocks();
    });

    afterEach(() => {
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("renderAssociated falls back on invalid JSON string and logs error", () => {
        const modal = document.getElementById("confirm-delete-modal");
        const spy = jest.spyOn(console, "error").mockImplementation(() => {});

        admin.__internals.renderAssociated(modal, "not json");

        const listItems = modal.querySelectorAll(
            "#confirm-delete-modal-assoc li",
        );
        expect(listItems.length).toBe(1);
        expect(listItems[0].textContent).toBe("not json");
        expect(spy).toHaveBeenCalled();
    });

    test("delete handler catches parseInt error when parseInt throws", () => {
        const spyErr = jest
            .spyOn(console, "error")
            .mockImplementation(() => {});
        const submitSpy = jest
            .spyOn(admin.__internals, "submitTempForm")
            .mockImplementation(() => {});

        // set context with numeric-like string so branch attempts parseInt
        admin.__internals.setContext({
            deleteUrl: "/d",
            ids: "+42",
            idsName: "ids[]",
        });

        // cause parseInt to throw
        const realParse = global.parseInt;
        global.parseInt = () => {
            throw new Error("boom parse");
        };

        // click the delete button to trigger handler
        document.getElementById("confirm-delete-modal-delete-btn").click();

        expect(spyErr).toHaveBeenCalled();
        // should have logged parseInt error
        expect(
            spyErr.mock.calls.some((c) =>
                String(c[0]).includes("parseInt error"),
            ),
        ).toBe(true);

        // restore parseInt
        global.parseInt = realParse;
        submitSpy.mockRestore();
    });

    test("delete handler handles normalization/filter errors gracefully", () => {
        const spyErr = jest
            .spyOn(console, "error")
            .mockImplementation(() => {});
        const submitSpy = jest
            .spyOn(admin.__internals, "submitTempForm")
            .mockImplementation(() => {});

        // create an array whose filter method throws
        const badArr = [1, 2, 3];
        badArr.filter = function () {
            throw new Error("filter boom");
        };

        admin.__internals.setContext({
            deleteUrl: "/pz",
            ids: badArr,
            idsName: "ids[]",
        });

        document.getElementById("confirm-delete-modal-delete-btn").click();

        expect(spyErr).toHaveBeenCalled();
        expect(
            spyErr.mock.calls.some((c) =>
                String(c[0]).includes("Error normalizing ids"),
            ),
        ).toBe(true);

        submitSpy.mockRestore();
    });
});
