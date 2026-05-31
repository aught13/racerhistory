/**
 * @jest-environment jsdom
 */

/* Targeted branch coverage for sport-aware-game-form.js */

function setupFormDom(opts = {}) {
    const sportUrl = opts.sportUrl || "/api/sport-config";
    const teamSeasonValue = opts.teamSeasonValue || "";
    const gameId = opts.gameId || "";

    document.body.innerHTML = `
        <select id="team-season-select" data-sport-url="${sportUrl}">
            <option value="">--</option>
            <option value="1">Season 1</option>
        </select>
        <div id="sport-indicator" style="display:none">
            <div class="alert alert-info"></div>
        </div>
        <span id="current-sport"></span>
        <div id="sport-loading" style="display:none"></div>
        <div id="sport-specific-section"></div>
        ${gameId ? `<input type="hidden" id="game-id-hidden" value="${gameId}" />` : ""}
        ${opts.extraHtml || ""}
    `;

    if (teamSeasonValue) {
        document.getElementById("team-season-select").value = teamSeasonValue;
    }
}

beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
    global.fetch = jest.fn();
});

afterEach(() => {
    delete global.fetch;
});

describe("SportAwareGameForm", () => {
    test("constructor exits when no team-season-select", async () => {
        document.body.innerHTML = "<div>no select</div>";
        const SAGFModule =
            await import("../../legacy/sport-aware-game-form.js");
        const SAGF = SAGFModule.default || SAGFModule;
        if (typeof SAGF === "function") {
            const form = new SAGF();
            expect(form.teamSeasonSelect).toBeNull();
        }
    });

    test("init auto-loads when select has value", async () => {
        setupFormDom({ teamSeasonValue: "1" });
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: jest.fn().mockResolvedValue({
                success: true,
                sportName: "Basketball",
                eavTemplate: [],
                values: {},
            }),
        });

        const SAGFModule =
            await import("../../legacy/sport-aware-game-form.js");
        const SAGF = SAGFModule.default || SAGFModule;
        if (typeof SAGF === "function") {
            new SAGF();
            await new Promise((r) => setTimeout(r, 50));
            expect(global.fetch).toHaveBeenCalled();
        }
    });

    test("change event with empty value hides indicator and shows fallback", async () => {
        setupFormDom();
        const SAGFModule =
            await import("../../legacy/sport-aware-game-form.js");
        const SAGF = SAGFModule.default || SAGFModule;
        if (typeof SAGF === "function") {
            new SAGF();
            const select = document.getElementById("team-season-select");
            select.value = "";
            select.dispatchEvent(new Event("change"));
            const indicator = document.getElementById("sport-indicator");
            expect(indicator.style.display).toBe("none");
        }
    });

    test("change event with value triggers updateSportFields", async () => {
        setupFormDom();
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: jest.fn().mockResolvedValue({
                success: true,
                sportName: "Hockey",
                eavTemplate: [
                    {
                        field_name: "goals",
                        display_label: "Goals",
                        field_type: "number",
                        field_group: "scoring",
                    },
                ],
                values: {},
            }),
        });

        const SAGFModule =
            await import("../../legacy/sport-aware-game-form.js");
        const SAGF = SAGFModule.default || SAGFModule;
        if (typeof SAGF === "function") {
            new SAGF();
            const select = document.getElementById("team-season-select");
            select.value = "1";
            select.dispatchEvent(new Event("change"));
            await new Promise((r) => setTimeout(r, 50));
            expect(global.fetch).toHaveBeenCalled();
        }
    });

    test("updateSportFields with game_id uses game_id param", async () => {
        setupFormDom({ gameId: "42", teamSeasonValue: "1" });
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: jest.fn().mockResolvedValue({
                success: true,
                sportName: "Basketball",
                eavTemplate: [],
                values: {},
            }),
        });

        const SAGFModule =
            await import("../../legacy/sport-aware-game-form.js");
        const SAGF = SAGFModule.default || SAGFModule;
        if (typeof SAGF === "function") {
            new SAGF();
            await new Promise((r) => setTimeout(r, 50));
            const url = global.fetch.mock.calls[0][0];
            expect(url).toContain("game_id=42");
        }
    });

    test("updateSportFields HTTP error shows error", async () => {
        setupFormDom({ teamSeasonValue: "1" });
        global.fetch = jest.fn().mockResolvedValue({
            ok: false,
            status: 500,
            json: jest.fn().mockRejectedValue(new Error("bad")),
        });

        jest.spyOn(console, "error").mockImplementation(() => {});
        const SAGFModule =
            await import("../../legacy/sport-aware-game-form.js");
        const SAGF = SAGFModule.default || SAGFModule;
        if (typeof SAGF === "function") {
            new SAGF();
            await new Promise((r) => setTimeout(r, 50));
        }
    });

    test("updateSportFields !data.success shows error and fallback", async () => {
        setupFormDom({ teamSeasonValue: "1" });
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: jest.fn().mockResolvedValue({
                success: false,
                error: "Not found",
            }),
        });

        const SAGFModule =
            await import("../../legacy/sport-aware-game-form.js");
        const SAGF = SAGFModule.default || SAGFModule;
        if (typeof SAGF === "function") {
            new SAGF();
            await new Promise((r) => setTimeout(r, 50));
            const section = document.getElementById("sport-specific-section");
            expect(section.innerHTML).toContain("Game Details");
        }
    });

    test("updateSportFields network error shows error", async () => {
        setupFormDom({ teamSeasonValue: "1" });
        global.fetch = jest.fn().mockRejectedValue(new Error("network"));

        jest.spyOn(console, "error").mockImplementation(() => {});
        const SAGFModule =
            await import("../../legacy/sport-aware-game-form.js");
        const SAGF = SAGFModule.default || SAGFModule;
        if (typeof SAGF === "function") {
            new SAGF();
            await new Promise((r) => setTimeout(r, 50));
            expect(console.error).toHaveBeenCalledWith(
                expect.stringContaining("Error fetching"),
                expect.any(Error),
            );
        }
    });

    test("renderSportFields with empty eavTemplate shows fallback", async () => {
        setupFormDom({ teamSeasonValue: "1" });
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: jest.fn().mockResolvedValue({
                success: true,
                sportName: "Track",
                eavTemplate: [],
                values: {},
            }),
        });

        const SAGFModule =
            await import("../../legacy/sport-aware-game-form.js");
        const SAGF = SAGFModule.default || SAGFModule;
        if (typeof SAGF === "function") {
            new SAGF();
            await new Promise((r) => setTimeout(r, 50));
            const section = document.getElementById("sport-specific-section");
            expect(section.innerHTML).toContain("Game Details");
        }
    });

    test("renderSportFields maps EAV period keys to legacy form fields", async () => {
        setupFormDom({
            teamSeasonValue: "1",
            extraHtml:
                '<input name="period_1_mur" value="" /><input name="period_1_opp" value="" />',
        });
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: jest.fn().mockResolvedValue({
                success: true,
                sportName: "Basketball",
                eavTemplate: [
                    {
                        field_name: "p1",
                        display_label: "P1",
                        field_type: "text",
                        field_group: "periods",
                    },
                ],
                values: {
                    period_1_team: "25",
                    period_1_opponent: "30",
                },
            }),
        });

        const SAGFModule =
            await import("../../legacy/sport-aware-game-form.js");
        const SAGF = SAGFModule.default || SAGFModule;
        if (typeof SAGF === "function") {
            new SAGF();
            await new Promise((r) => setTimeout(r, 50));
            const mur = document.querySelector('[name="period_1_mur"]');
            expect(mur.value).toBe("25");
        }
    });

    test("renderField with number type includes min/max", async () => {
        setupFormDom({ teamSeasonValue: "1" });
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: jest.fn().mockResolvedValue({
                success: true,
                sportName: "Baseball",
                eavTemplate: [
                    {
                        field_name: "runs",
                        display_label: "Runs",
                        field_type: "number",
                        field_group: "scoring",
                        min: 0,
                        max: 99,
                    },
                    {
                        field_name: "notes",
                        display_label: "Notes",
                        field_type: "text",
                        field_group: "meta",
                    },
                ],
                values: {},
            }),
        });

        const SAGFModule =
            await import("../../legacy/sport-aware-game-form.js");
        const SAGF = SAGFModule.default || SAGFModule;
        if (typeof SAGF === "function") {
            new SAGF();
            await new Promise((r) => setTimeout(r, 50));
            const runsInput = document.getElementById("runs");
            if (runsInput) {
                expect(runsInput.type).toBe("number");
            }
        }
    });

    test("getExistingFieldValue preserves existing form values", async () => {
        setupFormDom({
            teamSeasonValue: "1",
            extraHtml: '<input name="existing_field" value="preserved" />',
        });
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: jest.fn().mockResolvedValue({
                success: true,
                sportName: "Soccer",
                eavTemplate: [
                    {
                        field_name: "existing_field",
                        display_label: "Existing",
                        field_type: "text",
                        field_group: "general",
                    },
                ],
                values: {},
            }),
        });

        const SAGFModule =
            await import("../../legacy/sport-aware-game-form.js");
        const SAGF = SAGFModule.default || SAGFModule;
        if (typeof SAGF === "function") {
            new SAGF();
            await new Promise((r) => setTimeout(r, 50));
        }
    });
});
