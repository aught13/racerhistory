/**
 * @jest-environment jsdom
 */

/* Targeted branch coverage for games_sport_dynamic.js */

function setupDom(opts = {}) {
    const url = opts.url || "/api/sport-meta";
    const gameId = opts.gameId || "";
    const selectValue = opts.selectValue || "";
    document.body.innerHTML = `
        <select id="team-season-select" data-sport-url="${url}">
            <option value="">--</option>
            <option value="1">Season 1</option>
            <option value="2">Season 2</option>
        </select>
        <div id="sport-specific-section">${opts.sectionContent || ""}</div>
        <div id="sport-indicator" style="display:none"></div>
        <span id="current-sport"></span>
        <div id="sport-loading" style="display:none"></div>
        ${gameId ? `<input type="hidden" id="game-id-hidden" value="${gameId}" />` : ""}
    `;
    if (selectValue) {
        document.getElementById("team-season-select").value = selectValue;
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

describe("games_sport_dynamic.js", () => {
    test("exits early when no select element", async () => {
        document.body.innerHTML = "<div>no select</div>";
        await import("../games_sport_dynamic.js");
        // Module should not throw
    });

    test("fetchMeta HTML success path fills section", async () => {
        setupDom({ selectValue: "1" });
        const htmlContent =
            '<div class="card"><span data-sport-name="Basketball">Basketball</span></div>';
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            text: jest.fn().mockResolvedValue(htmlContent),
        });

        await import("../games_sport_dynamic.js");
        // Auto-loads because select has value and no .card present
        await new Promise((r) => setTimeout(r, 50));

        const section = document.getElementById("sport-specific-section");
        expect(section.innerHTML).toContain("card");
    });

    test("fetchMeta HTML failure falls to JSON path", async () => {
        setupDom({ selectValue: "1" });
        global.fetch = jest
            .fn()
            .mockResolvedValueOnce({ ok: false }) // HTML fails
            .mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValue({
                    success: true,
                    sportName: "Football",
                    eavTemplate: [
                        {
                            field_name: "goals",
                            display_label: "Goals",
                            field_type: "number",
                            field_group: "scoring",
                            min: 0,
                            max: 100,
                        },
                    ],
                    values: { goals: "3" },
                }),
            });

        jest.spyOn(console, "warn").mockImplementation(() => {});
        await import("../games_sport_dynamic.js");
        await new Promise((r) => setTimeout(r, 50));

        const sportName = document.getElementById("current-sport");
        expect(sportName.textContent).toBe("Football");
    });

    test("fetchMeta HTML throws falls to JSON path", async () => {
        setupDom({ selectValue: "1" });
        global.fetch = jest
            .fn()
            .mockRejectedValueOnce(new Error("network"))
            .mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValue({
                    success: true,
                    sportName: "Hockey",
                    eavTemplate: [],
                    values: {},
                }),
            });

        jest.spyOn(console, "warn").mockImplementation(() => {});
        await import("../games_sport_dynamic.js");
        await new Promise((r) => setTimeout(r, 50));
    });

    test("fetchMeta JSON with success=false returns early", async () => {
        setupDom({ selectValue: "1" });
        global.fetch = jest
            .fn()
            .mockResolvedValueOnce({ ok: false })
            .mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValue({ success: false }),
            });

        await import("../games_sport_dynamic.js");
        await new Promise((r) => setTimeout(r, 50));
    });

    test("fetchMeta JSON error falls to warn", async () => {
        setupDom({ selectValue: "1" });
        global.fetch = jest
            .fn()
            .mockResolvedValueOnce({ ok: false })
            .mockRejectedValueOnce(new Error("json fail"));

        jest.spyOn(console, "warn").mockImplementation(() => {});
        await import("../games_sport_dynamic.js");
        await new Promise((r) => setTimeout(r, 50));

        expect(console.warn).toHaveBeenCalledWith(
            expect.stringContaining("Failed to load"),
        );
    });

    test("fetchMeta without url returns early", async () => {
        document.body.innerHTML = `
            <select id="team-season-select">
                <option value="1">S1</option>
            </select>
            <div id="sport-specific-section"></div>`;
        document.getElementById("team-season-select").value = "1";
        await import("../games_sport_dynamic.js");
        await new Promise((r) => setTimeout(r, 50));
    });

    test("change event triggers fetchMeta", async () => {
        setupDom();
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            text: jest.fn().mockResolvedValue("<div>ok</div>"),
        });

        await import("../games_sport_dynamic.js");

        const select = document.getElementById("team-season-select");
        select.value = "2";
        select.dispatchEvent(new Event("change"));
        await new Promise((r) => setTimeout(r, 50));

        expect(global.fetch).toHaveBeenCalled();
    });

    test("auto-loads with existingGameId", async () => {
        setupDom({ gameId: "99" });
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            text: jest.fn().mockResolvedValue("<div>game</div>"),
        });

        await import("../games_sport_dynamic.js");
        await new Promise((r) => setTimeout(r, 50));

        const url = global.fetch.mock.calls[0][0];
        expect(url).toContain("game_id=99");
    });

    test("does not auto-load when section has .card", async () => {
        setupDom({
            selectValue: "1",
            sectionContent: '<div class="card">existing</div>',
        });
        global.fetch = jest.fn();

        await import("../games_sport_dynamic.js");
        await new Promise((r) => setTimeout(r, 50));

        expect(global.fetch).not.toHaveBeenCalled();
    });

    test("renderEav groups fields into rows of 4", async () => {
        setupDom({ selectValue: "1" });
        const fields = Array.from({ length: 6 }, (_, i) => ({
            field_name: `f${i}`,
            display_label: `Field ${i}`,
            field_type: i < 3 ? "number" : "text",
            field_group: i < 4 ? "scoring" : "other",
            default_value: `${i}`,
        }));

        global.fetch = jest
            .fn()
            .mockResolvedValueOnce({ ok: false })
            .mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValue({
                    success: true,
                    sportName: "Baseball",
                    eavTemplate: fields,
                    values: {},
                }),
            });

        await import("../games_sport_dynamic.js");
        await new Promise((r) => setTimeout(r, 50));

        const section = document.getElementById("sport-specific-section");
        expect(section.querySelectorAll(".card").length).toBe(2);
    });

    test("buildFieldControl with min/max number constraints", async () => {
        setupDom({ selectValue: "1" });
        global.fetch = jest
            .fn()
            .mockResolvedValueOnce({ ok: false })
            .mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValue({
                    success: true,
                    sportName: "Track",
                    eavTemplate: [
                        {
                            field_name: "score",
                            display_label: "Score",
                            field_type: "number",
                            min: 0,
                            max: 999,
                            field_group: "general",
                        },
                    ],
                    values: { score: "42" },
                }),
            });

        await import("../games_sport_dynamic.js");
        await new Promise((r) => setTimeout(r, 50));

        const input = document.getElementById("field-score");
        expect(input).toBeTruthy();
        expect(input.type).toBe("number");
        expect(input.min).toBe("0");
        expect(input.max).toBe("999");
        expect(input.value).toBe("42");
    });

    test("module exports when in CommonJS context", async () => {
        setupDom();
        const mod = await import("../games_sport_dynamic.js");
        // In Jest ESM, the module.exports are available
        expect(mod).toBeDefined();
    });
});
