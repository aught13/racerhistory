/* global HTMLCanvasElement, afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import AdminImageCropThumbController from "../controllers/admin_image_crop_thumb_controller.js";

const createCanvasContext = () => ({
    clearRect: jest.fn(),
    drawImage: jest.fn(),
});

const flush = () => Promise.resolve().then(() => Promise.resolve());

describe("admin-image-crop-thumb controller", () => {
    let application;
    let originalGetContext;

    function renderFixture({
        includePreviewCanvas = true,
        includeCropFields = true,
        imageWidth = 300,
        imageHeight = 300,
        canvasContext = createCanvasContext(),
    } = {}) {
        if (!originalGetContext) {
            originalGetContext = HTMLCanvasElement.prototype.getContext;
        }
        HTMLCanvasElement.prototype.getContext = jest.fn(() => canvasContext);

        const cropFields = includeCropFields
            ? `
                <input id="crop_x" data-admin-image-crop-thumb-target="cropX" />
                <input id="crop_y" data-admin-image-crop-thumb-target="cropY" />
                <input id="crop_width" data-admin-image-crop-thumb-target="cropWidth" />
                <input id="crop_height" data-admin-image-crop-thumb-target="cropHeight" />
            `
            : "";

        const previewCanvas = includePreviewCanvas
            ? '<canvas id="preview-canvas" width="150" height="150" data-admin-image-crop-thumb-target="previewCanvas"></canvas>'
            : "";

        document.body.innerHTML = `
            <div data-controller="admin-image-crop-thumb">
                <div id="crop-container" data-admin-image-crop-thumb-target="container" style="position:relative; width: 300px; height: 300px;">
                    <img id="crop-image" data-admin-image-crop-thumb-target="image" alt="crop" />
                    <div id="crop-overlay" data-admin-image-crop-thumb-target="overlay" style="display:none; position:absolute;">
                        <div class="resize-handle" data-admin-image-crop-thumb-target="resizeHandle"></div>
                    </div>
                </div>
                ${previewCanvas}
                ${cropFields}
                <button id="reset" data-action="click->admin-image-crop-thumb#reset" type="button">Reset</button>
            </div>
        `;

        const image = document.getElementById("crop-image");
        const container = document.getElementById("crop-container");

        Object.defineProperty(image, "complete", {
            configurable: true,
            value: true,
        });
        Object.defineProperty(image, "naturalWidth", {
            configurable: true,
            value: 300,
        });

        image.getBoundingClientRect = () => ({
            width: imageWidth,
            height: imageHeight,
            top: 0,
            left: 0,
            right: imageWidth,
            bottom: imageHeight,
        });
        container.getBoundingClientRect = () => ({
            width: 300,
            height: 300,
            top: 0,
            left: 0,
            right: 300,
            bottom: 300,
        });

        application = Application.start();
        application.register(
            "admin-image-crop-thumb",
            AdminImageCropThumbController,
        );

        return { image, container };
    }

    beforeEach(() => {
        renderFixture();
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        HTMLCanvasElement.prototype.getContext = originalGetContext;
        document.body.innerHTML = "";
    });

    test("initializes crop fields on connect", () => {
        const width = Number(document.getElementById("crop_width").value || 0);
        const height = Number(
            document.getElementById("crop_height").value || 0,
        );

        expect(width).toBeGreaterThan(0);
        expect(height).toBeGreaterThan(0);
        expect(document.getElementById("crop-overlay").style.display).toBe(
            "block",
        );
    });

    test("reset action re-applies a valid crop", () => {
        document.getElementById("reset").click();

        const width = Number(document.getElementById("crop_width").value || 0);
        const height = Number(
            document.getElementById("crop_height").value || 0,
        );
        expect(width).toBeGreaterThan(0);
        expect(height).toBeGreaterThan(0);
    });

    test("returns early when required targets are missing", () => {
        application.stop();
        application = null;

        renderFixture({ includePreviewCanvas: false });

        expect(document.getElementById("crop_width")).not.toBeNull();
        expect(document.getElementById("crop_width").value).toBe("");
        expect(document.getElementById("crop-overlay").style.display).toBe(
            "none",
        );
    });

    test("does not initialize crop when the image has no measured size", () => {
        application.stop();
        application = null;

        renderFixture({ imageWidth: 0, imageHeight: 0 });

        expect(document.getElementById("crop_width").value).toBe("");
        expect(document.getElementById("crop-overlay").style.display).toBe(
            "none",
        );
    });

    test("handles drag, resize, detached move, and idle mousemove paths", () => {
        const container = document.getElementById("crop-container");
        const resizeHandle = document.querySelector(".resize-handle");
        const overlay = document.getElementById("crop-overlay");

        document.dispatchEvent(
            new window.MouseEvent("mousemove", {
                bubbles: true,
                clientX: 5,
                clientY: 5,
            }),
        );

        resizeHandle.dispatchEvent(
            new window.MouseEvent("mousedown", {
                bubbles: true,
                clientX: 280,
                clientY: 280,
            }),
        );
        document.dispatchEvent(
            new window.MouseEvent("mousemove", {
                bubbles: true,
                clientX: 10,
                clientY: 10,
            }),
        );

        expect(overlay.style.width).toBe("20px");
        expect(overlay.style.height).toBe("20px");

        document.dispatchEvent(
            new window.MouseEvent("mouseup", { bubbles: true }),
        );

        container.dispatchEvent(
            new window.MouseEvent("mousedown", {
                bubbles: true,
                clientX: 10,
                clientY: 10,
            }),
        );
        document.dispatchEvent(
            new window.MouseEvent("mousemove", {
                bubbles: true,
                clientX: 60,
                clientY: 70,
            }),
        );

        expect(overlay.style.left).toBe("50px");
        expect(overlay.style.top).toBe("60px");

        document.dispatchEvent(
            new window.MouseEvent("mouseup", { bubbles: true }),
        );

        container.dispatchEvent(
            new window.MouseEvent("mousedown", {
                bubbles: true,
                clientX: 100,
                clientY: 100,
            }),
        );
        const containsSpy = jest
            .spyOn(document.body, "contains")
            .mockReturnValue(false);
        document.dispatchEvent(
            new window.MouseEvent("mousemove", {
                bubbles: true,
                clientX: 120,
                clientY: 130,
            }),
        );
        containsSpy.mockRestore();

        expect(overlay.style.left).toBe("50px");
        expect(overlay.style.top).toBe("60px");
    });

    test("skips preview drawing when the canvas context is unavailable or the image is incomplete", async () => {
        application.stop();
        application = null;

        renderFixture({ canvasContext: null });
        await flush();

        expect(document.getElementById("crop_width").value).toBe("300");
        expect(document.getElementById("crop-overlay").style.display).toBe(
            "block",
        );

        application.stop();
        application = null;

        const { image } = renderFixture();
        await flush();
        Object.defineProperty(image, "complete", {
            configurable: true,
            value: false,
        });

        document.getElementById("reset").click();

        expect(document.getElementById("crop-overlay").style.display).toBe(
            "block",
        );
    });

    test("omits optional crop fields when they are not present", async () => {
        application.stop();
        application = null;

        renderFixture({ includeCropFields: false });
        await flush();

        expect(document.querySelector("#crop_x")).toBeNull();
        expect(document.querySelector("#crop-overlay")).not.toBeNull();
        expect(document.getElementById("crop-overlay").style.display).toBe(
            "block",
        );
    });
});
