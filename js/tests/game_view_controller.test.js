/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

jest.mock("../lib/game_view_runtime.js", () => ({
    initGameViewRoot: jest.fn(),
}));

jest.mock("../lib/public_vite_datatables.mjs", () => ({
    publicDataTablesReady: {
        then(callback) {
            const readiness = globalThis.__gameViewDataTablesReady;
            if (readiness.ready) {
                Promise.resolve().then(callback);
            } else {
                readiness.callbacks.push(callback);
            }
        },
    },
}));

import { initGameViewRoot } from "../lib/game_view_runtime.js";
import GameViewController from "../controllers/game_view_controller.js";

function resolveDataTablesReady() {
    const readiness = globalThis.__gameViewDataTablesReady;
    readiness.ready = true;
    readiness.callbacks.splice(0).forEach((callback) => {
        Promise.resolve().then(callback);
    });
}

describe("game-view controller", () => {
    let application;

    beforeEach(() => {
        jest.clearAllMocks();
        globalThis.__gameViewDataTablesReady = {
            ready: false,
            callbacks: [],
        };
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
        delete globalThis.__gameViewDataTablesReady;
        jest.restoreAllMocks();
    });

    test("waits for DataTables before initializing the controller root", async () => {
        await Promise.resolve();

        const root = document.getElementById("game-view-root");
        expect(initGameViewRoot).not.toHaveBeenCalled();
        expect(globalThis.__gameViewDataTablesReady.callbacks).toHaveLength(1);

        resolveDataTablesReady();
        await Promise.resolve();

        expect(initGameViewRoot).toHaveBeenCalledWith(root);
    });

    test("initializes turbo frame content after DataTables is ready", async () => {
        await Promise.resolve();
        resolveDataTablesReady();
        await Promise.resolve();

        initGameViewRoot.mockClear();
        const frame = document.createElement("turbo-frame");
        document.body.appendChild(frame);

        frame.dispatchEvent(
            new CustomEvent("turbo:frame-load", {
                bubbles: true,
                detail: {},
            }),
        );
        await Promise.resolve();

        expect(initGameViewRoot).toHaveBeenLastCalledWith(frame);
    });

    test("does not initialize a frame removed before DataTables is ready", async () => {
        await Promise.resolve();
        const root = document.getElementById("game-view-root");
        initGameViewRoot.mockClear();

        const frame = document.createElement("turbo-frame");
        document.body.appendChild(frame);
        frame.dispatchEvent(
            new CustomEvent("turbo:frame-load", {
                bubbles: true,
                detail: {},
            }),
        );
        frame.remove();

        resolveDataTablesReady();
        await Promise.resolve();

        expect(initGameViewRoot).toHaveBeenCalledWith(root);
        expect(initGameViewRoot).not.toHaveBeenCalledWith(frame);
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
