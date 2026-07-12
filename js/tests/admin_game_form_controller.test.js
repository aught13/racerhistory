/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import AdminGameFormController from "../controllers/admin_game_form_controller.js";

describe("admin-game-form controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML =
            '<div id="admin-game-form-root" data-controller="admin-game-form"></div>';

        application = Application.start();
        application.register("admin-game-form", AdminGameFormController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.__ADMIN_GAME_FORM_LOOKUPS_INIT__;
        delete window.__ADMIN_GAME_FORM_SPORT_INIT__;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("calls override initializers on connect", async () => {
        const lookupsInitMock = jest.fn();
        const sportInitMock = jest.fn();
        window.__ADMIN_GAME_FORM_LOOKUPS_INIT__ = lookupsInitMock;
        window.__ADMIN_GAME_FORM_SPORT_INIT__ = sportInitMock;

        application.stop();
        application = Application.start();
        application.register("admin-game-form", AdminGameFormController);

        await Promise.resolve();
        await Promise.resolve();
        await Promise.resolve();
        await Promise.resolve();

        expect(lookupsInitMock).toHaveBeenCalledTimes(1);
        expect(sportInitMock).toHaveBeenCalledTimes(1);
    });
});
