import { jest } from "@jest/globals";

beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
});

test("renderAssociated handles invalid JSON string and falls back to raw string", async () => {
    const { __internals } = await import("../admin.js");
    const modal = document.createElement("div");
    modal.id = "confirm-delete-modal";
    const ul = document.createElement("ul");
    ul.id = "confirm-delete-modal-assoc";
    modal.appendChild(ul);
    document.body.appendChild(modal);

    const spy = jest.spyOn(console, "error").mockImplementation(() => {});
    __internals.renderAssociated(modal, "not json");
    expect(ul.querySelectorAll("li").length).toBe(1);
    expect(ul.querySelector("li").textContent).toBe("not json");
    expect(spy).toHaveBeenCalled();
    spy.mockRestore();
});

test("setContext stores context and calls renderAssociated", async () => {
    const { __internals } = await import("../admin.js");
    const modal = document.createElement("div");
    modal.id = "confirm-delete-modal";
    const ul = document.createElement("ul");
    ul.id = "confirm-delete-modal-assoc";
    modal.appendChild(ul);
    document.body.appendChild(modal);

    __internals.setContext({ associated: ["one", "two"] });
    expect(ul.querySelectorAll("li").length).toBe(2);
});

test("buildExtraFields handles JSON string, numeric string, array and bulkAction", async () => {
    const { __internals } = await import("../admin.js");
    const f1 = __internals.buildExtraFields({
        ids: '["a","b"]',
        idsName: "ids[]",
    });
    expect(f1.length).toBe(2);
    const f2 = __internals.buildExtraFields({
        ids: " 42 ",
        idsName: "ids[]",
    });
    expect(f2.length).toBe(1);
    expect(f2[0].value).toBe("42");
    const f3 = __internals.buildExtraFields({
        ids: ["x", "", null],
        idsName: "ids[]",
        bulkAction: "delete",
    });
    expect(f3.some((f) => f.name === "bulk_action")).toBe(true);
    expect(f3.filter((f) => f.name === "ids[]").length).toBe(1);
});

test("submitTempForm appends form with tokens and extra fields and logs submission", async () => {
    const { __internals } = await import("../admin.js");
    // prepare tokens source with hidden inputs
    const tokens = document.createElement("form");
    const hi = document.createElement("input");
    hi.type = "hidden";
    hi.name = "csrf";
    hi.value = "tok";
    tokens.appendChild(hi);
    document.body.appendChild(tokens);

    const spy = jest.spyOn(console, "debug").mockImplementation(() => {});
    __internals.submitTempForm("/do-delete", tokens, [
        { name: "x", value: "y" },
    ]);
    // a temp form should be appended
    const found = Array.from(document.body.querySelectorAll("form")).find(
        (f) => f.action && f.action.includes("/do-delete"),
    );
    expect(found).toBeTruthy();
    expect(found.querySelectorAll("input").length).toBeGreaterThanOrEqual(2);
    expect(spy).toHaveBeenCalled();
    spy.mockRestore();
});
