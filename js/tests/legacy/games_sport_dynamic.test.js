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
        const { fetchMeta } =
            await import("../../legacy/games_sport_dynamic.js");
        await fetchMeta("1");
        expect(global.fetch).toHaveBeenCalled();
        const name = document.getElementById("current-sport");
        expect(name.textContent).toBe("Soccer");
    });

    test("initializer cleanup removes the listener and aborts its request", async () => {
        const { initGamesSportDynamic } =
            await import("../../legacy/games_sport_dynamic.js");
        const select = document.getElementById("team-season-select");
        select.innerHTML =
            '<option value="1">Season One</option><option value="2">Season Two</option>';
        const cleanup = initGamesSportDynamic();

        select.value = "1";
        select.dispatchEvent(new Event("change"));
        const signal = global.fetch.mock.calls[0][1].signal;
        expect(signal.aborted).toBe(false);

        cleanup();
        expect(signal.aborted).toBe(true);
        expect(select.dataset.gamesSportDynamicBound).toBeUndefined();

        const callsAfterCleanup = global.fetch.mock.calls.length;
        select.value = "2";
        select.dispatchEvent(new Event("change"));
        expect(global.fetch).toHaveBeenCalledTimes(callsAfterCleanup);
    });
});
