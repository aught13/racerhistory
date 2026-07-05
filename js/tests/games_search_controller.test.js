/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import GamesSearchController from "../controllers/games_search_controller.js";

describe("games-search controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML =
            '<div id="games-root" data-controller="games-search"></div>';

        application = Application.start();
        application.register("games-search", GamesSearchController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.__GAMES_SEARCH_INIT__;
        delete window.__GAMES_SEARCH_CLEANUP__;
        delete window.__GAMES_SEARCH_RESET__;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("calls init override on connect", async () => {
        const initMock = jest.fn();
        window.__GAMES_SEARCH_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("games-search", GamesSearchController);

        await Promise.resolve();

        expect(initMock).toHaveBeenCalledTimes(1);
    });

    test("calls cleanup and reset overrides on disconnect", async () => {
        const cleanupMock = jest.fn();
        const resetMock = jest.fn();
        window.__GAMES_SEARCH_CLEANUP__ = cleanupMock;
        window.__GAMES_SEARCH_RESET__ = resetMock;

        application.stop();
        application = Application.start();
        application.register("games-search", GamesSearchController);
        await Promise.resolve();

        document.getElementById("games-root").remove();
        await Promise.resolve();

        expect(cleanupMock).toHaveBeenCalledTimes(1);
        expect(resetMock).toHaveBeenCalledTimes(1);
    });
});
