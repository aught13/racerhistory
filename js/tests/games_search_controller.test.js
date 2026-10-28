/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

jest.mock("../lib/games_search_runtime.js", () => ({
    cleanupGamesPage: jest.fn(),
    initGamesPage: jest.fn(),
    resetGamesSearchRuntimeState: jest.fn(),
}));

import {
    cleanupGamesPage,
    initGamesPage,
    resetGamesSearchRuntimeState,
} from "../lib/games_search_runtime.js";
import GamesSearchController from "../controllers/games_search_controller.js";

describe("games-search controller", () => {
    let application;

    beforeEach(() => {
        jest.clearAllMocks();
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

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes the games runtime on connect", async () => {
        await Promise.resolve();

        expect(initGamesPage).toHaveBeenCalledTimes(1);
    });

    test("cleans up the games runtime on disconnect", async () => {
        await Promise.resolve();
        document.getElementById("games-root").remove();
        await Promise.resolve();

        expect(cleanupGamesPage).toHaveBeenCalledTimes(1);
        expect(resetGamesSearchRuntimeState).toHaveBeenCalledTimes(1);
    });
});
