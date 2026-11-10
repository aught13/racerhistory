/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

jest.mock("../lib/stats_page_runtime.js", () => ({
    cleanupStatsPage: jest.fn(),
    initStatsPage: jest.fn(),
}));

import { cleanupStatsPage, initStatsPage } from "../lib/stats_page_runtime.js";
import StatsPageController from "../controllers/stats_page_controller.js";

describe("stats-page controller", () => {
    let application;

    beforeEach(() => {
        jest.clearAllMocks();
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

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes the stats runtime on connect", async () => {
        await Promise.resolve();

        expect(initStatsPage).toHaveBeenCalledWith(
            document.getElementById("stats-root"),
        );
    });

    test("cleans up the stats runtime on disconnect", async () => {
        await Promise.resolve();

        document.getElementById("stats-root").remove();
        await Promise.resolve();

        expect(cleanupStatsPage).toHaveBeenCalledTimes(1);
    });

    test("cleans up the stats runtime before Turbo caches the page", async () => {
        document.dispatchEvent(new Event("turbo:before-cache"));
        await Promise.resolve();

        expect(cleanupStatsPage).toHaveBeenCalledWith(
            document.getElementById("stats-root"),
        );
    });
});
