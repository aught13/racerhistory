const path = require("path");

describe("SportAwareGameForm branches", () => {
    let origFetch;

    beforeEach(() => {
        jest.resetModules();
        origFetch = global.fetch;
        document.body.innerHTML = "";
        // minimal DOM elements required by the class
        document.body.innerHTML = `
      <select id="team-season-select" data-sport-url="/sport"></select>
      <div id="sport-indicator"><div class="alert"></div></div>
      <span id="current-sport"></span>
      <span id="sport-loading"></span>
      <div id="sport-specific-section"></div>
    `;
    });

    afterEach(() => {
        global.fetch = origFetch;
    });

    test("handles non-ok fetch response by showing fallback", async () => {
        global.fetch = jest.fn().mockResolvedValue({ ok: false, status: 500 });
        const SportAware = require(
            path.resolve(__dirname, "../sport-aware-game-form.js"),
        );
        const instance = new SportAware();
        // ensure select has a value so updateSportFields will run
        const sel = document.getElementById("team-season-select");
        sel.value = "42";

        await instance.updateSportFields("42");

        // fallback shows 'Game Details' markup
        expect(
            document.getElementById("sport-specific-section").innerHTML,
        ).toContain("Game Details");
        // current sport span should show an error message
        expect(document.getElementById("current-sport").textContent).toMatch(
            /Failed to load|Failed to load sport-specific/,
        );
    });

    test("success with empty eavTemplate shows fallback", async () => {
        const json = { success: true, sportName: "Soccer", eavTemplate: [] };
        global.fetch = jest
            .fn()
            .mockResolvedValue({ ok: true, json: async () => json });
        const SportAware = require(
            path.resolve(__dirname, "../sport-aware-game-form.js"),
        );
        const instance = new SportAware();
        document.getElementById("team-season-select").value = "1";

        await instance.updateSportFields("1");

        // sport name should be displayed
        expect(document.getElementById("current-sport").textContent).toBe(
            "Soccer",
        );
        // fallback content present
        expect(
            document.getElementById("sport-specific-section").innerHTML,
        ).toContain("Game Details");
    });

    test("renderSportFields creates number inputs and preserves existing values", () => {
        const SportAware = require(
            path.resolve(__dirname, "../sport-aware-game-form.js"),
        );
        const instance = new SportAware();

        // create an existing field to preserve its value
        const existing = document.createElement("input");
        existing.name = "period_1_mur";
        existing.value = "99";
        document.body.appendChild(existing);

        const data = {
            sportName: "Hockey",
            eavTemplate: [
                {
                    field_group: "periods",
                    field_name: "period_1_mur",
                    field_type: "number",
                    display_label: "P1 Team",
                    default_value: "5",
                    min: 0,
                    max: 20,
                },
                {
                    field_group: "periods",
                    field_name: "notes",
                    field_type: "text",
                    display_label: "Notes",
                    default_value: "x",
                },
            ],
        };

        instance.renderSportFields(data);

        const html = document.getElementById(
            "sport-specific-section",
        ).innerHTML;
        // number input present
        expect(html).toMatch(/type="number"/);
        // existing field value preserved (should not be default)
        const created = document.getElementById("period_1_mur");
        expect(created).toBeTruthy();
        expect(created.value).toBe("99");
    });

    test("updateSportFields maps eav values to legacy inputs", async () => {
        const mapped = {
            success: true,
            sportName: "MappedSport",
            eavTemplate: [],
            values: {
                period_1_team: "11",
                period_2_team: "22",
            },
        };
        global.fetch = jest
            .fn()
            .mockResolvedValue({ ok: true, json: async () => mapped });
        // create legacy inputs that should be filled
        const p1 = document.createElement("input");
        p1.name = "period_1_mur";
        document.body.appendChild(p1);
        const p2 = document.createElement("input");
        p2.name = "period_2_mur";
        document.body.appendChild(p2);

        const SportAware = require(
            path.resolve(__dirname, "../sport-aware-game-form.js"),
        );
        const instance = new SportAware();
        document.getElementById("team-season-select").value = "9";

        await instance.updateSportFields("9");

        expect(document.getElementsByName("period_1_mur")[0].value).toBe("11");
        expect(document.getElementsByName("period_2_mur")[0].value).toBe("22");
    });
});
