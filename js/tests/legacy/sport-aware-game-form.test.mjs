/** @jest-environment jsdom */
import { jest, beforeEach, describe, test, expect } from "@jest/globals";

describe("SportAwareGameForm", () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <select id="team-season-select" data-sport-url="/fake"></select>
            <div id="sport-specific-section"></div>
            <span id="current-sport"></span>
            <div id="sport-indicator"><div class="alert"></div></div>
            <span id="sport-loading"></span>
        `;
        jest.resetModules();
        global.fetch = jest.fn(() =>
            Promise.resolve({
                ok: true,
                json: () => Promise.resolve({ success: false }),
            }),
        );
    });

    test("constructor and fallback render", async () => {
        // Import as module - file exports class when required
        const { default: Module } =
            await import("../../legacy/sport-aware-game-form.js");
        // We expect it to instantiate without throwing
        const inst = new Module();
        expect(inst).toBeTruthy();
        // Simulate change to trigger fallback
        const sel = document.getElementById("team-season-select");
        sel.value = "2";
        sel.dispatchEvent(new Event("change"));
        await new Promise((r) => global.setTimeout(r, 0));
        const section = document.getElementById("sport-specific-section");
        expect(section.innerHTML).toContain("Game Details");
    });

    test("utility methods", async () => {
        const { default: Module } =
            await import("../../legacy/sport-aware-game-form.js");
        const inst = new Module();
        expect(inst.capitalizeFirst("abc")).toBe("Abc");
        expect(inst.escapeHtml("<script>")).toBe("&lt;script&gt;");
    });

    test("renderSportFields produces grouped cards and correct inputs", async () => {
        const { default: Module } =
            await import("../../legacy/sport-aware-game-form.js");
        const inst = new Module();

        // Prepare a sample eavTemplate with two groups and number/text fields
        const data = {
            sportName: "SampleSport",
            eavTemplate: [
                {
                    field_name: "score_team",
                    display_label: "Score Team",
                    field_type: "number",
                    field_group: "main",
                    min: 0,
                    max: 100,
                },
                {
                    field_name: "score_opp",
                    display_label: "Score Opp",
                    field_type: "number",
                    field_group: "main",
                    min: 0,
                    max: 100,
                },
                {
                    field_name: "coach",
                    display_label: "Coach",
                    field_type: "text",
                    field_group: "staff",
                },
            ],
        };

        // Call renderSportFields and assert DOM changes
        inst.renderSportFields(data);
        const section = document.getElementById("sport-specific-section");
        expect(section).toBeTruthy();
        const html = section.innerHTML;

        // Should include group headers
        expect(html).toContain("Main");
        expect(html).toContain("Staff");

        // Numeric inputs should have min/max attributes
        expect(html).toMatch(/name="score_team"[\s\S]*type="number"/);
        expect(html).toMatch(/name="score_team"[\s\S]*min="0"/);
        expect(html).toMatch(/name="score_team"[\s\S]*max="100"/);

        // Text input should be present (order-independent)
        expect(html).toMatch(/name="coach"/);
        expect(html).toMatch(/type="text"/);
    });

    test("updateSportFields maps values to legacy inputs on success", async () => {
        const { default: Module } =
            await import("../../legacy/sport-aware-game-form.js");
        const inst = new Module();

        // Create legacy inputs expected to be set
        const legacy1 = document.createElement("input");
        legacy1.name = "period_1_mur";
        document.body.appendChild(legacy1);

        const legacy2 = document.createElement("input");
        legacy2.name = "period_1_opp";
        document.body.appendChild(legacy2);

        // Mock fetch response with success and values
        global.fetch = jest.fn(() =>
            Promise.resolve({
                ok: true,
                json: () =>
                    Promise.resolve({
                        success: true,
                        sportName: "X",
                        eavTemplate: [],
                        values: { period_1_team: "10", period_1_opponent: "8" },
                    }),
            }),
        );

        await inst.updateSportFields("123");

        expect(document.getElementsByName("period_1_mur")[0].value).toBe("10");
        expect(document.getElementsByName("period_1_opp")[0].value).toBe("8");
    });

    test("updateSportFields handles non-OK response and shows fallback", async () => {
        const { default: Module } =
            await import("../../legacy/sport-aware-game-form.js");
        const inst = new Module();

        // Mock non-OK response
        global.fetch = jest.fn(() =>
            Promise.resolve({ ok: false, status: 500 }),
        );

        // Spy on showFallbackFields
        const fallbackSpy = jest.spyOn(inst, "showFallbackFields");

        await inst.updateSportFields("123");

        expect(fallbackSpy).toHaveBeenCalled();
    });

    test("updateSportFields handles network error and shows fallback", async () => {
        const { default: Module } =
            await import("../../legacy/sport-aware-game-form.js");
        const inst = new Module();

        // Mock fetch to throw
        global.fetch = jest.fn(() => Promise.reject(new Error("network")));

        const fallbackSpy = jest.spyOn(inst, "showFallbackFields");

        await inst.updateSportFields("123");

        expect(fallbackSpy).toHaveBeenCalled();
    });
});
