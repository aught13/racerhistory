// ...existing code...
jest.disableAutomock();

beforeEach(() => {
  jest.resetModules();
  document.body.innerHTML = '';
});

test('buildFieldControl creates number input with min/max', () => {
  document.body.innerHTML = '<select id="team-season-select"></select><div id="sport-specific-section"></div>';
  const mod = require('../games_sport_dynamic.js');
  const field = { field_name: 'goals', field_type: 'number', min: 0, max: 10, display_label: 'Goals' };
  const el = mod.buildFieldControl(field, {});
  const input = el.querySelector('input');
  expect(input.type).toBe('number');
  expect(input.min).toBe('0');
  expect(input.max).toBe('10');
});

test('groupFields groups by field_group', () => {
  document.body.innerHTML = '<select id="team-season-select"></select><div id="sport-specific-section"></div>';
  const mod = require('../games_sport_dynamic.js');
  const fields = [ { field_name: 'a', field_group: 'x' }, { field_name: 'b', field_group: 'y' }, { field_name: 'c', field_group: 'x' } ];
  const groups = mod.groupFields(fields);
  expect(Object.keys(groups).sort()).toEqual(['x','y']);
  expect(groups.x.length).toBe(2);
});

test('renderEav chunks fields into rows of up to 4', () => {
  document.body.innerHTML = '<select id="team-season-select"></select><div id="sport-specific-section"></div>';
  const mod = require('../games_sport_dynamic.js');
  const template = [];
  for (let i = 0; i < 9; i++) template.push({ field_name: 'f' + i });
  mod.renderEav(template, {});
  const section = document.getElementById('sport-specific-section');
  // should create groups -> one 'general' group with card body rows
  const rows = section.querySelectorAll('.card .card-body .row');
  // 9 fields with chunk size 4 => 3 rows
  expect(rows.length).toBe(3);
});

test('fetchMeta uses HTML path when available and updates sport name', async () => {
  document.body.innerHTML = `
    <select id="team-season-select" data-sport-url="/meta"></select>
    <div id="sport-specific-section"></div>
    <span id="current-sport"></span>
    <span id="sport-indicator"></span>
    <span id="sport-loading"></span>
  `;
  const html = '<div data-sport-name="Soccer">fragment</div>';
  global.fetch = jest.fn().mockResolvedValueOnce({ ok: true, text: async () => html });
  const mod = require('../games_sport_dynamic.js');
  await mod.fetchMeta('123');
  const section = document.getElementById('sport-specific-section');
  expect(section.innerHTML).toContain('fragment');
  const sn = document.getElementById('current-sport');
  expect(sn.textContent).toBe('Soccer');
});

test('fetchMeta falls back to JSON and renders eav', async () => {
  document.body.innerHTML = `
    <select id="team-season-select" data-sport-url="/meta"></select>
    <div id="sport-specific-section"></div>
    <span id="current-sport"></span>
    <span id="sport-indicator"></span>
    <span id="sport-loading"></span>
  `;
  // first fetch (HTML) throws
  global.fetch = jest.fn()
    .mockRejectedValueOnce(new Error('html fail'))
    .mockResolvedValueOnce({ json: async () => ({ success: true, sportName: 'X', eavTemplate: [{ field_name: 'a' }], values: {} }) });
  const mod = require('../games_sport_dynamic.js');
  await mod.fetchMeta('321');
  const section = document.getElementById('sport-specific-section');
  expect(section.textContent).toContain('Sport-Specific Details');
  const sn = document.getElementById('current-sport');
  expect(sn.textContent).toBe('X');
});
/** @jest-environment jsdom */
// helpers are required inside tests after DOM setup

describe("games_sport_dynamic helpers", () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <select id="team-season-select" data-sport-url="/fake"></select>
            <div id="sport-specific-section"></div>
            <div id="sport-indicator"></div>
            <span id="current-sport"></span>
            <span id="sport-loading"></span>
        `;
        jest.resetModules();
    });

    test("groupFields groups by field_group and defaults to general", () => {
        const { groupFields } = require("../../js/games_sport_dynamic.js");
        const fields = [
            { field_name: "a", field_group: "one" },
            { field_name: "b" },
            { field_name: "c", field_group: "one" },
            { field_name: "d", field_group: "two" },
        ];
        const grouped = groupFields(fields);
        expect(Object.keys(grouped).sort()).toEqual(
            ["one", "two", "general"].sort(),
        );
        expect(grouped.one.length).toBe(2);
        expect(grouped.general.length).toBe(1);
    });

    test("buildFieldControl creates number input with min/max and text fallback", () => {
        const {
            buildFieldControl,
        } = require("../../js/games_sport_dynamic.js");
        const numField = {
            field_name: "score",
            display_label: "Score",
            field_type: "number",
            min: 0,
            max: 10,
        };
        const wrapper = buildFieldControl(numField, {});
        const input = wrapper.querySelector("input");
        expect(input).toBeTruthy();
        expect(input.type).toBe("number");
        expect(input.min).toBe("0");
        expect(input.max).toBe("10");

        const txtField = {
            field_name: "coach",
            display_label: "Coach",
            field_type: "text",
        };
        const wrapper2 = buildFieldControl(txtField, { coach: "Alice" });
        const input2 = wrapper2.querySelector("input");
        expect(input2.type).toBe("text");
        expect(input2.value).toBe("Alice");
    });

    test("renderEav appends grouped cards to section", () => {
        const { renderEav } = require("../../js/games_sport_dynamic.js");
        const template = [
            {
                field_name: "f1",
                display_label: "F1",
                field_group: "g1",
                field_type: "text",
            },
            {
                field_name: "f2",
                display_label: "F2",
                field_group: "g2",
                field_type: "number",
                min: 1,
                max: 5,
            },
        ];
        renderEav(template, {});
        const section = document.getElementById("sport-specific-section");
        expect(section.querySelectorAll(".card").length).toBe(2);
        expect(section.innerHTML).toContain("G1");
        expect(section.innerHTML).toContain("G2");
        // ensure inputs present
        expect(section.querySelector('input[name="f1"]')).toBeTruthy();
        expect(section.querySelector('input[name="f2"]')).toBeTruthy();
    });

    test("buildFieldControl uses default_value when existingValues missing", () => {
        const {
            buildFieldControl,
        } = require("../../js/games_sport_dynamic.js");
        const f = {
            field_name: "x",
            display_label: "X",
            field_type: "text",
            default_value: "DEFAULT",
        };
        const w = buildFieldControl(f, {});
        const input = w.querySelector("input");
        expect(input.value).toBe("DEFAULT");
    });

    test("buildFieldControl number input without min/max has empty attrs", () => {
        const {
            buildFieldControl,
        } = require("../../js/games_sport_dynamic.js");
        const nf = {
            field_name: "n",
            display_label: "N",
            field_type: "number",
        };
        const w = buildFieldControl(nf, {});
        const input = w.querySelector("input");
        expect(input.type).toBe("number");
        // when not set, min/max should be empty strings
        expect(input.min).toBe("");
        expect(input.max).toBe("");
    });

    test("renderEav with empty template leaves only heading and no cards", () => {
        const { renderEav } = require("../../js/games_sport_dynamic.js");
        const section = document.getElementById("sport-specific-section");
        renderEav([], {});
        expect(section.querySelectorAll(".card").length).toBe(0);
        expect(section.textContent).toContain("Sport-Specific Details");
    });

    test("fetchMeta sets sport name and renders eavTemplate on success", async () => {
        const mod = require("../../js/games_sport_dynamic.js");

        global.fetch = jest.fn(() =>
            Promise.resolve({
                ok: true,
                json: () =>
                    Promise.resolve({
                        success: true,
                        sportName: "S",
                        eavTemplate: [{ field_name: "t1", field_group: "g" }],
                        values: {},
                    }),
            }),
        );

        await mod.fetchMeta("42");
        expect(document.getElementById("current-sport").textContent).toBe("S");
        // renderEav should have added a card for group 'g'
        const section = document.getElementById("sport-specific-section");
        expect(section.querySelectorAll(".card").length).toBeGreaterThan(0);
        expect(section.textContent).toContain("G");
    });

    test("fetchMeta does not render when data.success is false", async () => {
        const mod = require("../../js/games_sport_dynamic.js");
        global.fetch = jest.fn(() =>
            Promise.resolve({
                ok: true,
                json: () => Promise.resolve({ success: false }),
            }),
        );
        await mod.fetchMeta("42");
        const section = document.getElementById("sport-specific-section");
        expect(section.querySelectorAll(".card").length).toBe(0);
    });

    test("fetchMeta handles fetch rejection gracefully", async () => {
        const mod = require("../../js/games_sport_dynamic.js");
        global.fetch = jest.fn(() => Promise.reject(new Error("boom")));
        await expect(mod.fetchMeta("42")).resolves.toBeUndefined();
        const section = document.getElementById("sport-specific-section");
        expect(section.querySelectorAll(".card").length).toBe(0);
    });
});
