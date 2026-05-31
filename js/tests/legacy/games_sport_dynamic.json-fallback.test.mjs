/**
 * @jest-environment jsdom
 */
import { jest, beforeEach, describe, test, expect } from "@jest/globals";

// test uses DOM APIs and module imports; no file system access needed here

describe("games_sport_dynamic JSON fallback", () => {
    let moduleExports;

    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = `
      <select id="team-season-select" data-sport-url="/admin/games/ajax-game-eav-meta"></select>
      <div id="sport-specific-section"></div>
      <div id="sport-indicator" style="display:none"><span id="current-sport"></span><div id="sport-loading" style="display:none"></div></div>
      <input type="hidden" id="game-id-hidden" value="" />
    `;
    });

    test("falls back to JSON and renders client-side when HTML fetch fails", async () => {
        const jsonResponse = {
            success: true,
            sportName: "Basketball",
            eavTemplate: [
                {
                    field_name: "period_1_team",
                    display_label: "P1 Team",
                    field_type: "number",
                    field_group: "scoring",
                },
            ],
            values: {},
        };

        // First fetch (HTML) will fail (simulate network or non-OK), second fetch returns JSON
        global.fetch = jest
            .fn()
            .mockImplementationOnce(() => Promise.reject(new Error("network")))
            .mockImplementationOnce(() =>
                Promise.resolve({ json: () => Promise.resolve(jsonResponse) }),
            );

        moduleExports = await import("../../legacy/games_sport_dynamic.js");

        // Call fetchMeta helper directly to trigger fallback JSON flow
        await moduleExports.fetchMeta("1");

        const section = document.getElementById("sport-specific-section");
        expect(section.innerHTML).toContain("P1 Team");
    });
});
