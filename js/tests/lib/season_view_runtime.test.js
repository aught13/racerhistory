/**
 * @jest-environment jsdom
 */

import { describe, test, expect, beforeEach, jest } from "@jest/globals";

jest.mock("../../legacy/modules/season-view-init.mjs", () => ({
    __esModule: true,
    default: jest.fn(),
}));

import initSeasonView from "../../legacy/modules/season-view-init.mjs";
import {
    bootSeasonView,
    initSeasonViewRoot,
} from "../../lib/season_view_runtime.js";

describe("season_view_runtime", () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test("initializes the supplied root and defaults to the document", () => {
        const root = document.createElement("div");

        initSeasonViewRoot(root);
        initSeasonViewRoot();

        expect(initSeasonView).toHaveBeenNthCalledWith(1, { root });
        expect(initSeasonView).toHaveBeenNthCalledWith(2, { root: document });
    });

    test("boots a frame root for turbo frame loads", () => {
        const frame = document.createElement("turbo-frame");
        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: frame });

        bootSeasonView(event);

        expect(initSeasonView).toHaveBeenCalledWith({ root: frame });
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
        ["missing event", undefined],
    ])("uses the document for a %s", (_label, event) => {
        bootSeasonView(event);

        expect(initSeasonView).toHaveBeenCalledWith({ root: document });
    });
});
