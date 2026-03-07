/** @jest-environment jsdom */

import { jest } from "@jest/globals";
describe("games_sport_dynamic", () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <select id="team-season-select" data-sport-url="/fake"></select>
            <div id="sport-specific-section"></div>
            <span id="current-sport"></span>
            <span id="sport-loading"></span>
            <div id="sport-indicator"></div>
            <input id="game-id-hidden" value="" />
        `;
        jest.resetModules();
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () =>
                    Promise.resolve({
                        success: true,
                        sportName: "Soccer",
                        eavTemplate: [],
                        values: {},
                    }),
            }),
        );
    });

    test("init does not throw and can fetch meta", async () => {
        // Require module and call fetchMeta directly
        const { fetchMeta } = await import("../games_sport_dynamic.js");
        await fetchMeta("1");
        expect(global.fetch).toHaveBeenCalled();
        const name = document.getElementById("current-sport");
        expect(name.textContent).toBe("Soccer");
    });
});
