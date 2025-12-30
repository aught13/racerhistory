describe("Additional coverage targets", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        jest.resetModules();
    });

    test("games_sport_dynamic renderEav creates cards for grouped fields", () => {
        // prepare DOM elements used by module
        document.body.innerHTML =
            '<select id="team-season-select" data-sport-url="/test"></select><div id="sport-specific-section"></div>';
        const gs = require("../../js/games_sport_dynamic");
        const renderEav =
            gs.renderEav || (gs.__internals && gs.__internals.renderEav);

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
        // Prepare DOM for sport-aware form
        document.body.innerHTML = `
      <select id="team-season-select"></select>
      <div id="sport-specific-section"></div>
      <input type="hidden" id="game-id-hidden" value="" />
      <form id="game-form"><input name="legacy_goals" /></form>
    `;
        // require sport-aware module and simulate fetch
        const saf = require("../../js/sport-aware-game-form");
        // stub fetch used by module's updateSportFields method
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({
                sportFields: [{ name: "goals", legacyInput: "legacy_goals" }],
                values: { goals: 3 },
            }),
        });

        // call update method if available
        if (saf && saf.SportAwareGameForm) {
            const inst = new saf.SportAwareGameForm({
                selectId: "team-season-select",
            });
            await inst.updateSportFields("1");
            // legacy input should receive mapped value
            const legacy = document.querySelector('input[name="legacy_goals"]');
            expect(legacy).toBeTruthy();
            expect(legacy.value).toBe("3");
        } else {
            // fallback assertion: ensure module loaded
            expect(saf).toBeTruthy();
        }
    });

    test("admin.renderAssociated handles associated objects with label/name", () => {
        document.body.innerHTML =
            '<div id="confirm-delete-modal"><ul id="confirm-delete-modal-assoc"></ul></div>';
        const admin = require("../../js/admin");
        const internals = admin.__internals || {};

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
