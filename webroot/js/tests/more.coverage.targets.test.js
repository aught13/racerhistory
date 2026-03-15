import { jest } from "@jest/globals";

describe("Additional coverage targets", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        jest.resetModules();
    });

    test("games_sport_dynamic renderEav creates cards for grouped fields", async () => {
        // prepare DOM elements used by module
        document.body.innerHTML =
            '<select id="team-season-select" data-sport-url="/test"></select><div id="sport-specific-section"></div>';
        const mod = await import("../games_sport_dynamic");
        const renderEav =
            mod.renderEav || (mod.__internals && mod.__internals.renderEav);

        const template = [
            {
                field_group: "stats",
                field_name: "goals",
                display_label: "Goals",
                field_type: "number",
            },
            {
                field_group: "stats",
                field_name: "assists",
                display_label: "Assists",
                field_type: "number",
            },
            {
                field_group: "meta",
                field_name: "weather",
                display_label: "Weather",
                field_type: "text",
            },
        ];
        renderEav(template, { goals: "2", assists: "1", weather: "sunny" });

        const cards = document.querySelectorAll(
            "#sport-specific-section .card",
        );
        expect(cards.length).toBe(2);
        expect(
            cards[0].querySelectorAll("input").length,
        ).toBeGreaterThanOrEqual(1);
    });

    test("sport-aware-game-form maps fetched values into legacy inputs", async () => {
        // Prepare DOM for sport-aware form with legacy period inputs
        document.body.innerHTML = `
      <select id="team-season-select" data-sport-url="/api/sport-meta"></select>
      <div id="sport-specific-section"></div>
      <div id="sport-indicator"><div class="alert"></div></div>
      <span id="current-sport"></span>
      <span id="sport-loading"></span>
      <input type="hidden" id="game-id-hidden" value="" />
      <form id="game-form"><input name="period_1_mur" /></form>
    `;
        // require sport-aware module and simulate fetch
        const saf = await import("../sport-aware-game-form");
        const SportAwareGameForm = saf.default || saf;
        // stub fetch: response uses period EAV keys that map to legacy inputs
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({
                success: true,
                sportName: "Hockey",
                eavTemplate: [],
                values: { period_1_team: "3" },
            }),
        });

        // call update method if available
        if (saf && SportAwareGameForm) {
            const inst = new SportAwareGameForm({
                selectId: "team-season-select",
            });
            await inst.updateSportFields("1");
            // legacy input should receive mapped value via periodKeyMap
            const legacy = document.querySelector('input[name="period_1_mur"]');
            expect(legacy).toBeTruthy();
            expect(legacy.value).toBe("3");
        } else {
            // fallback assertion: ensure module loaded
            expect(saf).toBeTruthy();
        }
    });

    test("renderAssociated handles associated objects with label/name", async () => {
        document.body.innerHTML =
            '<div id="confirm-delete-modal"><ul id="confirm-delete-modal-assoc"></ul></div>';
        const { __internals } = await import("../admin");
        const internals = __internals || {};

        const associated = [{ label: "One" }, { name: "Two" }, { foo: "Bar" }];
        expect(() =>
            internals.renderAssociated(
                document.getElementById("confirm-delete-modal"),
                associated,
            ),
        ).not.toThrow();
        const items = document.querySelectorAll(
            "#confirm-delete-modal-assoc li",
        );
        expect(items.length).toBe(3);
        expect(items[0].textContent).toContain("One");
        expect(items[1].textContent).toContain("Two");
    });
});
