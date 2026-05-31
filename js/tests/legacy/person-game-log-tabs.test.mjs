/** @jest-environment jsdom */

import { describe, test, expect, beforeEach, jest } from "@jest/globals";

async function loadModule() {
    jest.resetModules();
    const mod = await import("../../legacy/modules/person-game-log-tabs.mjs");
    return mod.default;
}

describe("person-game-log-tabs", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
    });

    test("loads turbo frame src on tab click", async () => {
        document.body.innerHTML = `
            <div data-person-game-log>
                <button type="button" data-person-game-log-tab data-person-game-log-frame="frame-1">Season</button>
                <turbo-frame id="frame-1" data-person-game-log-frame data-person-game-log-src="/people/game-log/1/1"></turbo-frame>
            </div>
        `;

        const init = await loadModule();
        init({ root: document });

        const tab = document.querySelector("[data-person-game-log-tab]");
        const frame = document.querySelector(
            "turbo-frame[data-person-game-log-src]",
        );
        expect(frame.getAttribute("src")).toBeNull();

        tab.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        expect(frame.getAttribute("src")).toBe("/people/game-log/1/1");
    });

    test("returns empty tabs collection when none are present", async () => {
        const init = await loadModule();
        const result = init({ root: document });
        expect(result.tabs).toEqual([]);
    });
});
