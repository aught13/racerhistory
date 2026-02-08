const path = require("path");

describe("SportAwareGameForm branch coverage", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
        global.fetch = undefined;
    });

    test("maps EAV values to legacy inputs", async () => {
        const SportAwareGameForm = require(
            path.resolve(__dirname, "../sport-aware-game-form.js"),
        );

        // DOM required by the class
        document.body.innerHTML = `
      <select id="team-season-select" data-sport-url="/sport"></select>
      <div id="sport-indicator"><span id="current-sport"></span><div class="alert"></div></div>
      <span id="sport-loading" style="display:none"></span>
      <div id="sport-specific-section"></div>
      <input name="period_1_mur" />
      <input name="period_1_opp" />
    `;

        const team = document.getElementById("team-season-select");
        team.value = "42";

        // mock fetch to return sport data with values
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({
                success: true,
                sportName: "Basketball",
                eavTemplate: {
                    f1: {
                        field_group: "general",
                        field_name: "f1",
                        display_label: "F1",
                    },
                },
                values: { period_1_team: "10", period_1_opponent: "8" },
            }),
        });

        const inst = new SportAwareGameForm();
        await inst.updateSportFields("42");

        const mur = document.getElementsByName("period_1_mur")[0];
        const opp = document.getElementsByName("period_1_opp")[0];
        expect(mur.value).toBe("10");
        expect(opp.value).toBe("8");
    });

    test("empty eavTemplate falls back to legacy fields", async () => {
        const SportAwareGameForm = require(
            path.resolve(__dirname, "../sport-aware-game-form.js"),
        );
        document.body.innerHTML = `
      <select id="team-season-select" data-sport-url="/sport"></select>
      <div id="sport-indicator"><span id="current-sport"></span><div class="alert"></div></div>
      <span id="sport-loading" style="display:none"></span>
      <div id="sport-specific-section"></div>
    `;
        document.getElementById("team-season-select").value = "7";

        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({
                success: true,
                sportName: "Soccer",
                eavTemplate: [],
            }),
        });

        const inst = new SportAwareGameForm();
        await inst.updateSportFields("7");

        // fallback should render an input named 'periods'
        expect(document.querySelector('[name="periods"]')).not.toBeNull();
    });

    test("non-ok response shows error and falls back", async () => {
        const SportAwareGameForm = require(
            path.resolve(__dirname, "../sport-aware-game-form.js"),
        );
        document.body.innerHTML = `
      <select id="team-season-select" data-sport-url="/sport"></select>
      <div id="sport-indicator"><span id="current-sport"></span><div class="alert"></div></div>
      <span id="sport-loading" style="display:none"></span>
      <div id="sport-specific-section"></div>
    `;
        document.getElementById("team-season-select").value = "9";

        global.fetch = jest.fn().mockResolvedValue({ ok: false, status: 500 });

        const inst = new SportAwareGameForm();
        await inst.updateSportFields("9");

        // showError sets current-sport text and fallback renders periods
        expect(document.getElementById("current-sport").textContent).toMatch(
            /Failed to load/,
        );
        expect(document.querySelector('[name="periods"]')).not.toBeNull();
    });
});
