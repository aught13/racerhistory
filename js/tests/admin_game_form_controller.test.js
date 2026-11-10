/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

jest.mock("../lib/game_form_lookups.js", () => ({
    initGameFormLookups: jest.fn(),
}));
jest.mock("../legacy/games_sport_dynamic.js", () => ({
    initGamesSportDynamic: jest.fn(),
}));

import { initGameFormLookups } from "../lib/game_form_lookups.js";
import { initGamesSportDynamic } from "../legacy/games_sport_dynamic.js";
import AdminGameFormController from "../controllers/admin_game_form_controller.js";

describe("admin-game-form controller", () => {
    let application;

    beforeEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML =
            '<div id="admin-game-form-root" data-controller="admin-game-form" data-action="turbo:before-cache@document->admin-game-form#beforeCache turbo:load@document->admin-game-form#afterCache"></div>';
    });

    function startApplication() {
        application = Application.start();
        application.register("admin-game-form", AdminGameFormController);
    }

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes form modules on connect", async () => {
        startApplication();

        await Promise.resolve();
        await Promise.resolve();
        await Promise.resolve();
        await Promise.resolve();

        expect(initGameFormLookups).toHaveBeenCalledTimes(1);
        expect(initGamesSportDynamic).toHaveBeenCalledTimes(1);
    });

    test("cleans up before Turbo cache and reinitializes after restore", async () => {
        const lookupsCleanup = jest.fn();
        const sportCleanup = jest.fn();
        initGameFormLookups.mockReturnValue(lookupsCleanup);
        initGamesSportDynamic.mockReturnValue(sportCleanup);

        startApplication();

        await Promise.resolve();
        await Promise.resolve();
        await Promise.resolve();
        await Promise.resolve();

        document.dispatchEvent(new Event("turbo:before-cache"));
        expect(lookupsCleanup).toHaveBeenCalledTimes(1);
        expect(sportCleanup).toHaveBeenCalledTimes(1);

        document.dispatchEvent(new Event("turbo:load"));
        await Promise.resolve();
        await Promise.resolve();
        await Promise.resolve();
        await Promise.resolve();

        expect(initGameFormLookups).toHaveBeenCalledTimes(2);
        expect(initGamesSportDynamic).toHaveBeenCalledTimes(2);

        document.dispatchEvent(new Event("turbo:before-cache"));
        expect(lookupsCleanup).toHaveBeenCalledTimes(2);
        expect(sportCleanup).toHaveBeenCalledTimes(2);
    });
});
