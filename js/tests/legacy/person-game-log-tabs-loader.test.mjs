/** @jest-environment jsdom */

import { describe, test, expect, beforeEach, jest } from "@jest/globals";

async function importLoader() {
    jest.resetModules();
    const bootMock = jest.fn();
    jest.doMock("../../lib/person_game_log_tabs_runtime.js", () => ({
        __esModule: true,
        bootPersonGameLogTabs: bootMock,
    }));

    const module = await import("../../legacy/person-game-log-tabs-loader.mjs");
    return { bootMock, boot: module.__personGameLogTabsBoot };
}

describe("person-game-log-tabs loader", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        Object.defineProperty(document, "readyState", {
            value: "complete",
            configurable: true,
        });
    });

    test("calls the runtime boot function on module load", async () => {
        const { bootMock } = await importLoader();

        expect(bootMock.mock.calls).toEqual([[]]);
    });

    test("passes turbo frame root on frame load event", async () => {
        const addSpy = jest.spyOn(document, "addEventListener");
        const { bootMock } = await importLoader();

        const frameHandler = addSpy.mock.calls.find(
            (call) => call[0] === "turbo:frame-load",
        )?.[1];
        expect(frameHandler).toBeTruthy();

        const frame = document.createElement("turbo-frame");
        document.body.appendChild(frame);
        const event = new Event("turbo:frame-load", { bubbles: true });
        Object.defineProperty(event, "target", { value: frame });

        bootMock.mockClear();
        frameHandler(event);

        expect(bootMock).toHaveBeenCalledWith(event);
        addSpy.mockRestore();
    });

    test("fires boot on DOMContentLoaded when loading", async () => {
        Object.defineProperty(document, "readyState", {
            value: "loading",
            configurable: true,
        });

        const addSpy = jest.spyOn(document, "addEventListener");
        const { bootMock } = await importLoader();

        const domHandler = addSpy.mock.calls.find(
            (call) => call[0] === "DOMContentLoaded",
        )?.[1];
        expect(domHandler).toBeTruthy();

        const event = new Event("DOMContentLoaded", { bubbles: true });
        bootMock.mockClear();
        domHandler(event);

        expect(bootMock).toHaveBeenCalledWith(event);
        Object.defineProperty(document, "readyState", {
            value: "complete",
            configurable: true,
        });
        addSpy.mockRestore();
    });
});
