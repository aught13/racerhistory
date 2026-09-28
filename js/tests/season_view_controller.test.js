/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import SeasonViewController from "../controllers/season_view_controller.js";
import { publicDataTablesReady } from "../lib/public_vite_datatables.mjs";

describe("season-view controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML =
            '<div id="season-root" data-controller="season-view"></div>';

        application = Application.start();
        application.register("season-view", SeasonViewController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.__SEASON_VIEW_INIT__;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("calls init override on connect", async () => {
        const initMock = jest.fn();
        window.__SEASON_VIEW_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("season-view", SeasonViewController);
        await Promise.resolve();
        await publicDataTablesReady;

        expect(initMock).toHaveBeenCalledWith({
            root: document.getElementById("season-root"),
        });
    });

    test("calls init override for turbo frame loads", async () => {
        const initMock = jest.fn();
        window.__SEASON_VIEW_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("season-view", SeasonViewController);
        await Promise.resolve();
        await publicDataTablesReady;

        const frame = document.createElement("turbo-frame");
        document.body.appendChild(frame);

        frame.dispatchEvent(
            new CustomEvent("turbo:frame-load", {
                bubbles: true,
                detail: {},
            }),
        );
        await Promise.resolve();

        expect(initMock).toHaveBeenCalledWith({ root: frame });
    });

    test("does not initialize a frame removed before dependencies are ready", async () => {
        const initMock = jest.fn();
        window.__SEASON_VIEW_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("season-view", SeasonViewController);
        await Promise.resolve();
        await publicDataTablesReady;

        const frame = document.createElement("turbo-frame");
        document.body.appendChild(frame);
        frame.dispatchEvent(
            new CustomEvent("turbo:frame-load", {
                bubbles: true,
                detail: {},
            }),
        );
        frame.remove();
        await Promise.resolve();

        expect(initMock).not.toHaveBeenCalledWith({ root: frame });
    });

    test("ignores turbo:frame-load events with non-Element targets", async () => {
        const initMock = jest.fn();
        window.__SEASON_VIEW_INIT__ = initMock;

        application.stop();
        application = Application.start();
        application.register("season-view", SeasonViewController);
        await Promise.resolve();
        await publicDataTablesReady;

        const callsBefore = initMock.mock.calls.length;

        // Dispatch event with a non-Element target
        const event = new CustomEvent("turbo:frame-load", {
            bubbles: true,
            detail: {},
        });
        Object.defineProperty(event, "target", { value: "not-an-element" });
        document.dispatchEvent(event);

        await Promise.resolve();

        // Init should not have been called for non-Element target
        expect(initMock.mock.calls.length).toBe(callsBefore);
    });
});
