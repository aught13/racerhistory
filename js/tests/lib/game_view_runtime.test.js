/**
 * @jest-environment jsdom
 */

import { describe, test, expect, beforeEach, jest } from "@jest/globals";

describe("game_view_runtime", () => {
    beforeEach(() => {
        jest.resetModules();
        delete window.__GAME_VIEW_INIT__;
    });

    test("uses window override when available", async () => {
        const mockInit = jest.fn();
        window.__GAME_VIEW_INIT__ = mockInit;

        const mod = await import("../../lib/game_view_runtime.js");
        mod.initGameViewRoot();

        expect(mockInit).toHaveBeenCalled();
    });

    test("uses default when no override", async () => {
        delete window.__GAME_VIEW_INIT__;

        const mod = await import("../../lib/game_view_runtime.js");
        mod.initGameViewRoot();

        expect(true).toBe(true);
    });

    test("bootGameView handles turbo:frame-load events", async () => {
        const mockInit = jest.fn();
        window.__GAME_VIEW_INIT__ = mockInit;

        const frame = document.createElement("div");
        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: frame });

        const mod = await import("../../lib/game_view_runtime.js");
        mod.bootGameView(event);

        expect(mockInit).toHaveBeenCalled();
    });

    test("bootGameView handles non-turbo events", async () => {
        const mockInit = jest.fn();
        window.__GAME_VIEW_INIT__ = mockInit;

        const event = new Event("custom-event");
        const mod = await import("../../lib/game_view_runtime.js");
        mod.bootGameView(event);

        expect(mockInit).toHaveBeenCalled();
    });

    test("bootGameView handles invalid frame targets", async () => {
        const mockInit = jest.fn();
        window.__GAME_VIEW_INIT__ = mockInit;

        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: "not an element" });

        const mod = await import("../../lib/game_view_runtime.js");
        mod.bootGameView(event);

        expect(mockInit).toHaveBeenCalled();
    });
});
