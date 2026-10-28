/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

jest.mock("../lib/season_view_runtime.js", () => ({
    initSeasonViewRoot: jest.fn(),
}));

import { initSeasonViewRoot } from "../lib/season_view_runtime.js";
import SeasonViewController from "../controllers/season_view_controller.js";
import { publicDataTablesReady } from "../lib/public_vite_datatables.mjs";

describe("season-view controller", () => {
    let application;

    beforeEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML = `
            <div id="season-root" data-controller="season-view" data-action="turbo:before-cache@document->season-view#beforeCache">
                <div class="modal show" data-season-view-target="imageModal">
                    <button data-season-view-target="imageModalDismiss"></button>
                </div>
            </div>
        `;

        application = Application.start();
        application.register("season-view", SeasonViewController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes the controller root after DataTables is ready", async () => {
        await publicDataTablesReady;
        await Promise.resolve();

        expect(initSeasonViewRoot).toHaveBeenCalledWith(
            document.getElementById("season-root"),
        );
    });

    test("initializes a frame root after Turbo frame loads", async () => {
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

        expect(initSeasonViewRoot).toHaveBeenLastCalledWith(frame);
    });

    test("does not initialize a frame removed before dependencies are ready", async () => {
        await publicDataTablesReady;
        await Promise.resolve();
        initSeasonViewRoot.mockClear();

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

        expect(initSeasonViewRoot).not.toHaveBeenCalledWith(frame);
    });

    test("ignores Turbo frame loads with non-Element targets", async () => {
        await publicDataTablesReady;
        await Promise.resolve();
        const callsBefore = initSeasonViewRoot.mock.calls.length;
        const event = new CustomEvent("turbo:frame-load", {
            bubbles: true,
            detail: {},
        });
        Object.defineProperty(event, "target", { value: "not-an-element" });

        document.dispatchEvent(event);
        await Promise.resolve();

        expect(initSeasonViewRoot).toHaveBeenCalledTimes(callsBefore);
    });

    test("dismisses the open image modal before Turbo caches the page", async () => {
        await publicDataTablesReady;
        await Promise.resolve();
        const dismiss = document.querySelector(
            '[data-season-view-target="imageModalDismiss"]',
        );
        const click = jest.spyOn(dismiss, "click");

        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(click).toHaveBeenCalledTimes(1);
    });

    test("does not dismiss an image modal that is already closed", async () => {
        await publicDataTablesReady;
        await Promise.resolve();
        const modal = document.querySelector(
            '[data-season-view-target="imageModal"]',
        );
        modal.classList.remove("show");
        const dismiss = document.querySelector(
            '[data-season-view-target="imageModalDismiss"]',
        );
        const click = jest.spyOn(dismiss, "click");

        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(click).not.toHaveBeenCalled();
    });
});
