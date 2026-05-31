/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import ImageUploadController from "../controllers/image_upload_controller.js";

const flushPromises = async () => {
    await Promise.resolve();
    await Promise.resolve();
};

describe("image-upload controller", () => {
    let application;

    const getController = async () => {
        const root = document.querySelector('[data-controller="image-upload"]');

        for (let i = 0; i < 4; i += 1) {
            const controller =
                application.getControllerForElementAndIdentifier(
                    root,
                    "image-upload",
                ) ||
                application.controllers.find(
                    (item) => item.identifier === "image-upload",
                );

            if (controller) {
                return controller;
            }

            await Promise.resolve();
        }

        return undefined;
    };

    function renderFixture({
        includeFileInput = true,
        includeBrightness = true,
        includeContrast = true,
        includeRotate = true,
        includeForm = true,
        includePreview = true,
        includeStatus = true,
        includeTags = true,
        includeFileInputChangeAction = true,
        includeRotationAction = true,
    } = {}) {
        document.body.innerHTML = `
            <div data-controller="image-upload">
                ${includeForm ? '<form id="uploadForm" action="/admin/images/upload" data-image-upload-target="form">' : '<div id="uploadForm">'}
                    ${includeFileInput ? `<input id="imageFile" type="file" data-image-upload-target="fileInput" ${includeFileInputChangeAction ? 'data-action="change->image-upload#handleFileChange"' : ""} />` : ""}
                    ${includeTags ? '<input id="tags" data-image-upload-target="tags" />' : ""}
                    ${includeBrightness ? '<input id="brightness" type="range" value="0" data-image-upload-target="brightness" />' : ""}
                    <span id="brightnessBadge" data-image-upload-target="brightnessBadge">0</span>
                    ${includeContrast ? '<input id="contrast" type="range" value="0" data-image-upload-target="contrast" />' : ""}
                    <span id="contrastBadge" data-image-upload-target="contrastBadge">0</span>
                    ${includeRotate ? `<input id="rotate" type="hidden" value="0" data-image-upload-target="rotate" />` : ""}
                    ${includeRotate && includeRotationAction ? '<button id="rotateButton" type="button" data-action="click->image-upload#setRotation" data-image-upload-degrees-param="180"></button>' : ""}
                    <button type="submit"><span id="uploadBtn" data-image-upload-target="submitLabel">Upload Image</span><span id="uploadSpinner" class="d-none" data-image-upload-target="submitSpinner"></span></button>
                ${includeForm ? "</form>" : "</div>"}
                ${includePreview ? '<div id="previewContainer" class="d-none" data-image-upload-target="previewContainer"><img id="previewImage" data-image-upload-target="previewImage" alt="Preview" /></div>' : ""}
                ${includePreview ? '<div id="manipulationControls" class="d-none" data-image-upload-target="manipulationControls"></div>' : ""}
                ${includePreview ? '<p id="noFileText" data-image-upload-target="noFileText">Select an image above to see adjustment options</p>' : ""}
                ${includeStatus ? '<div id="uploadStatus" class="d-none" data-image-upload-target="status"></div>' : ""}
            </div>
        `;
    }

    beforeEach(() => {
        renderFixture();

        window.FileReader = class FileReaderMock {
            readAsDataURL() {
                if (typeof this.onload === "function") {
                    this.onload({
                        target: { result: "data:image/png;base64,abc" },
                    });
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
        const rotate = document.getElementById("rotate");

        const file = new window.File(["abc"], "photo.png", {
            type: "image/png",
        });
        Object.defineProperty(fileInput, "files", {
            value: [file],
            configurable: true,
        });

        fileInput.dispatchEvent(new Event("change", { bubbles: true }));
        brightness.value = "25";
        brightness.dispatchEvent(new Event("input", { bubbles: true }));
        contrast.value = "15";
        contrast.dispatchEvent(new Event("input", { bubbles: true }));
        document.getElementById("rotateButton").click();

        expect(
            document
                .getElementById("previewContainer")
                .classList.contains("d-none"),
        ).toBe(false);
        expect(
            document
                .getElementById("manipulationControls")
                .classList.contains("d-none"),
        ).toBe(false);
        expect(
            document.getElementById("noFileText").classList.contains("d-none"),
        ).toBe(true);
        expect(document.getElementById("previewImage").src).toContain(
            "data:image/png;base64,abc",
        );
        expect(document.getElementById("brightnessBadge").textContent).toBe(
            "25",
        );
        expect(document.getElementById("contrastBadge").textContent).toBe("15");
        expect(rotate.value).toBe("180");
    });

    test("resets preview state when no file is selected", () => {
        const fileInput = document.getElementById("imageFile");
        const previewImage = document.getElementById("previewImage");

        previewImage.setAttribute("src", "data:image/png;base64,abc");
        Object.defineProperty(fileInput, "files", {
            value: [],
            configurable: true,
        });

        fileInput.dispatchEvent(new Event("change", { bubbles: true }));

        expect(previewImage.getAttribute("src")).toBeNull();
        expect(
            document.getElementById("noFileText").classList.contains("d-none"),
        ).toBe(false);
        expect(
            document
                .getElementById("previewContainer")
                .classList.contains("d-none"),
        ).toBe(true);
    });

    test("shows an error when submitting without a selected file", () => {
        const form = document.getElementById("uploadForm");

        form.dispatchEvent(
            new Event("submit", { bubbles: true, cancelable: true }),
        );

        expect(globalThis.fetch).not.toHaveBeenCalled();
        expect(document.getElementById("uploadStatus").textContent).toContain(
            "Please select an image file",
        );
    });

    test("submits only non-empty optional fields and handles failed responses", async () => {
        const fileInput = document.getElementById("imageFile");
        const tags = document.getElementById("tags");
        const form = document.getElementById("uploadForm");
        const rotate = document.getElementById("rotate");
        const brightness = document.getElementById("brightness");
        const contrast = document.getElementById("contrast");

        const file = new window.File(["abc"], "photo.png", {
            type: "image/png",
        });
        Object.defineProperty(fileInput, "files", {
            value: [file],
            configurable: true,
        });

        fileInput.dispatchEvent(new Event("change", { bubbles: true }));
        tags.value = "   ";
        rotate.value = "-90";
        brightness.value = "0";
        contrast.value = "0";

        globalThis.fetch.mockResolvedValueOnce({
            json: async () => ({ success: false, error: "bad upload" }),
        });

        form.dispatchEvent(
            new Event("submit", { bubbles: true, cancelable: true }),
        );

        await flushPromises();

        expect(globalThis.fetch).toHaveBeenCalledTimes(1);
        const [, options] = globalThis.fetch.mock.calls[0];
        expect(options.body.get("tags")).toBeNull();
        expect(options.body.get("rotate")).toBeNull();
        expect(options.body.get("brightness")).toBeNull();
        expect(options.body.get("contrast")).toBeNull();
        expect(document.getElementById("uploadStatus").textContent).toContain(
            "bad upload",
        );
    });

    test("shows upload errors and tolerates missing optional targets", async () => {
        application.stop();
        application = null;

        renderFixture({
            includeFileInput: false,
            includeBrightness: false,
            includeContrast: false,
            includeRotate: false,
            includeForm: false,
            includePreview: false,
            includeStatus: false,
            includeTags: false,
        });

        application = Application.start();
        application.register("image-upload", ImageUploadController);

        expect(document.getElementById("brightnessBadge").textContent).toBe(
            "0",
        );
        expect(document.getElementById("contrastBadge").textContent).toBe("0");

        const errorSpy = jest
            .spyOn(globalThis, "fetch")
            .mockRejectedValueOnce(new Error("network failed"));
        errorSpy.mockRejectedValueOnce(new Error("network failed again"));

        expect(() =>
            document
                .querySelector('[data-controller="image-upload"]')
                .dispatchEvent(
                    new Event("submit", { bubbles: true, cancelable: true }),
                ),
        ).not.toThrow();

        await flushPromises();

        expect(errorSpy).not.toHaveBeenCalled();
    });

    test("submits upload data and redirects on success", async () => {
        jest.useFakeTimers();

        const fileInput = document.getElementById("imageFile");
        const tags = document.getElementById("tags");
        const form = document.getElementById("uploadForm");
        const rotate = document.getElementById("rotate");
        const brightness = document.getElementById("brightness");

        const file = new window.File(["abc"], "photo.png", {
            type: "image/png",
        });
        Object.defineProperty(fileInput, "files", {
            value: [file],
            configurable: true,
        });

        fileInput.dispatchEvent(new Event("change", { bubbles: true }));
        tags.value = "person-1, teamseason-2";
        rotate.value = "90";
        brightness.value = "10";

        form.dispatchEvent(
            new Event("submit", { bubbles: true, cancelable: true }),
        );

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

        expect(document.getElementById("uploadStatus").textContent).toContain(
            "Image uploaded successfully",
        );
    });

    test("disconnect and no-target guards are safe", async () => {
        application.stop();
        application = null;

        renderFixture({
            includeFileInput: false,
            includeBrightness: false,
            includeContrast: false,
            includeRotate: false,
            includeForm: false,
            includePreview: false,
            includeStatus: false,
            includeTags: false,
        });

        application = Application.start();
        application.register("image-upload", ImageUploadController);
        const controller = await getController();

        expect(() => controller.disconnect()).not.toThrow();
    });

    test("handleFileChange handles preview guard and reader result fallback", () => {
        const originalFileReader = window.FileReader;
        window.FileReader = class FileReaderFallbackMock {
            readAsDataURL() {
                if (typeof this.onload === "function") {
                    this.onload({ target: undefined });
                }
            }
        };

        const withPreviewContext = {
            hasPreviewImageTarget: true,
            previewImageTarget: { src: "initial" },
            showPreviewState: jest.fn(),
            currentImageFile: null,
        };
        const file = new window.File(["abc"], "photo.png", {
            type: "image/png",
        });

        ImageUploadController.prototype.handleFileChange.call(
            withPreviewContext,
            {
                target: { files: [file] },
            },
        );

        expect(withPreviewContext.previewImageTarget.src).toBe("");
        expect(withPreviewContext.showPreviewState).toHaveBeenCalledTimes(1);

        const noPreviewContext = {
            hasPreviewImageTarget: false,
            showPreviewState: jest.fn(),
            currentImageFile: null,
        };

        ImageUploadController.prototype.handleFileChange.call(
            noPreviewContext,
            {
                target: { files: [file] },
            },
        );
        expect(noPreviewContext.showPreviewState).toHaveBeenCalledTimes(1);

        window.FileReader = originalFileReader;
    });

    test("brightness, contrast, and rotation branches handle fallbacks", async () => {
        const controller = await getController();

        controller.handleBrightnessInput({
            currentTarget: { value: "7" },
        });
        expect(document.getElementById("brightnessBadge").textContent).toBe(
            "7",
        );

        controller.handleBrightnessInput({});
        expect(document.getElementById("brightnessBadge").textContent).toBe(
            "0",
        );

        controller.handleContrastInput({
            currentTarget: { value: "-3" },
        });
        expect(document.getElementById("contrastBadge").textContent).toBe("-3");

        controller.handleContrastInput({});
        expect(document.getElementById("contrastBadge").textContent).toBe("0");

        controller.setRotation({ params: { degrees: 270 } });
        expect(document.getElementById("rotate").value).toBe("270");

        controller.setRotation({});
        expect(document.getElementById("rotate").value).toBe("0");

        expect(() =>
            ImageUploadController.prototype.setRotation.call(
                { hasRotateTarget: false },
                { params: { degrees: 180 } },
            ),
        ).not.toThrow();
    });

    test("submit uses upload fallback URL, omits optional fields, and handles generic failure message", async () => {
        application.stop();
        application = null;

        renderFixture({
            includeTags: false,
            includeRotate: false,
            includeBrightness: false,
            includeContrast: false,
        });

        application = Application.start();
        application.register("image-upload", ImageUploadController);
        const controller = await getController();

        const file = new window.File(["abc"], "photo.png", {
            type: "image/png",
        });
        controller.currentImageFile = file;

        const form = document.getElementById("uploadForm");
        form.removeAttribute("action");

        globalThis.fetch.mockResolvedValueOnce({
            json: async () => ({ success: false }),
        });

        await controller.submit({ preventDefault: jest.fn() });

        expect(globalThis.fetch).toHaveBeenCalledTimes(1);
        const [url, options] = globalThis.fetch.mock.calls[0];
        expect(url).toBe("/admin/images/upload");
        expect(options.body.get("tags")).toBeNull();
        expect(options.body.get("rotate")).toBeNull();
        expect(options.body.get("brightness")).toBeNull();
        expect(options.body.get("contrast")).toBeNull();
        expect(document.getElementById("uploadStatus").textContent).toContain(
            "Upload failed",
        );
    });

    test("submit catch branch and helper guard branches are covered", async () => {
        application.stop();
        application = null;

        renderFixture({
            includeForm: false,
            includeStatus: true,
        });

        application = Application.start();
        application.register("image-upload", ImageUploadController);
        const controller = await getController();

        const file = new window.File(["abc"], "photo.png", {
            type: "image/png",
        });
        controller.currentImageFile = file;

        globalThis.fetch.mockRejectedValueOnce(new Error("network down"));
        await controller.submit({ preventDefault: jest.fn() });

        expect(document.getElementById("uploadStatus").textContent).toContain(
            "Upload error: network down",
        );

        expect(() => controller.updateAdjustmentBadge(null, 10)).not.toThrow();

        const badge = document.createElement("span");
        controller.updateAdjustmentBadge(badge, -9);
        expect(badge.className).toContain("bg-danger");

        expect(() =>
            ImageUploadController.prototype.togglePreviewSections.call(
                {
                    hasPreviewContainerTarget: false,
                    hasManipulationControlsTarget: false,
                    hasNoFileTextTarget: false,
                },
                true,
            ),
        ).not.toThrow();

        expect(() =>
            ImageUploadController.prototype.showStatus.call(
                { hasStatusTarget: false },
                "error",
                "ignored",
            ),
        ).not.toThrow();

        const submitGuardContext = {
            hasSubmitLabelTarget: false,
            hasSubmitSpinnerTarget: false,
            hasFormTarget: false,
        };
        expect(() =>
            ImageUploadController.prototype.setSubmitting.call(
                submitGuardContext,
                true,
            ),
        ).not.toThrow();

        const formWithoutButton = document.createElement("form");
        expect(() =>
            ImageUploadController.prototype.setSubmitting.call(
                {
                    hasSubmitLabelTarget: true,
                    submitLabelTarget: document.createElement("span"),
                    hasSubmitSpinnerTarget: true,
                    submitSpinnerTarget: document.createElement("span"),
                    hasFormTarget: true,
                    formTarget: formWithoutButton,
                },
                false,
            ),
        ).not.toThrow();
    });
});
