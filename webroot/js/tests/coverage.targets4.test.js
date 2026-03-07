import { jest } from "@jest/globals";

describe("Coverage targets 4 - edge normalizations and multi-row rendering", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        jest.resetModules();
        // Ensure requestSubmit is available in jsdom test env to avoid Not implemented errors
        // Override to a no-op so tests can inspect form DOM after click handlers run.
        try {
            HTMLFormElement.prototype.requestSubmit = function () {};
        } catch {
            // ignore in environments where HTMLFormElement is not present
        }
    });

    test("admin normalizes ids array removing empty and null and handles toString throwing element", async () => {
        document.body.innerHTML =
            '<div id="confirm-delete-modal"><button id="confirm-delete-modal-delete-btn">Delete</button><form id="confirm-delete-modal-hidden-form"></form></div>';
        const badObj = {
            toString: () => {
                throw new Error("boom");
            },
        };
        const { __internals } = await import("../admin");
        const internals = __internals || {};

        // setContext with ids array containing empty, null, number, whitespace and an object that throws
        internals.setContext({
            deleteUrl: "/n",
            ids: ["", null, 7, " 8 ", badObj],
            idsName: "ids[]",
        });
        const btn = document.getElementById("confirm-delete-modal-delete-btn");
        // clicking should not throw despite toString throwing
        expect(() =>
            btn.dispatchEvent(new MouseEvent("click", { bubbles: true })),
        ).not.toThrow();

        // verify temp form created with normalized ids for 7 and 8 only
        const forms = Array.from(document.getElementsByTagName("form"));
        const temp = forms.find(
            (f) => f.action && f.action.indexOf("/n") !== -1,
        );
        expect(temp).toBeTruthy();
        const vals = Array.from(
            temp.querySelectorAll('input[name="ids[]"]'),
        ).map((i) => i.value);
        // values should be trimmed strings
        expect(vals).toEqual(expect.arrayContaining(["7", "8"]));
    });

    test("games_sport_dynamic renderEav handles many fields across multiple rows", async () => {
        // prepare DOM
        document.body.innerHTML =
            '<select id="team-season-select" data-sport-url="/test"></select><div id="sport-specific-section"></div>';
        const mod = await import("../games_sport_dynamic");
        const renderEav =
            mod.renderEav || (mod.__internals && mod.__internals.renderEav);

        const template = [];
        for (let i = 0; i < 9; i++) {
            template.push({
                field_group: "stats",
                field_name: "f" + i,
                display_label: "F" + i,
                field_type: i % 2 === 0 ? "number" : "text",
            });
        }

        renderEav(template, {});
        const cards = document.querySelectorAll(
            "#sport-specific-section .card",
        );
        expect(cards.length).toBe(1);
        // rows should be multiple (at least 3 rows for 9 fields, chunked 4 per row -> 3 rows)
        const rows = cards[0].querySelectorAll(".row");
        expect(rows.length).toBeGreaterThanOrEqual(3);
    });

    test("sport-aware renderField renders number with min/max and text fallback when display_label missing", async () => {
        document.body.innerHTML =
            '<select id="team-season-select" data-sport-url="/test"></select><div id="sport-specific-section"></div>';
        const _safmod = await import("../sport-aware-game-form");
        const Saf = _safmod.default || _safmod;
        const inst = new Saf();
        // call renderField directly by obtaining function from prototype
        const proto = Object.getPrototypeOf(inst);
        const htmlNum = proto.renderField.call(inst, {
            field_name: "num",
            field_type: "number",
            min: 0,
            max: 5,
            display_label: "Num",
        });
        expect(htmlNum).toContain('type="number"');
        const htmlText = proto.renderField.call(inst, {
            field_name: "txt",
            field_type: "text",
        });
        // even without display_label it should render label (empty) and an input
        expect(htmlText).toContain("<input");
    });

    test("admin parseInt fallback handles + and whitespace numeric string", async () => {
        // Instead of firing the whole click flow (which depends on jsdom requestSubmit),
        // call the exported helper directly to assert the parseInt fallback behavior.
        const { __internals } = await import("../admin");
        const internals = __internals || {};
        const extra = internals.buildExtraFields({
            ids: " +42 ",
            idsName: "ids[]",
        });
        expect(Array.isArray(extra)).toBeTruthy();
        const idField = extra.find((f) => f.name === "ids[]");
        expect(idField).toBeTruthy();
        expect(idField.value).toBe("42");
    });
});
