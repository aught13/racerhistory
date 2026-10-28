/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

jest.mock("../lib/people_index_runtime.js", () => ({
    cleanupPeopleIndexPage: jest.fn(),
    initPeopleIndexPage: jest.fn(),
}));

import {
    cleanupPeopleIndexPage,
    initPeopleIndexPage,
} from "../lib/people_index_runtime.js";
import PeopleIndexController from "../controllers/people_index_controller.js";

describe("people-index controller", () => {
    let application;

    beforeEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML =
            '<div id="people-root" data-controller="people-index"></div>';

        application = Application.start();
        application.register("people-index", PeopleIndexController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes the people runtime on connect", async () => {
        await Promise.resolve();

        expect(initPeopleIndexPage).toHaveBeenCalledTimes(1);
    });

    test("cleans up the people runtime on disconnect", async () => {
        await Promise.resolve();
        document.getElementById("people-root").remove();
        await Promise.resolve();

        expect(cleanupPeopleIndexPage).toHaveBeenCalledTimes(1);
    });
});
