/**
 * @jest-environment jsdom
 */

import { describe, test, expect, beforeEach, jest } from "@jest/globals";

jest.mock("../../legacy/modules/game-view-init.mjs", () => ({
    __esModule: true,
    default: jest.fn(),
}));

import initGameView from "../../legacy/modules/game-view-init.mjs";
import { bootGameView, initGameViewRoot } from "../../lib/game_view_runtime.js";

describe("game_view_runtime", () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test("initializes the supplied root and defaults to the document", () => {
        const root = document.createElement("div");

        initGameViewRoot(root);
        initGameViewRoot();

        expect(initGameView).toHaveBeenNthCalledWith(1, { root });
        expect(initGameView).toHaveBeenNthCalledWith(2, { root: document });
    });

    test("boots a frame root for turbo frame loads", () => {
        const frame = document.createElement("turbo-frame");
        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: frame });

        bootGameView(event);

        expect(initGameView).toHaveBeenCalledWith({ root: frame });
    });

    test.each([
        ["custom event", new Event("custom-event")],
        [
            "invalid frame target",
            (() => {
                const event = new Event("turbo:frame-load");
                Object.defineProperty(event, "target", {
                    value: "not an element",
                });
                return event;
            })(),
        ],
    ])("uses the document for a %s", (_label, event) => {
        bootGameView(event);

        expect(initGameView).toHaveBeenCalledWith({ root: document });
    });
});
