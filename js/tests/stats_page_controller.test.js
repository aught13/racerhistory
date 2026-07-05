/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import StatsPageController from "../controllers/stats_page_controller.js";

describe("stats-page controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML =
            '<div id="stats-root" data-controller="stats-page"></div>';

        application = Application.start();
        application.register("stats-page", StatsPageController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.__STATS_PAGE_INIT__;
        delete window.__STATS_PAGE_CLEANUP__;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("calls init override on connect", async () => {
        const initMock = jest.fn();
        window.__STATS_PAGE_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("stats-page", StatsPageController);

        await Promise.resolve();

        expect(initMock).toHaveBeenCalledTimes(1);
    });

    test("calls cleanup override on disconnect", async () => {
        const cleanupMock = jest.fn();
        window.__STATS_PAGE_CLEANUP__ = cleanupMock;

        application.stop();
        application = Application.start();
        application.register("stats-page", StatsPageController);
        await Promise.resolve();

        document.getElementById("stats-root").remove();
        await Promise.resolve();

        expect(cleanupMock).toHaveBeenCalledTimes(1);
    });
});
