let gsd;

describe("games_sport_dynamic helper branches", () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <select id="team-season-select" data-sport-url="/meta"></select>
            <div id="sport-specific-section"></div>
            <span id="sport-indicator"><span id="current-sport"></span><span id="sport-loading"></span></span>
            <input type="hidden" id="game-id-hidden" value="">
        `;
        // Ensure module is evaluated after DOM is ready so it doesn't early-return
        jest.resetModules();
        gsd = require("../games_sport_dynamic");
    });

    afterEach(() => {
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("buildFieldControl produces number input with min/max and text input otherwise", () => {
        const num = {
            field_name: "n1",
            display_label: "N",
            field_type: "number",
            min: 0,
            max: 5,
        };
        const txt = { field_name: "t1", display_label: "T" };
        const numEl = gsd.buildFieldControl(num, {});
        expect(numEl.querySelector("input").type).toBe("number");
        expect(numEl.querySelector("input").min).toBe("0");
        expect(numEl.querySelector("input").max).toBe("5");

        const txtEl = gsd.buildFieldControl(txt, {});
        expect(txtEl.querySelector("input").type).toBe("text");
    });

    test("groupFields groups by field_group and defaults to general", () => {
        const fields = [
            { field_name: "a", field_group: "one" },
            { field_name: "b" },
            { field_name: "c", field_group: "one" },
        ];
        const grouped = gsd.groupFields(fields);
        expect(Object.keys(grouped)).toContain("one");
        expect(Object.keys(grouped)).toContain("general");
        expect(grouped.one.length).toBe(2);
    });

    test("renderEav renders cards for groups and creates inputs for fields", () => {
        const template = [
            { field_name: "f1", field_group: "grp", display_label: "F1" },
            { field_name: "f2", field_group: "grp", display_label: "F2" },
        ];
        gsd.renderEav(template, { f1: "x" });

        const section = document.getElementById("sport-specific-section");
        expect(section.querySelectorAll(".card").length).toBe(1);
        // two inputs created for f1 and f2
        const inputs = section.querySelectorAll('input[id^="field-"]');
        expect(inputs.length).toBe(2);
        // labels present
        const labels = section.querySelectorAll("label.form-label");
        expect(labels.length).toBeGreaterThanOrEqual(2);
    });

    test("renderEav with empty template clears section", () => {
        const section = document.getElementById("sport-specific-section");
        section.innerHTML = '<div class="card"></div>';
        gsd.renderEav([], {});
        expect(section.querySelectorAll(".card").length).toBe(0);
        expect(section.textContent).toMatch(/Sport-Specific Details/);
    });

    test("fetchMeta uses fetch and updates sport name and section", async () => {
        // mock fetch to return a json payload
        global.fetch = jest.fn().mockResolvedValue({
            json: async () => ({
                success: true,
                sportName: "Footy",
                eavTemplate: [{ field_name: "x" }],
                values: {},
            }),
        });

        await gsd.fetchMeta("42");

        const sportNameSpan = document.getElementById("current-sport");
        const section = document.getElementById("sport-specific-section");
        expect(sportNameSpan.textContent).toBe("Footy");
        expect(section.querySelectorAll(".card").length).toBe(1);
    });
});
