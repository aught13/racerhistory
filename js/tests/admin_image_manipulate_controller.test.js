/* global HTMLCanvasElement, afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import AdminImageManipulateController from "../controllers/admin_image_manipulate_controller.js";

describe("admin-image-manipulate controller", () => {
    let application;
    let originalGetContext;

    beforeEach(() => {
        originalGetContext = HTMLCanvasElement.prototype.getContext;
        const context = new Proxy(
            {},
            {
                get(target, prop) {
                    if (!(prop in target)) {
                        target[prop] = jest.fn();
                    }
                    return target[prop];
                },
                set(target, prop, value) {
                    target[prop] = value;
                    return true;
                },
            },
        );
        HTMLCanvasElement.prototype.getContext = jest.fn(() => context);

        document.body.innerHTML = `
            <div data-controller="admin-image-manipulate">
                <img id="sourceImage" data-admin-image-manipulate-target="image" alt="source" />
                <canvas id="previewCanvas" data-admin-image-manipulate-target="canvas"></canvas>

                <button id="ratio-free" type="button"
                    data-admin-image-manipulate-target="aspectButton ratioFree"
                    data-action="click->admin-image-manipulate#setAspectRatio"
                    data-admin-image-manipulate-ratio-param="free">Free</button>
                <button id="ratio-1" type="button"
                    data-admin-image-manipulate-target="aspectButton"
                    data-action="click->admin-image-manipulate#setAspectRatio"
                    data-admin-image-manipulate-ratio-param="1">1:1</button>

                <button id="rot-90" type="button"
                    data-action="click->admin-image-manipulate#setRotation"
                    data-admin-image-manipulate-degrees-param="90">90</button>

                <input id="rotate-range" type="range" value="0"
                    data-admin-image-manipulate-target="rotateRange"
                    data-action="input->admin-image-manipulate#syncRotateRange" />
                <input id="rotate" type="number" value="0"
                    data-admin-image-manipulate-target="rotateInput"
                    data-action="input->admin-image-manipulate#syncRotateInput" />

                <input id="crop-x" data-admin-image-manipulate-target="cropX" />
                <input id="crop-y" data-admin-image-manipulate-target="cropY" />
                <input id="crop-width" data-admin-image-manipulate-target="cropWidth" />
                <input id="crop-height" data-admin-image-manipulate-target="cropHeight" />

                <button id="reset" type="button" data-action="click->admin-image-manipulate#reset">Reset</button>
            </div>
        `;

        const image = document.getElementById("sourceImage");
        const canvas = document.getElementById("previewCanvas");

        Object.defineProperty(image, "complete", {
            configurable: true,
            value: true,
        });
        Object.defineProperty(image, "naturalWidth", {
            configurable: true,
            value: 400,
        });
        Object.defineProperty(image, "naturalHeight", {
            configurable: true,
            value: 300,
        });

        canvas.getBoundingClientRect = () => ({
            width: 400,
            height: 300,
            top: 0,
            left: 0,
            right: 400,
            bottom: 300,
        });

        application = Application.start();
        application.register(
            "admin-image-manipulate",
            AdminImageManipulateController,
        );
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        HTMLCanvasElement.prototype.getContext = originalGetContext;
        document.body.innerHTML = "";
    });

    test("quick rotate button syncs rotation controls", () => {
        document.getElementById("rot-90").click();

        expect(document.getElementById("rotate").value).toBe("90");
        expect(document.getElementById("rotate-range").value).toBe("90");
    });

    test("aspect ratio buttons toggle active class", () => {
        const free = document.getElementById("ratio-free");
        const ratioOne = document.getElementById("ratio-1");

        ratioOne.click();
        expect(ratioOne.classList.contains("active")).toBe(true);
        expect(free.classList.contains("active")).toBe(false);

        free.click();
        expect(free.classList.contains("active")).toBe(true);
    });

    test("reset clears rotation values", () => {
        document.getElementById("rot-90").click();
        document.getElementById("reset").click();

        expect(document.getElementById("rotate").value).toBe("0");
        expect(document.getElementById("rotate-range").value).toBe("0");
        expect(
            document.getElementById("ratio-free").classList.contains("active"),
        ).toBe(true);
    });
});
