/**
 * @jest-environment jsdom
 */

import { describe, test, expect, beforeEach, jest } from "@jest/globals";

describe("turbo_scroll.js", () => {
    beforeEach(() => {
        jest.resetModules();
        jest.spyOn(window, "scrollTo").mockImplementation(() => {});
    });

    afterEach(() => {
        jest.restoreAllMocks();
    });

    test("scrolls to top when blog frame loads", async () => {
        const mod = await import("../../lib/turbo_scroll.js");
        mod.initTurboScrollBehavior();

        const frame = document.createElement("div");
        frame.id = "blog";
        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: frame });

        document.dispatchEvent(event);

        expect(window.scrollTo).toHaveBeenCalledWith({
            top: 0,
            left: 0,
            behavior: "auto",
        });
    });

    test("skips scroll when frame is not blog", async () => {
        const mod = await import("../../lib/turbo_scroll.js");
        mod.initTurboScrollBehavior();

        const frame = document.createElement("div");
        frame.id = "other";
        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: frame });

        document.dispatchEvent(event);

        expect(window.scrollTo).not.toHaveBeenCalled();
    });

    test("skips scroll when frame is null", async () => {
        const mod = await import("../../lib/turbo_scroll.js");
        mod.initTurboScrollBehavior();

        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: null });

        document.dispatchEvent(event);

        expect(window.scrollTo).not.toHaveBeenCalled();
    });

    test("skips scroll when frame is not an object", async () => {
        const mod = await import("../../lib/turbo_scroll.js");
        mod.initTurboScrollBehavior();

        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: "not an object" });

        document.dispatchEvent(event);

        expect(window.scrollTo).not.toHaveBeenCalled();
    });
});
