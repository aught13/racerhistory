/* Tests for webroot/js/games_sport_dynamic.js (CommonJS)
 */
beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
    // Ensure a select exists so the module IIFE doesn't return early
    const select = document.createElement("select");
    select.id = "team-season-select";
    select.setAttribute("data-sport-url", "/fake/meta");
    document.body.appendChild(select);
    // section and other elements
    const section = document.createElement("div");
    section.id = "sport-specific-section";
    document.body.appendChild(section);
    const indicator = document.createElement("span");
    indicator.id = "sport-indicator";
    document.body.appendChild(indicator);
    const sportName = document.createElement("span");
    sportName.id = "current-sport";
    document.body.appendChild(sportName);
    const loading = document.createElement("span");
    loading.id = "sport-loading";
    document.body.appendChild(loading);
});

test("buildFieldControl produces number input with min/max and default", () => {
    const mod = require("../games_sport_dynamic.js");
    const field = {
        field_name: "shots",
        display_label: "Shots",
        field_type: "number",
        min: 0,
        max: 10,
        default_value: 3,
    };
    const wrapper = mod.buildFieldControl(field, {});
    const input = wrapper.querySelector("input");
    expect(input).toBeTruthy();
    expect(input.type).toBe("number");
    expect(input.min).toBe("0");
    expect(input.max).toBe("10");
    expect(input.value).toBe("3");
});

test("groupFields groups by field_group", () => {
    const mod = require("../games_sport_dynamic.js");
    const fields = [
        { field_name: "a", field_group: "offense" },
        { field_name: "b" },
        { field_name: "c", field_group: "offense" },
    ];
    const groups = mod.groupFields(fields);
    expect(Object.keys(groups)).toContain("offense");
    expect(Object.keys(groups)).toContain("general");
    expect(groups.offense.length).toBe(2);
});

test("fetchMeta HTML fragment path updates section and sport name", async () => {
    // mock fetch: first call returns html ok
    global.fetch = jest.fn().mockResolvedValueOnce({
        ok: true,
        text: () => Promise.resolve('<div data-sport-name="Soccer">HTML</div>'),
    });
    const mod = require("../games_sport_dynamic.js");
    await mod.fetchMeta("42");
    const section = document.getElementById("sport-specific-section");
    expect(section.innerHTML).toContain("HTML");
    const sportName = document.getElementById("current-sport");
    expect(sportName.textContent).toBe("Soccer");
});

test("fetchMeta falls back to JSON and renders EAV; loading hidden after", async () => {
    // first call: htmlResp.ok = false
    // second call: json success
    global.fetch = jest.fn().mockImplementation((url, opts) => {
        if (opts && opts.headers && opts.headers.Accept === "text/html") {
            return Promise.resolve({
                ok: false,
                text: () => Promise.resolve(""),
            });
        }
        return Promise.resolve({
            json: () =>
                Promise.resolve({
                    success: true,
                    sportName: "Baseball",
                    eavTemplate: [
                        { field_name: "x" },
                        { field_name: "y" },
                        { field_name: "z" },
                        { field_name: "w" },
                        { field_name: "u" },
                    ],
                    values: { x: "1" },
                }),
        });
    });

    const mod = require("../games_sport_dynamic.js");
    await mod.fetchMeta("99");
    const sportName = document.getElementById("current-sport");
    expect(sportName.textContent).toBe("Baseball");
    const section = document.getElementById("sport-specific-section");
    // should have heading and at least one card
    expect(section.querySelector("h5").textContent).toBe(
        "Sport-Specific Details",
    );
    expect(section.querySelector(".card")).toBeTruthy();
    const loading = document.getElementById("sport-loading");
    expect(loading.style.display).toBe("none");
});
