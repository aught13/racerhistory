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

    test("applyRotation with non-finite value defaults to 0", () => {
        const controller = application.controllers.find(
            (c) => c.identifier === "admin-image-manipulate",
        );

        controller.applyRotation(NaN, null);

        expect(document.getElementById("rotate").value).toBe("0");
        expect(document.getElementById("rotate-range").value).toBe("0");
    });

    test("applyRotation with source 'range' skips range update", () => {
        const controller = application.controllers.find(
            (c) => c.identifier === "admin-image-manipulate",
        );

        document.getElementById("rotate-range").value = "100";
        controller.applyRotation(50, "range");

        // Range should stay at 100 because source was 'range'
        expect(document.getElementById("rotate-range").value).toBe("100");
        // Input should be updated to 50
        expect(document.getElementById("rotate").value).toBe("50");
    });

    test("applyRotation with source 'input' skips input update", () => {
        const controller = application.controllers.find(
            (c) => c.identifier === "admin-image-manipulate",
        );

        document.getElementById("rotate").value = "100";
        controller.applyRotation(75, "input");

        // Input should stay at 100 because source was 'input'
        expect(document.getElementById("rotate").value).toBe("100");
        // Range should be updated to 75
        expect(document.getElementById("rotate-range").value).toBe("75");
    });

    test("updateCropInputs updates all crop values", () => {
        const controller = application.controllers.find(
            (c) => c.identifier === "admin-image-manipulate",
        );

        const cropData = { x: 10, y: 20, width: 100, height: 80 };
        controller.updateCropInputs(cropData);

        expect(document.getElementById("crop-x").value).toBe("10");
        expect(document.getElementById("crop-y").value).toBe("20");
        expect(document.getElementById("crop-width").value).toBe("100");
        expect(document.getElementById("crop-height").value).toBe("80");
    });

    test("markActiveRatioButton removes active from others", () => {
        const controller = application.controllers.find(
            (c) => c.identifier === "admin-image-manipulate",
        );

        const button1 = document.getElementById("ratio-free");
        const button2 = document.getElementById("ratio-1");

        button1.classList.add("active");
        button2.classList.add("active");

        controller.markActiveRatioButton(button2);

        expect(button1.classList.contains("active")).toBe(false);
        expect(button2.classList.contains("active")).toBe(true);
    });

    test("markActiveRatioButton with null removes all active", () => {
        const controller = application.controllers.find(
            (c) => c.identifier === "admin-image-manipulate",
        );

        const button1 = document.getElementById("ratio-free");
        const button2 = document.getElementById("ratio-1");

        button1.classList.add("active");
        button2.classList.add("active");

        controller.markActiveRatioButton(null);

        expect(button1.classList.contains("active")).toBe(false);
        expect(button2.classList.contains("active")).toBe(false);
    });

    test("sync rotation from range input", () => {
        const rangeInput = document.getElementById("rotate-range");
        rangeInput.value = "60";
        rangeInput.dispatchEvent(new Event("input", { bubbles: true }));

        expect(document.getElementById("rotate").value).toBe("60");
    });

    test("sync rotation from numeric input", () => {
        const numInput = document.getElementById("rotate");
        numInput.value = "30";
        numInput.dispatchEvent(new Event("input", { bubbles: true }));

        expect(document.getElementById("rotate-range").value).toBe("30");
    });
});
