import { jest } from "@jest/globals";

describe("games_sport_dynamic branches", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        global.fetch = undefined;
    });

    test("buildFieldControl creates number input with min/max", async () => {
        // ensure the module initializes (it early-returns if select missing)
        document.body.innerHTML =
            '<select id="team-season-select"></select><div id="sport-specific-section"></div><div id="sport-indicator"><span id="current-sport"></span></div><span id="sport-loading"></span>';
        const mod = require("../games_sport_dynamic.js");
        const build = mod.buildFieldControl;
        const field = {
            field_name: "pts",
            display_label: "Points",
            field_type: "number",
            min: 0,
            max: 99,
        };
        const node = build(field, {});
        const input = node.querySelector("input");
        expect(input).not.toBeNull();
        expect(input.type).toBe("number");
        expect(input.min).toBe("0");
        expect(input.max).toBe("99");
        const label = node.querySelector("label");
        expect(label.textContent).toBe("Points");
    });

    test("groupFields groups by field_group and defaults to general", async () => {
        document.body.innerHTML =
            '<select id="team-season-select"></select><div id="sport-specific-section"></div><div id="sport-indicator"><span id="current-sport"></span></div><span id="sport-loading"></span>';
        const mod = require("../games_sport_dynamic.js");
        const group = mod.groupFields;
        const fields = [
            { field_name: "a", field_group: "offense" },
            { field_name: "b" },
            { field_name: "c", field_group: "offense" },
        ];
        const groups = group(fields);
        expect(Object.keys(groups)).toContain("offense");
        expect(Object.keys(groups)).toContain("general");
        expect(groups.offense.length).toBe(2);
        expect(groups.general.length).toBe(1);
    });

    test("renderEav renders grouped cards and inputs", async () => {
        // ensure module initialises and section exists
        document.body.innerHTML =
            '<select id="team-season-select"></select><div id="sport-specific-section"></div><div id="sport-indicator"><span id="current-sport"></span></div><span id="sport-loading"></span>';
        const mod = require("../games_sport_dynamic.js");
        const template = [
            { field_name: "x", display_label: "X", field_group: "grp" },
            { field_name: "y", display_label: "Y", field_group: "grp" },
            { field_name: "z", display_label: "Z" },
        ];
        mod.renderEav(template, { x: "1", y: "2" });
        const section = document.getElementById("sport-specific-section");
        expect(section.querySelectorAll(".card").length).toBeGreaterThan(0);
        expect(section.querySelector('[name="x"]').value).toBe("1");
        expect(section.querySelector('[name="y"]').value).toBe("2");
    });

    test("fetchMeta uses HTML fragment when available", async () => {
        // prepare DOM before requiring module to allow select binding
        document.body.innerHTML = `
      <select id="team-season-select" data-sport-url="/sport"></select>
      <div id="sport-specific-section"></div>
      <div id="sport-indicator"><span id="current-sport"></span></div>
      <span id="sport-loading" style="display:none"></span>
    `;
        const modPath = "../games_sport_dynamic.js";
        const mod = require(modPath);

        // mock fetch: first call (HTML) returns fragment with data-sport-name
        global.fetch = jest.fn().mockResolvedValueOnce({
            ok: true,
            text: async () => '<div data-sport-name="Footy"><p>frag</p></div>',
        });

        await mod.fetchMeta("12");
        const section = document.getElementById("sport-specific-section");
        expect(section.innerHTML).toContain("frag");
        const sport = document.getElementById("current-sport");
        expect(sport.textContent).toBe("Footy");
    });

    test("fetchMeta falls back to JSON when HTML fails", async () => {
        document.body.innerHTML = `
      <select id="team-season-select" data-sport-url="/sport"></select>
      <div id="sport-specific-section"></div>
      <div id="sport-indicator"><span id="current-sport"></span></div>
      <span id="sport-loading" style="display:none"></span>
    `;
        const mod = require("../games_sport_dynamic.js");

        // first fetch rejects, second returns JSON
        global.fetch = jest
            .fn()
            .mockRejectedValueOnce(new Error("no html"))
            .mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    sportName: "Hoops",
                    eavTemplate: [],
                    values: {},
                }),
            });

        await mod.fetchMeta("34");
        expect(document.getElementById("current-sport").textContent).toBe(
            "Hoops",
        );
    });
});
