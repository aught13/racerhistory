/* global HTMLCanvasElement, afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import AdminImageCropThumbController from "../controllers/admin_image_crop_thumb_controller.js";

describe("admin-image-crop-thumb controller", () => {
    let application;
    let originalGetContext;

    beforeEach(() => {
        originalGetContext = HTMLCanvasElement.prototype.getContext;
        HTMLCanvasElement.prototype.getContext = jest.fn(() => ({
            clearRect: jest.fn(),
            drawImage: jest.fn(),
        }));

        document.body.innerHTML = `
            <div data-controller="admin-image-crop-thumb">
                <div id="crop-container" data-admin-image-crop-thumb-target="container" style="position:relative; width: 300px; height: 300px;">
                    <img id="crop-image" data-admin-image-crop-thumb-target="image" alt="crop" />
                    <div id="crop-overlay" data-admin-image-crop-thumb-target="overlay" style="display:none; position:absolute;">
                        <div class="resize-handle" data-admin-image-crop-thumb-target="resizeHandle"></div>
                    </div>
                </div>
                <canvas id="preview-canvas" width="150" height="150" data-admin-image-crop-thumb-target="previewCanvas"></canvas>
                <input id="crop_x" data-admin-image-crop-thumb-target="cropX" />
                <input id="crop_y" data-admin-image-crop-thumb-target="cropY" />
                <input id="crop_width" data-admin-image-crop-thumb-target="cropWidth" />
                <input id="crop_height" data-admin-image-crop-thumb-target="cropHeight" />
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
            width: 300,
            height: 300,
            top: 0,
            left: 0,
            right: 300,
            bottom: 300,
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
});
