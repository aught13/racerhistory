/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import ImageUploadController from "../controllers/image_upload_controller.js";

describe("image-upload controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <div data-controller="image-upload">
                <form id="uploadForm" action="/admin/images/upload" data-image-upload-target="form">
                    <input id="imageFile" type="file" data-image-upload-target="fileInput" />
                    <input id="tags" data-image-upload-target="tags" />
                    <input id="brightness" type="range" value="0" data-image-upload-target="brightness" />
                    <span id="brightnessBadge" data-image-upload-target="brightnessBadge">0</span>
                    <input id="contrast" type="range" value="0" data-image-upload-target="contrast" />
                    <span id="contrastBadge" data-image-upload-target="contrastBadge">0</span>
                    <input id="rotate" type="hidden" value="0" data-image-upload-target="rotate" />
                    <button type="submit"><span id="uploadBtn" data-image-upload-target="submitLabel">Upload Image</span><span id="uploadSpinner" class="d-none" data-image-upload-target="submitSpinner"></span></button>
                </form>
                <div id="previewContainer" class="d-none" data-image-upload-target="previewContainer">
                    <img id="previewImage" data-image-upload-target="previewImage" alt="Preview" />
                </div>
                <div id="manipulationControls" class="d-none" data-image-upload-target="manipulationControls"></div>
                <p id="noFileText" data-image-upload-target="noFileText">Select an image above to see adjustment options</p>
                <div id="uploadStatus" class="d-none" data-image-upload-target="status"></div>
            </div>
        `;

        window.FileReader = class FileReaderMock {
            readAsDataURL() {
                if (typeof this.onload === "function") {
                    this.onload({ target: { result: "data:image/png;base64,abc" } });
                }
            }
        };

        globalThis.fetch = jest.fn().mockResolvedValue({
            json: async () => ({
                success: true,
                image: { id: 77 },
            }),
        });

        application = Application.start();
        application.register("image-upload", ImageUploadController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        globalThis.fetch = undefined;
        delete window.FileReader;
        jest.restoreAllMocks();
        jest.useRealTimers();
    });

    test("shows preview and badges when a file is selected", () => {
        const fileInput = document.getElementById("imageFile");
        const brightness = document.getElementById("brightness");
        const contrast = document.getElementById("contrast");

        const file = new window.File(["abc"], "photo.png", { type: "image/png" });
        Object.defineProperty(fileInput, "files", {
            value: [file],
            configurable: true,
        });

        fileInput.dispatchEvent(new Event("change", { bubbles: true }));
        brightness.value = "25";
        brightness.dispatchEvent(new Event("input", { bubbles: true }));
        contrast.value = "15";
        contrast.dispatchEvent(new Event("input", { bubbles: true }));

        expect(document.getElementById("previewContainer").classList.contains("d-none")).toBe(false);
        expect(document.getElementById("manipulationControls").classList.contains("d-none")).toBe(false);
        expect(document.getElementById("noFileText").classList.contains("d-none")).toBe(true);
        expect(document.getElementById("previewImage").src).toContain("data:image/png;base64,abc");
        expect(document.getElementById("brightnessBadge").textContent).toBe("25");
        expect(document.getElementById("contrastBadge").textContent).toBe("15");
    });

    test("submits upload data and redirects on success", async () => {
        jest.useFakeTimers();

        const fileInput = document.getElementById("imageFile");
        const tags = document.getElementById("tags");
        const form = document.getElementById("uploadForm");
        const rotate = document.getElementById("rotate");
        const brightness = document.getElementById("brightness");

        const file = new window.File(["abc"], "photo.png", { type: "image/png" });
        Object.defineProperty(fileInput, "files", {
            value: [file],
            configurable: true,
        });

        fileInput.dispatchEvent(new Event("change", { bubbles: true }));
        tags.value = "person-1, teamseason-2";
        rotate.value = "90";
        brightness.value = "10";

        form.dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));

        await Promise.resolve();
        await Promise.resolve();

        expect(globalThis.fetch).toHaveBeenCalledTimes(1);
        const [url, options] = globalThis.fetch.mock.calls[0];
        expect(url).toBe("/admin/images/upload");
        expect(options.method).toBe("POST");
        expect(options.body.get("upload")).toBe(file);
        expect(options.body.get("tags")).toBe("person-1, teamseason-2");
        expect(options.body.get("rotate")).toBe("90");
        expect(options.body.get("brightness")).toBe("10");

        expect(document.getElementById("uploadStatus").textContent).toContain("Image uploaded successfully");
    });
});
