/**
 * @jest-environment jsdom
 */

import { describe, test, expect, beforeEach, jest } from "@jest/globals";

describe("season_view_runtime", () => {
    beforeEach(() => {
        jest.resetModules();
        delete window.__SEASON_VIEW_INIT__;
    });

    test("uses window override when available", async () => {
        const mockInit = jest.fn();
        window.__SEASON_VIEW_INIT__ = mockInit;

        const mod = await import("../../lib/season_view_runtime.js");
        mod.initSeasonViewRoot();

        expect(mockInit).toHaveBeenCalled();
    });

    test("uses default when no override", async () => {
        delete window.__SEASON_VIEW_INIT__;

        const mod = await import("../../lib/season_view_runtime.js");
        mod.initSeasonViewRoot();

        expect(true).toBe(true); // Just verify it ran without error
    });

    test("bootSeasonView handles turbo:frame-load events", async () => {
        const mockInit = jest.fn();
        window.__SEASON_VIEW_INIT__ = mockInit;

        const frame = document.createElement("div");
        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: frame });

        const mod = await import("../../lib/season_view_runtime.js");
        mod.bootSeasonView(event);

        expect(mockInit).toHaveBeenCalled();
    });

    test("bootSeasonView handles non-turbo events", async () => {
        const mockInit = jest.fn();
        window.__SEASON_VIEW_INIT__ = mockInit;

        const event = new Event("custom-event");
        const mod = await import("../../lib/season_view_runtime.js");
        mod.bootSeasonView(event);

        expect(mockInit).toHaveBeenCalled();
    });

    test("bootSeasonView handles invalid frame targets", async () => {
        const mockInit = jest.fn();
        window.__SEASON_VIEW_INIT__ = mockInit;

        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: "not an element" });

        const mod = await import("../../lib/season_view_runtime.js");
        mod.bootSeasonView(event);

        // Should fall back to using document
        expect(mockInit).toHaveBeenCalled();
    });

    test("bootSeasonView handles undefined event", async () => {
        const mockInit = jest.fn();
        window.__SEASON_VIEW_INIT__ = mockInit;

        const mod = await import("../../lib/season_view_runtime.js");
        mod.bootSeasonView();

        expect(mockInit).toHaveBeenCalled();
    });
});
