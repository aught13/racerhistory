/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

jest.mock("../lib/seasons_page_runtime.js", () => ({
    cleanupSeasonsPage: jest.fn(),
    enhancedBoot: jest.fn(),
}));

import {
    cleanupSeasonsPage,
    enhancedBoot,
} from "../lib/seasons_page_runtime.js";
import SeasonsPageController from "../controllers/seasons_page_controller.js";

describe("seasons-page controller", () => {
    let application;

    beforeEach(() => {
        jest.clearAllMocks();
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

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes the seasons runtime on connect", async () => {
        await Promise.resolve();

        expect(enhancedBoot).toHaveBeenCalledWith(
            undefined,
            document.getElementById("seasons-root"),
        );
    });

    test("cleans up the seasons runtime on disconnect", async () => {
        await Promise.resolve();

        document.getElementById("seasons-root").remove();
        await Promise.resolve();

        expect(cleanupSeasonsPage).toHaveBeenCalledTimes(1);
    });

    test("cleans up the seasons runtime before Turbo caches the page", async () => {
        document.dispatchEvent(new Event("turbo:before-cache"));
        await Promise.resolve();

        expect(cleanupSeasonsPage).toHaveBeenCalledWith(
            document.getElementById("seasons-root"),
        );
    });
});
