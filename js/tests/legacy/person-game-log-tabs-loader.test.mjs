/** @jest-environment jsdom */

import { describe, test, expect, beforeEach, jest } from "@jest/globals";

async function importLoader() {
    jest.resetModules();
    const initMock = jest.fn();
    globalThis.__PERSON_GAME_LOG_TABS_INIT__ = (...args) => initMock(...args);

    const module = await import("../../legacy/person-game-log-tabs-loader.mjs");
    return { initMock, boot: module.__personGameLogTabsBoot };
}

describe("person-game-log-tabs loader", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        Object.defineProperty(document, "readyState", {
            value: "complete",
            configurable: true,
        });
    });

    afterEach(() => {
        delete globalThis.__PERSON_GAME_LOG_TABS_INIT__;
    });

    test("calls init with document root on load", async () => {
        const { initMock, boot } = await importLoader();
        boot();
        expect(initMock).toHaveBeenCalled();
        const args = initMock.mock.calls[0][0];
        expect(args.root).toBe(document);
    });

    test("passes turbo frame root on frame load event", async () => {
        const addSpy = jest.spyOn(document, "addEventListener");
        const { initMock, boot } = await importLoader();

        const frameHandler = addSpy.mock.calls.find(
            (call) => call[0] === "turbo:frame-load",
        )?.[1];
        expect(frameHandler).toBeTruthy();

        const frame = document.createElement("turbo-frame");
        document.body.appendChild(frame);

        initMock.mockClear();
        boot({ type: "turbo:frame-load", target: frame });
        await Promise.resolve();

        expect(initMock).toHaveBeenCalledWith({ root: frame });
        addSpy.mockRestore();
    });

    test("fires boot on DOMContentLoaded when loading", async () => {
        Object.defineProperty(document, "readyState", {
            value: "loading",
            configurable: true,
        });

        const addSpy = jest.spyOn(document, "addEventListener");
        const { initMock, boot } = await importLoader();

        const domHandler = addSpy.mock.calls.find(
            (call) => call[0] === "DOMContentLoaded",
        )?.[1];
        expect(domHandler).toBeTruthy();

        initMock.mockClear();
        boot(new Event("DOMContentLoaded", { bubbles: true }));
        await Promise.resolve();

        expect(initMock).toHaveBeenCalled();
        Object.defineProperty(document, "readyState", {
            value: "complete",
            configurable: true,
        });
        addSpy.mockRestore();
    });
});
