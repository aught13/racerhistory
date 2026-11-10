/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

jest.mock("../lib/game_view_runtime.js", () => ({
    initGameViewRoot: jest.fn(),
}));

import { initGameViewRoot } from "../lib/game_view_runtime.js";
import GameViewController from "../controllers/game_view_controller.js";

describe("game-view controller", () => {
    let application;

    beforeEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML = `
            <div id="game-view-root" data-controller="game-view" data-action="turbo:before-cache@document->game-view#beforeCache">
                <div class="modal show" data-game-view-target="imageModal">
                    <button data-game-view-target="imageModalDismiss"></button>
                </div>
            </div>
        `;

        application = Application.start();
        application.register("game-view", GameViewController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("initializes the controller root on connect", async () => {
        await Promise.resolve();

        expect(initGameViewRoot).toHaveBeenCalledWith(
            document.getElementById("game-view-root"),
        );
    });

    test("initializes the root for turbo frame loads", async () => {
        await Promise.resolve();
        const frame = document.createElement("turbo-frame");
        document.body.appendChild(frame);

        frame.dispatchEvent(
            new CustomEvent("turbo:frame-load", {
                bubbles: true,
                detail: {},
            }),
        );

        expect(initGameViewRoot).toHaveBeenLastCalledWith(frame);
    });

    test("ignores turbo frame loads with non-Element targets", async () => {
        await Promise.resolve();
        const callsBefore = initGameViewRoot.mock.calls.length;
        const event = new CustomEvent("turbo:frame-load", {
            bubbles: true,
            detail: {},
        });
        Object.defineProperty(event, "target", { value: "not-an-element" });

        document.dispatchEvent(event);

        expect(initGameViewRoot).toHaveBeenCalledTimes(callsBefore);
    });

    test("dismisses the open image modal before Turbo caches the page", async () => {
        await Promise.resolve();
        const dismiss = document.querySelector(
            '[data-game-view-target="imageModalDismiss"]',
        );
        const click = jest.spyOn(dismiss, "click");

        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(click).toHaveBeenCalledTimes(1);
    });

    test("does not dismiss an image modal that is already closed", async () => {
        await Promise.resolve();
        const modal = document.querySelector(
            '[data-game-view-target="imageModal"]',
        );
        modal.classList.remove("show");
        const dismiss = document.querySelector(
            '[data-game-view-target="imageModalDismiss"]',
        );
        const click = jest.spyOn(dismiss, "click");

        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(click).not.toHaveBeenCalled();
    });
});
