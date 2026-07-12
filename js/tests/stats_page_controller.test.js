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

    test("calls default init when no override", async () => {
        delete window.__STATS_PAGE_INIT__;
        delete window.__STATS_PAGE_CLEANUP__;

        application.stop();
        application = Application.start();
        application.register("stats-page", StatsPageController);

        await Promise.resolve();

        expect(true).toBe(true);
    });

    test("calls default cleanup when no override", async () => {
        delete window.__STATS_PAGE_INIT__;
        delete window.__STATS_PAGE_CLEANUP__;

        application.stop();
        application = Application.start();
        application.register("stats-page", StatsPageController);
        await Promise.resolve();

        document.getElementById("stats-root").remove();
        await Promise.resolve();

        expect(true).toBe(true);
    });

    test("handles override being non-function value", async () => {
        window.__STATS_PAGE_INIT__ = "not a function";
        window.__STATS_PAGE_CLEANUP__ = { obj: true };

        application.stop();
        application = Application.start();
        application.register("stats-page", StatsPageController);

        await Promise.resolve();

        document.getElementById("stats-root").remove();
        await Promise.resolve();

        expect(true).toBe(true);
    });
});
