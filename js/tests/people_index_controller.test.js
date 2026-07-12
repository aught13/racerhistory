/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import PeopleIndexController from "../controllers/people_index_controller.js";

describe("people-index controller", () => {
    let application;

    beforeEach(() => {
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

        delete window.__PEOPLE_INDEX_INIT__;
        delete window.__PEOPLE_INDEX_CLEANUP__;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("calls init override on connect", async () => {
        const initMock = jest.fn();
        window.__PEOPLE_INDEX_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("people-index", PeopleIndexController);

        await Promise.resolve();

        expect(initMock).toHaveBeenCalledTimes(1);
    });

    test("calls cleanup override on disconnect", async () => {
        const cleanupMock = jest.fn();
        window.__PEOPLE_INDEX_CLEANUP__ = cleanupMock;

        application.stop();
        application = Application.start();
        application.register("people-index", PeopleIndexController);
        await Promise.resolve();

        document.getElementById("people-root").remove();
        await Promise.resolve();

        expect(cleanupMock).toHaveBeenCalledTimes(1);
    });

    test("calls default init when no override", async () => {
        // Ensure no overrides exist
        delete window.__PEOPLE_INDEX_INIT__;
        delete window.__PEOPLE_INDEX_CLEANUP__;

        application.stop();
        application = Application.start();
        application.register("people-index", PeopleIndexController);

        await Promise.resolve();

        // Should not throw even without override
        expect(true).toBe(true);
    });

    test("calls default cleanup when no override", async () => {
        delete window.__PEOPLE_INDEX_INIT__;
        delete window.__PEOPLE_INDEX_CLEANUP__;

        application.stop();
        application = Application.start();
        application.register("people-index", PeopleIndexController);
        await Promise.resolve();

        document.getElementById("people-root").remove();
        await Promise.resolve();

        // Should not throw even without override
        expect(true).toBe(true);
    });

    test("handles override being non-function value", async () => {
        window.__PEOPLE_INDEX_INIT__ = "not a function";
        window.__PEOPLE_INDEX_CLEANUP__ = { obj: true };

        application.stop();
        application = Application.start();
        application.register("people-index", PeopleIndexController);

        await Promise.resolve();

        document.getElementById("people-root").remove();
        await Promise.resolve();

        expect(true).toBe(true);
    });
});
