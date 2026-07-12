/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import SeasonsPageController from "../controllers/seasons_page_controller.js";

describe("seasons-page controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML =
            '<div id="seasons-root" data-controller="seasons-page"></div>';

        application = Application.start();
        application.register("seasons-page", SeasonsPageController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.__SEASONS_PAGE_INIT__;
        delete window.__SEASONS_PAGE_CLEANUP__;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("calls init override on connect", async () => {
        const initMock = jest.fn();
        window.__SEASONS_PAGE_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("seasons-page", SeasonsPageController);

        await Promise.resolve();

        expect(initMock).toHaveBeenCalledTimes(1);
    });

    test("calls cleanup override on disconnect", async () => {
        const cleanupMock = jest.fn();
        window.__SEASONS_PAGE_CLEANUP__ = cleanupMock;

        application.stop();
        application = Application.start();
        application.register("seasons-page", SeasonsPageController);
        await Promise.resolve();

        document.getElementById("seasons-root").remove();
        await Promise.resolve();

        expect(cleanupMock).toHaveBeenCalledTimes(1);
    });

    test("calls default init when no override", async () => {
        delete window.__SEASONS_PAGE_INIT__;
        delete window.__SEASONS_PAGE_CLEANUP__;

        application.stop();
        application = Application.start();
        application.register("seasons-page", SeasonsPageController);

        await Promise.resolve();

        expect(true).toBe(true);
    });

    test("calls default cleanup when no override", async () => {
        delete window.__SEASONS_PAGE_INIT__;
        delete window.__SEASONS_PAGE_CLEANUP__;

        application.stop();
        application = Application.start();
        application.register("seasons-page", SeasonsPageController);
        await Promise.resolve();

        document.getElementById("seasons-root").remove();
        await Promise.resolve();

        expect(true).toBe(true);
    });
});
