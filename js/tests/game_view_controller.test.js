/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import GameViewController from "../controllers/game_view_controller.js";

describe("game-view controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML =
            '<div id="game-view-root" data-controller="game-view"></div>';

        application = Application.start();
        application.register("game-view", GameViewController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.__GAME_VIEW_INIT__;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("calls init override on connect", async () => {
        const initMock = jest.fn();
        window.__GAME_VIEW_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("game-view", GameViewController);

        await Promise.resolve();

        expect(initMock).toHaveBeenCalledTimes(1);
    });

    test("calls init override for turbo frame load", async () => {
        const initMock = jest.fn();
        window.__GAME_VIEW_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("game-view", GameViewController);
        await Promise.resolve();

        const frame = document.createElement("turbo-frame");
        frame.id = "test-frame";
        document.body.appendChild(frame);

        const callsBefore = initMock.mock.calls.length;

        frame.dispatchEvent(
            new CustomEvent("turbo:frame-load", {
                bubbles: true,
                detail: {},
            }),
        );

        await Promise.resolve();

        expect(initMock.mock.calls.length).toBeGreaterThan(callsBefore);
        expect(initMock).toHaveBeenCalledWith({ root: frame });
    });
});
