/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import SeasonViewController from "../controllers/season_view_controller.js";

describe("season-view controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML =
            '<div id="season-root" data-controller="season-view"></div>';

        application = Application.start();
        application.register("season-view", SeasonViewController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.__SEASON_VIEW_INIT__;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("calls init override on connect", async () => {
        const initMock = jest.fn();
        window.__SEASON_VIEW_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("season-view", SeasonViewController);
        await Promise.resolve();

        expect(initMock).toHaveBeenCalledWith({
            root: document.getElementById("season-root"),
        });
    });

    test("calls init override for turbo frame loads", async () => {
        const initMock = jest.fn();
        window.__SEASON_VIEW_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("season-view", SeasonViewController);
        await Promise.resolve();

        const frame = document.createElement("turbo-frame");
        document.body.appendChild(frame);

        frame.dispatchEvent(
            new CustomEvent("turbo:frame-load", {
                bubbles: true,
                detail: {},
            }),
        );
        await Promise.resolve();

        expect(initMock).toHaveBeenCalledWith({ root: frame });
    });
});
