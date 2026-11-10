/* global Blob, File, MouseEvent, afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import ImageSelectorController from "../controllers/image_selector_controller.js";

const flush = async () => {
    await Promise.resolve();
    await Promise.resolve();
};

const getController = (application) => {
    const controller = application.controllers.find(
        (controller) => controller.identifier === "image-selector",
    );
    if (controller && !controller.targetForm) {
        controller.targetForm = document.getElementById("image-owner-form");
        controller.refreshConfigAndTargetField();
    }

    return controller;
};

describe("image-selector controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <form id="image-owner-form">
                <input id="image-target" value="2" />
                <button id="image-modal-trigger" type="button" data-bs-toggle="modal" data-bs-target="#image-modal">
                    Open selector
                </button>
            </form>
            <div
                id="image-modal"
                data-controller="image-selector"
                data-image-selector-target-field-id-value="image-target"
                data-image-selector-tag-filter-value="featured"
                data-image-selector-upload-context-value='{"entity":"person"}'
                data-image-selector-aspect-ratio-value="1.5"
                data-image-selector-csrf-token-value="csrf-token"
                class="modal fade"
            >
                <button id="image-modal-select-tab" data-image-selector-target="selectTab" type="button">Select</button>
                <button id="image-modal-upload-tab" data-image-selector-target="uploadTab" type="button">Upload</button>
                <input id="image-modal-search" data-image-selector-target="searchInput" type="text" />
                <div id="image-modal-gallery" data-image-selector-target="gallery" class="row"></div>
                <input id="image-modal-file-input" data-image-selector-target="fileInput" type="file" />
                <div id="image-modal-crop-container" data-image-selector-target="cropContainer" class="d-none">
                    <img id="image-modal-crop-image" data-image-selector-target="cropImage" alt="crop" />
                    <div id="image-modal-crop-preview" data-image-selector-target="cropPreview" class="d-none"></div>
                </div>
                <div id="image-modal-no-preview" data-image-selector-target="noPreview">No preview</div>
                <div id="image-modal-crop-controls" data-image-selector-target="cropControls" class="d-none"></div>
                <form id="image-modal-tag-form" data-image-selector-target="tagForm">
                    <input name="tags" value="featured" />
                </form>
                <input id="image-modal-skip-crop" data-image-selector-target="skipCropToggle" type="checkbox" />
                <button id="image-modal-select-btn" data-image-selector-target="selectButton" class="d-none" type="button">Select image</button>
                <button id="image-modal-upload-btn" data-image-selector-target="uploadButton" class="d-none" type="button">Upload &amp; Crop</button>
                <button id="image-modal-rotate-left" data-image-selector-target="rotateLeftButton" type="button">Rotate left</button>
                <button id="image-modal-rotate-right" data-image-selector-target="rotateRightButton" type="button">Rotate right</button>
                <button id="image-modal-reset-crop" data-image-selector-target="resetCropButton" type="button">Reset crop</button>
                <button id="image-modal-dismiss" data-image-selector-target="dismissButton" data-bs-dismiss="modal" type="button">Close</button>
            </div>
        `;

        window.bootstrap = {
            Modal: {
                getInstance: jest.fn(),
                getOrCreateInstance: jest.fn(),
            },
        };

        window.Cropper = jest.fn(() => ({
            destroy: jest.fn(),
            rotate: jest.fn(),
            reset: jest.fn(),
            getCroppedCanvas: jest.fn(() => ({
                toBlob: jest.fn((callback) =>
                    callback(new Blob(["cropped"], { type: "image/jpeg" })),
                ),
            })),
        }));

        window.FileReader = class FileReaderMock {
            readAsDataURL() {
                if (typeof this.onload === "function") {
                    this.onload({
                        target: { result: "data:image/jpeg;base64,abc" },
                    });
                }
            }
        };

        globalThis.fetch = jest.fn();

        application = Application.start();
        application.register("image-selector", ImageSelectorController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.bootstrap;
        delete window.Cropper;
        delete window.FileReader;
        delete globalThis.fetch;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
        jest.resetModules();
    });

    test("loads images, filters gallery, selects an image, and resets on modal hide", async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({
                images: [
                    {
                        id: 1,
                        url: "/img/1.jpg",
                        thumbnail_url: "/img/1-thumb.jpg",
                        original_name: "first.jpg",
                        tags: ["featured", "hero"],
                    },
                    {
                        id: 2,
                        url: "/img/2.jpg",
                        thumbnail_url: "/img/2-thumb.jpg",
                        original_name: "second.jpg",
                        tags: ["archive"],
                    },
                ],
            }),
        });

        const modal = document.getElementById("image-modal");
        const target = document.getElementById("image-target");

        const showEvent = new Event("show.bs.modal");
        showEvent.relatedTarget = document.getElementById(
            "image-modal-trigger",
        );
        modal.dispatchEvent(showEvent);
        modal.dispatchEvent(new Event("shown.bs.modal"));
        await flush();

        expect(fetch).toHaveBeenCalledWith(
            "/admin/images/browse?tag=featured",
            expect.objectContaining({ credentials: "same-origin" }),
        );
        expect(target.dataset.selectedImageUrl).toBe("/img/2.jpg");

        const searchInput = document.getElementById("image-modal-search");
        searchInput.value = "first";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));

        expect(
            document.getElementById("image-modal-gallery").innerHTML,
        ).toContain("#1");
        expect(
            document.getElementById("image-modal-gallery").innerHTML,
        ).not.toContain("#2");

        document
            .querySelector('[data-image-id="1"]')
            .dispatchEvent(new MouseEvent("click", { bubbles: true }));
        const dismissClick = jest.spyOn(
            document.getElementById("image-modal-dismiss"),
            "click",
        );
        document.getElementById("image-modal-select-btn").click();

        expect(target.value).toBe("1");
        expect(target.dataset.selectedImageThumbnailUrl).toBe(
            "/img/1-thumb.jpg",
        );
        expect(dismissClick).toHaveBeenCalledTimes(1);

        document.querySelector('[data-image-id="1"]').classList.add("border");
        document.getElementById("image-modal-skip-crop").checked = true;
        modal.dispatchEvent(new Event("hidden.bs.modal"));

        expect(
            document
                .getElementById("image-modal-crop-container")
                .classList.contains("d-none"),
        ).toBe(true);
        expect(
            document
                .getElementById("image-modal-no-preview")
                .classList.contains("d-none"),
        ).toBe(false);
        expect(
            document
                .querySelector('[data-image-id="1"]')
                .classList.contains("border"),
        ).toBe(false);
    });

    test("handles file selection, cropper controls, and upload with crop context", async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({
                success: true,
                image: {
                    id: 77,
                    url: "/img/77.jpg",
                    thumbnail_url: "/img/77-thumb.jpg",
                    hero_url: "/img/77-hero.jpg",
                },
            }),
        });

        const showEvent = new Event("show.bs.modal");
        showEvent.relatedTarget = document.getElementById(
            "image-modal-trigger",
        );
        document.getElementById("image-modal").dispatchEvent(showEvent);

        const fileInput = document.getElementById("image-modal-file-input");
        const file = new File(["abc"], "photo.jpg", { type: "image/jpeg" });

        Object.defineProperty(fileInput, "files", {
            configurable: true,
            value: [file],
        });

        fileInput.dispatchEvent(new Event("change", { bubbles: true }));
        await flush();

        expect(window.Cropper).toHaveBeenCalledTimes(1);
        expect(
            document
                .getElementById("image-modal-crop-container")
                .classList.contains("d-none"),
        ).toBe(false);
        expect(
            document
                .getElementById("image-modal-crop-preview")
                .classList.contains("d-none"),
        ).toBe(false);

        const cropperInstance = window.Cropper.mock.results[0].value;
        document.getElementById("image-modal-rotate-left").click();
        document.getElementById("image-modal-rotate-right").click();
        document.getElementById("image-modal-reset-crop").click();

        expect(cropperInstance.rotate).toHaveBeenCalledWith(-90);
        expect(cropperInstance.rotate).toHaveBeenCalledWith(90);
        expect(cropperInstance.reset).toHaveBeenCalledTimes(1);

        document.getElementById("image-modal-upload-btn").click();
        await flush();
        await flush();

        expect(fetch).toHaveBeenCalledWith(
            "/admin/images/upload",
            expect.objectContaining({
                method: "POST",
                credentials: "same-origin",
            }),
        );

        const uploadCall = fetch.mock.calls.at(-1);
        expect(uploadCall[1].headers["X-CSRF-Token"]).toBe("csrf-token");
        expect(uploadCall[1].body.get("upload")).toBeInstanceOf(Blob);
        expect(uploadCall[1].body.get("tags")).toBe("featured");
        expect(uploadCall[1].body.get("context")).toBe(
            JSON.stringify({ entity: "person" }),
        );
        expect(document.getElementById("image-target").value).toBe("77");
        expect(
            document.getElementById("image-modal").classList.contains("show"),
        ).toBe(false);
    });

    test("cleans up lingering modal backdrop after programmatic hide", () => {
        const controller = getController(application);
        const modal = document.getElementById("image-modal");

        delete window.bootstrap;
        modal.classList.add("show");
        document.body.classList.add("modal-open");
        document.body.style.overflow = "hidden";
        document.body.style.paddingRight = "17px";
        const backdrop = document.createElement("div");
        backdrop.className = "modal-backdrop fade show";
        document.body.appendChild(backdrop);

        controller.hideModalWithCleanup();

        expect(modal.classList.contains("show")).toBe(false);
        expect(document.body.classList.contains("modal-open")).toBe(false);
        expect(document.querySelector(".modal-backdrop")).toBeNull();
        expect(document.body.style.overflow).toBe("");
        expect(document.body.style.paddingRight).toBe("");
    });

    test("skips modal artifact cleanup while another modal is visible", () => {
        const controller = getController(application);

        document.body.classList.add("modal-open");
        const backdrop = document.createElement("div");
        backdrop.className = "modal-backdrop fade show";
        document.body.appendChild(backdrop);

        const activeModal = document.createElement("div");
        activeModal.className = "modal show";
        document.body.appendChild(activeModal);

        controller.cleanupModalArtifacts();

        expect(document.body.classList.contains("modal-open")).toBe(true);
        expect(document.querySelector(".modal-backdrop")).toBeTruthy();

        activeModal.remove();
        controller.cleanupModalArtifacts();

        expect(document.body.classList.contains("modal-open")).toBe(false);
        expect(document.querySelector(".modal-backdrop")).toBeNull();
    });

    test("turbo before-cache aborts requests and clears only transient modal state", async () => {
        const controller = getController(application);
        controller.searchInput.value = "new query";
        controller.loadedImages = [{ id: 1, url: "/img/1.jpg" }];
        controller.renderGallery(controller.loadedImages);

        fetch.mockImplementation(
            (_url, { signal }) =>
                new Promise((_resolve, reject) => {
                    signal.addEventListener("abort", () =>
                        reject(new DOMException("Aborted", "AbortError")),
                    );
                }),
        );

        const pending = controller.loadImages();
        const signal = fetch.mock.calls[0][1].signal;
        const target = document.getElementById("image-target");
        target.value = "2";
        document.body.classList.add("modal-open");

        document.dispatchEvent(new Event("turbo:before-cache"));
        await pending;

        expect(signal.aborted).toBe(true);
        expect(controller.searchInput.value).toBe("");
        expect(controller.loadedImages).toEqual([]);
        expect(controller.gallery.childElementCount).toBe(0);
        expect(target.value).toBe("2");
        expect(document.body.classList.contains("modal-open")).toBe(false);
    });

    test("handles guard and failure paths for loading, selecting, and uploading", async () => {
        const alertSpy = jest
            .spyOn(window, "alert")
            .mockImplementation(() => {});
        const errorSpy = jest
            .spyOn(console, "error")
            .mockImplementation(() => {});

        document.getElementById("image-modal-select-btn").click();
        expect(alertSpy).toHaveBeenCalledWith("Please select an image");

        document.getElementById("image-modal-upload-btn").click();
        expect(alertSpy).toHaveBeenCalledWith("Please select an image first");

        fetch
            .mockResolvedValueOnce({ ok: false, json: async () => ({}) })
            .mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: false, error: "bad upload" }),
            });

        document
            .getElementById("image-modal")
            .dispatchEvent(new Event("shown.bs.modal"));
        await flush();
        expect(
            document.getElementById("image-modal-gallery").innerHTML,
        ).toContain("Failed to load images");

        delete window.Cropper;
        const fileInput = document.getElementById("image-modal-file-input");
        const file = new File(["abc"], "photo.jpg", { type: "image/jpeg" });
        Object.defineProperty(fileInput, "files", {
            configurable: true,
            value: [file],
        });
        fileInput.dispatchEvent(new Event("change", { bubbles: true }));
        await flush();

        expect(errorSpy).toHaveBeenCalledWith("Cropper.js is not available.");

        document.getElementById("image-modal-skip-crop").checked = true;
        document.getElementById("image-modal-upload-btn").click();
        await flush();
        await flush();

        expect(alertSpy).toHaveBeenCalledWith("Upload failed: bad upload");

        alertSpy.mockRestore();
        errorSpy.mockRestore();
    });

    test("covers guard branches for missing elements and no-file selection", async () => {
        const controller = getController(application);
        expect(controller).toBeTruthy();

        controller.selectBtn = null;
        controller.uploadBtn = null;
        expect(() => controller.toggleActionButtons(true)).not.toThrow();

        controller.gallery = null;
        await controller.loadImages();
        expect(fetch).not.toHaveBeenCalled();

        const fileInput = document.getElementById("image-modal-file-input");
        Object.defineProperty(fileInput, "files", {
            configurable: true,
            value: [],
        });
        fileInput.dispatchEvent(new Event("change", { bubbles: true }));

        expect(controller.selectedFile).toBeNull();
    });

    test("covers empty gallery and unmatched selection behavior", () => {
        const controller = getController(application);
        const gallery = document.getElementById("image-modal-gallery");

        controller.renderGallery([]);
        expect(gallery.innerHTML).toContain("No images found");

        controller.loadedImages = [
            {
                id: 10,
                url: "/img/10.jpg",
                original_name: "ten.jpg",
            },
        ];

        const unmatchedCard = document.createElement("div");
        unmatchedCard.className = "card image-card";
        unmatchedCard.dataset.imageId = "99";
        gallery.appendChild(unmatchedCard);

        controller.onGalleryImageClick(unmatchedCard);

        expect(controller.selectedImageId).toBe(99);
        expect(controller.selectedImage).toBeNull();

        controller.onSearch("zzz");
        expect(gallery.innerHTML).toContain("No images found");
    });

    test("covers selection sync and data-clearing branches", () => {
        const controller = getController(application);
        const target = document.getElementById("image-target");

        target.value = "abc";
        target.dataset.selectedImageUrl = "/img/old.jpg";
        target.dataset.selectedImageThumbnailUrl = "/img/old-thumb.jpg";
        target.dataset.selectedImageHeroUrl = "/img/old-hero.jpg";

        controller.loadedImages = [
            {
                id: 2,
                url: "/img/2.jpg",
                thumbnail_url: "/img/2-thumb.jpg",
            },
        ];

        controller.syncTargetFieldSelection();
        expect(target.dataset.selectedImageUrl).toBe("/img/old.jpg");

        controller.applySelectedImageData(null);
        expect(target.dataset.selectedImageUrl).toBeUndefined();
        expect(target.dataset.selectedImageThumbnailUrl).toBeUndefined();
        expect(target.dataset.selectedImageHeroUrl).toBeUndefined();

        controller.applySelectedImageData({ id: 2, url: "/img/2.jpg" });
        expect(target.dataset.selectedImageUrl).toBe("/img/2.jpg");
        expect(target.dataset.selectedImageThumbnailUrl).toBeUndefined();
        expect(target.dataset.selectedImageHeroUrl).toBeUndefined();
    });

    test("covers upload fallback and preparation error branches", async () => {
        const controller = getController(application);
        const alertSpy = jest
            .spyOn(window, "alert")
            .mockImplementation(() => {});

        controller.selectedFile = new File(["abc"], "photo.jpg", {
            type: "image/jpeg",
        });

        controller.uploadBtn = null;
        await controller.onUploadImage();

        controller.uploadBtn = document.getElementById(
            "image-modal-upload-btn",
        );
        controller.tagForm = null;
        controller.targetField = null;
        controller.config.uploadContext = null;
        controller.skipCropToggle.checked = false;

        controller.cropper = {
            getCroppedCanvas: () => ({
                toBlob: (callback) => callback(null),
            }),
        };

        await controller.onUploadImage();

        expect(fetch).not.toHaveBeenCalled();
        expect(alertSpy).toHaveBeenCalledWith(
            "Upload failed: Unable to prepare image",
        );
        expect(controller.uploadBtn.disabled).toBe(false);
        expect(controller.uploadBtn.textContent).toBe("Upload & Crop");

        alertSpy.mockRestore();
    });

    test("covers connect defaults, gallery click miss, and unbind/destroy guards", async () => {
        const modalElement = document.getElementById("image-modal");
        [
            "target-field-id",
            "tag-filter",
            "upload-context",
            "aspect-ratio",
            "csrf-token",
        ].forEach((value) =>
            modalElement.removeAttribute(`data-image-selector-${value}-value`),
        );
        application.stop();

        application = Application.start();
        application.register("image-selector", ImageSelectorController);

        await flush();

        const controller = getController(application);
        const gallery = document.getElementById("image-modal-gallery");

        expect(controller.config).toEqual({});
        expect(controller.aspectRatio).toBeNull();

        gallery.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        expect(controller.selectedImageId).toBeNull();

        controller.listeners = null;
        expect(() => controller.unbindEvents()).not.toThrow();

        const destroy = jest.fn();
        controller.cropper = { destroy };
        controller.destroyCropper();
        expect(destroy).toHaveBeenCalled();
        expect(controller.cropper).toBeNull();
    });

    test("refreshes config and target field when config appears after connect", async () => {
        const modalElement = document.getElementById("image-modal");
        [
            "target-field-id",
            "tag-filter",
            "upload-context",
            "aspect-ratio",
            "csrf-token",
        ].forEach((value) =>
            modalElement.removeAttribute(`data-image-selector-${value}-value`),
        );
        application.stop();

        application = Application.start();
        application.register("image-selector", ImageSelectorController);
        await flush();

        const controller = getController(application);
        expect(controller.targetField).toBeNull();
        expect(controller.aspectRatio).toBeNull();

        modalElement.setAttribute(
            "data-image-selector-target-field-id-value",
            "image-target",
        );
        modalElement.setAttribute(
            "data-image-selector-aspect-ratio-value",
            "1",
        );

        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({
                images: [],
            }),
        });

        const showEvent = new Event("show.bs.modal");
        showEvent.relatedTarget = document.getElementById(
            "image-modal-trigger",
        );
        modalElement.dispatchEvent(showEvent);
        modalElement.dispatchEvent(new Event("shown.bs.modal"));
        await flush();

        expect(controller.targetField).toBe(
            document.getElementById("image-target"),
        );
        expect(controller.aspectRatio).toBe(1);
    });

    test("covers modal-hidden optional paths and select-tab no-reload branch", () => {
        const controller = getController(application);

        controller.fileInput = null;
        controller.skipCropToggle = null;
        controller.cropContainer = null;
        controller.cropPreview = null;
        controller.noPreview = null;
        controller.cropControls = null;
        controller.onModalHidden();

        const loadSpy = jest
            .spyOn(controller, "loadImages")
            .mockImplementation(() => Promise.resolve());
        controller.loadedImages = [{ id: 1, url: "/img/1.jpg" }];
        controller.onSelectTabShown();
        expect(loadSpy).not.toHaveBeenCalled();
        loadSpy.mockRestore();

        controller.toggleActionButtons(false);
        expect(controller.selectBtn.classList.contains("d-none")).toBe(true);
        expect(controller.uploadBtn.classList.contains("d-none")).toBe(false);

        const dismissClick = jest.spyOn(controller.dismissBtn, "click");
        controller.targetField = null;
        controller.selectedImageId = 11;
        controller.selectedImage = { id: 11, url: "/img/11.jpg" };
        controller.onSelectImage();
        expect(dismissClick).toHaveBeenCalled();
        dismissClick.mockRestore();
    });

    test("covers loadImages tag-filter query and sync fallback to null selection", async () => {
        const controller = getController(application);
        const target = document.getElementById("image-target");

        controller.config.tagFilter = "portrait";
        target.value = "999";
        target.dataset.selectedImageUrl = "/img/old.jpg";

        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({}),
        });

        await controller.loadImages();

        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining("tag=portrait"),
            expect.objectContaining({ credentials: "same-origin" }),
        );
        expect(controller.loadedImages).toEqual([]);
        expect(target.dataset.selectedImageUrl).toBeUndefined();
        expect(controller.gallery.innerHTML).toContain("No images found");
    });

    test("covers file-selected optional element guards and missing Cropper", async () => {
        const controller = getController(application);
        const fileInput = document.getElementById("image-modal-file-input");
        const errorSpy = jest
            .spyOn(console, "error")
            .mockImplementation(() => {});
        const previousCropper = window.Cropper;

        controller.skipCropToggle = null;
        controller.cropImage = null;
        controller.cropContainer = null;
        controller.cropPreview = null;
        controller.noPreview = null;
        controller.cropControls = null;
        window.Cropper = undefined;

        Object.defineProperty(fileInput, "files", {
            configurable: true,
            value: [
                new File(["file"], "no-cropper.jpg", {
                    type: "image/jpeg",
                }),
            ],
        });

        fileInput.dispatchEvent(new Event("change", { bubbles: true }));
        await flush();

        expect(errorSpy).toHaveBeenCalledWith("Cropper.js is not available.");

        window.Cropper = previousCropper;
        errorSpy.mockRestore();
    });

    test("covers upload not-ok response and API error fallback branches", async () => {
        const controller = getController(application);
        const alertSpy = jest
            .spyOn(window, "alert")
            .mockImplementation(() => {});

        controller.selectedFile = new File(["img"], "", {
            type: "image/jpeg",
        });
        controller.skipCropToggle.checked = false;
        controller.aspectRatio = null;
        controller.targetField = null;
        controller.cropper = {
            getCroppedCanvas: jest.fn(() => ({
                toBlob: (callback) =>
                    callback(new Blob(["jpeg"], { type: "image/jpeg" })),
            })),
        };

        fetch.mockResolvedValueOnce({
            ok: false,
            json: async () => ({}),
        });
        await controller.onUploadImage();

        fetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({ success: false }),
        });
        await controller.onUploadImage();

        expect(alertSpy).toHaveBeenCalledWith("Upload failed: Upload failed");
        expect(controller.cropper.getCroppedCanvas).toHaveBeenCalledWith(
            undefined,
        );
        expect(controller.uploadBtn.disabled).toBe(false);
        expect(controller.uploadBtn.textContent).toBe("Upload & Crop");

        alertSpy.mockRestore();
    });
});
