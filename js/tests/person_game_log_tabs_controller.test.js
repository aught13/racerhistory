/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import PersonGameLogTabsController from "../controllers/person_game_log_tabs_controller.js";

describe("person-game-log-tabs controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML =
            '<div id="person-log-root" data-controller="person-game-log-tabs"></div>';

        application = Application.start();
        application.register("person-game-log-tabs", PersonGameLogTabsController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.__PERSON_GAME_LOG_TABS_INIT__;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("calls init override on connect", async () => {
        const initMock = jest.fn();
        window.__PERSON_GAME_LOG_TABS_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("person-game-log-tabs", PersonGameLogTabsController);
        await Promise.resolve();

        expect(initMock).toHaveBeenCalledWith({
            root: document.getElementById("person-log-root"),
        });
    });

    test("calls init override for turbo frame loads", async () => {
        const initMock = jest.fn();
        window.__PERSON_GAME_LOG_TABS_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("person-game-log-tabs", PersonGameLogTabsController);
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
