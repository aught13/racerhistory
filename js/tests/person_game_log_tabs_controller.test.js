/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

jest.mock("../lib/person_game_log_tabs_runtime.js", () => ({
    initPersonGameLogTablesRoot: jest.fn(),
    initPersonGameLogTabsRoot: jest.fn(),
}));

import {
    initPersonGameLogTablesRoot,
    initPersonGameLogTabsRoot,
} from "../lib/person_game_log_tabs_runtime.js";
import PersonGameLogTabsController from "../controllers/person_game_log_tabs_controller.js";
import { publicDataTablesReady } from "../lib/public_vite_datatables.mjs";

describe("person-game-log-tabs controller", () => {
    let application;

    beforeEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML =
            '<div id="person-log-root" data-controller="person-game-log-tabs"></div>';

        application = Application.start();
        application.register(
            "person-game-log-tabs",
            PersonGameLogTabsController,
        );
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes tabs and tables on connect", async () => {
        await publicDataTablesReady;
        await Promise.resolve();
        const root = document.getElementById("person-log-root");

        expect(initPersonGameLogTabsRoot).toHaveBeenCalledWith(root);
        expect(initPersonGameLogTablesRoot).toHaveBeenCalledWith(root);
    });

    test("initializes tabs and tables for Turbo frame loads", async () => {
        await publicDataTablesReady;
        await Promise.resolve();
        const frame = document.createElement("turbo-frame");
        document.body.appendChild(frame);

        frame.dispatchEvent(
            new CustomEvent("turbo:frame-load", {
                bubbles: true,
                detail: {},
            }),
        );
        await Promise.resolve();

        expect(initPersonGameLogTabsRoot).toHaveBeenLastCalledWith(frame);
        expect(initPersonGameLogTablesRoot).toHaveBeenLastCalledWith(frame);
    });

    test("ignores Turbo frame loads with non-Element targets", async () => {
        await publicDataTablesReady;
        await Promise.resolve();
        const callsBefore = initPersonGameLogTabsRoot.mock.calls.length;
        const event = new CustomEvent("turbo:frame-load", {
            bubbles: true,
            detail: {},
        });
        Object.defineProperty(event, "target", { value: "not-an-element" });

        document.dispatchEvent(event);
        await Promise.resolve();

        expect(initPersonGameLogTabsRoot).toHaveBeenCalledTimes(callsBefore);
    });
});
