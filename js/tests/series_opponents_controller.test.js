/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

jest.mock("../lib/series_opponents_runtime.js", () => ({
    cleanupSeriesOpponentsTable: jest.fn(),
    initSeriesOpponentsTable: jest.fn(),
}));

import {
    cleanupSeriesOpponentsTable,
    initSeriesOpponentsTable,
} from "../lib/series_opponents_runtime.js";
import SeriesOpponentsController from "../controllers/series_opponents_controller.js";

describe("series-opponents controller", () => {
    let application;

    beforeEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML =
            '<div id="series-root" data-controller="series-opponents"></div>';

        application = Application.start();
        application.register("series-opponents", SeriesOpponentsController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes the opponents table on connect", async () => {
        await Promise.resolve();

        expect(initSeriesOpponentsTable).toHaveBeenCalledWith(
            document.getElementById("series-root"),
        );
    });

    test("cleans up the opponents table on disconnect", async () => {
        await Promise.resolve();

        document.getElementById("series-root").remove();
        await Promise.resolve();

        expect(cleanupSeriesOpponentsTable).toHaveBeenCalledTimes(1);
    });

    test("cleans up the opponents table before Turbo caches the page", async () => {
        document.dispatchEvent(new Event("turbo:before-cache"));
        await Promise.resolve();

        expect(cleanupSeriesOpponentsTable).toHaveBeenCalledWith(
            document.getElementById("series-root"),
        );
    });
});
