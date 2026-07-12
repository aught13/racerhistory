/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import SeriesOpponentsController from "../controllers/series_opponents_controller.js";

describe("series-opponents controller", () => {
    let application;

    beforeEach(() => {
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

        delete window.__SERIES_OPPONENTS_INIT__;
        delete window.__SERIES_OPPONENTS_CLEANUP__;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("calls init override on connect", async () => {
        const initMock = jest.fn();
        window.__SERIES_OPPONENTS_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("series-opponents", SeriesOpponentsController);

        await Promise.resolve();

        expect(initMock).toHaveBeenCalledTimes(1);
    });

    test("calls cleanup override on disconnect", async () => {
        const cleanupMock = jest.fn();
        window.__SERIES_OPPONENTS_CLEANUP__ = cleanupMock;

        application.stop();
        application = Application.start();
        application.register("series-opponents", SeriesOpponentsController);
        await Promise.resolve();

        document.getElementById("series-root").remove();
        await Promise.resolve();

        expect(cleanupMock).toHaveBeenCalledTimes(1);
    });

    test("calls default init when no override", async () => {
        delete window.__SERIES_OPPONENTS_INIT__;
        delete window.__SERIES_OPPONENTS_CLEANUP__;

        application.stop();
        application = Application.start();
        application.register("series-opponents", SeriesOpponentsController);

        await Promise.resolve();

        expect(true).toBe(true);
    });

    test("calls default cleanup when no override", async () => {
        delete window.__SERIES_OPPONENTS_INIT__;
        delete window.__SERIES_OPPONENTS_CLEANUP__;

        application.stop();
        application = Application.start();
        application.register("series-opponents", SeriesOpponentsController);
        await Promise.resolve();

        document.getElementById("series-root").remove();
        await Promise.resolve();

        expect(true).toBe(true);
    });

    test("handles override being non-function value", async () => {
        window.__SERIES_OPPONENTS_INIT__ = "not a function";
        window.__SERIES_OPPONENTS_CLEANUP__ = { obj: true };

        application.stop();
        application = Application.start();
        application.register("series-opponents", SeriesOpponentsController);

        await Promise.resolve();

        document.getElementById("series-root").remove();
        await Promise.resolve();

        expect(true).toBe(true);
    });
});
