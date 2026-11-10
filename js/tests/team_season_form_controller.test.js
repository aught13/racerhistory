/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import TeamSeasonFormController from "../controllers/team_season_form_controller.js";

describe("team-season-form controller", () => {
    let application;
    let controllerElement;
    let tinymceInitMock;
    let tinymceRemoveMock;
    let tinymceGetMock;
    let originalXMLHttpRequest;

    const flush = async () => {
        await Promise.resolve();
        await Promise.resolve();
    };

    function getController() {
        return application.getControllerForElementAndIdentifier(
            controllerElement,
            "team-season-form",
        );
    }

    function controllerMarkup(options = {}) {
        const {
            includePreviewEditor = true,
            includeRecapEditor = true,
            includeImageField = true,
            includeImagePreview = true,
            includePreviewImageTag = true,
            includeHeroVariantButton = true,
            includeUploadButton = true,
            imageFieldValue = "",
        } = options;

        return `
            <select id="team-id" data-team-season-form-target="teamSelect">
                <option value="">Select a Team</option>
            </select>
            <select id="season-id" data-team-season-form-target="seasonSelect">
                <option value="">Select a Season</option>
            </select>
            ${
                includePreviewEditor
                    ? '<textarea id="team-season-preview" data-team-season-form-target="previewEditor"></textarea>'
                    : ""
            }
            ${
                includeRecapEditor
                    ? '<textarea id="team-season-recap" data-team-season-form-target="recapEditor"></textarea>'
                    : ""
            }
            ${
                includeImageField
                    ? `<input id="team-season-image-field" data-team-season-form-target="imageField" data-action="change->team-season-form#handleImageFieldChange" value="${imageFieldValue}" />`
                    : ""
            }
            ${
                includeImagePreview
                    ? `<div id="team-season-image-preview" data-team-season-form-target="imagePreview" style="display:none;">${
                          includePreviewImageTag
                              ? '<img src="" alt="preview">'
                              : ""
                      }</div>`
                    : ""
            }
            ${
                includeHeroVariantButton
                    ? '<a id="team-season-hero-variant-btn" data-team-season-form-target="heroVariantButton" style="display:none;"></a>'
                    : ""
            }
            ${
                includeUploadButton
                    ? '<button id="select-team-season-image" data-team-season-form-target="uploadButton" data-action="click->team-season-form#handleUploadClick">Select / Upload</button>'
                    : ""
            }
        `;
    }

    async function mountController(options = {}) {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";

        controllerElement = document.createElement("div");
        controllerElement.setAttribute("data-controller", "team-season-form");
        controllerElement.setAttribute(
            "data-action",
            "turbo:before-cache@document->team-season-form#beforeCache turbo:before-render@document->team-season-form#beforeRender turbo:load@document->team-season-form#onTurboLoad admin:popup-option-added@document->team-season-form#popupOptionAdded",
        );
        controllerElement.setAttribute(
            "data-team-season-form-existing-image-id-value",
            options.existingImageIdValue ?? "77",
        );
        controllerElement.setAttribute(
            "data-team-season-form-existing-preview-url-value",
            options.existingPreviewUrlValue ?? "/img/storage/77-hero.jpg",
        );
        controllerElement.setAttribute(
            "data-team-season-form-csrf-token-value",
            options.csrfTokenValue ?? "",
        );
        controllerElement.setAttribute(
            "data-team-season-form-max-upload-bytes-value",
            options.maxUploadBytesValue ?? 0,
        );
        controllerElement.setAttribute(
            "data-team-season-form-upload-url-value",
            options.uploadUrlValue ?? "/admin/images/upload",
        );
        controllerElement.innerHTML = controllerMarkup(options);
        document.body.appendChild(controllerElement);

        application = Application.start();
        application.register("team-season-form", TeamSeasonFormController);

        await flush();

        return (
            getController() ||
            application.controllers.find(
                (controller) =>
                    controller.identifier === "team-season-form" &&
                    controller.element === controllerElement,
            )
        );
    }

    beforeEach(async () => {
        originalXMLHttpRequest = window.XMLHttpRequest;

        tinymceGetMock = jest.fn(() => null);
        tinymceInitMock = jest.fn();
        tinymceRemoveMock = jest.fn();
        window.tinymce = {
            get: tinymceGetMock,
            init: tinymceInitMock,
            remove: tinymceRemoveMock,
        };

        window.fetch = jest.fn(async () => ({
            ok: true,
            json: async () => ({
                success: true,
                image: {
                    id: 88,
                    url: "/img/storage/88.jpg",
                    thumbnail_url: "/img/storage/88-thumb.jpg",
                    hero_url: "/img/storage/88-hero.jpg",
                },
            }),
        }));

        await mountController();
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        window.XMLHttpRequest = originalXMLHttpRequest;
        delete window.tinymce;
        delete window.fetch;
        document.head.querySelector('meta[name="csrfToken"]')?.remove();
        document.body.innerHTML = "";
    });

    test("initializes both TinyMCE editors and hero UI", () => {
        expect(tinymceGetMock).toHaveBeenCalledWith("team-season-preview");
        expect(tinymceGetMock).toHaveBeenCalledWith("team-season-recap");
        expect(tinymceInitMock).toHaveBeenCalledTimes(2);

        const previewEditorConfig = tinymceInitMock.mock.calls[0][0];
        expect(previewEditorConfig.selector).toBe("#team-season-preview");
        expect(previewEditorConfig.images_upload_url).toBe(
            "/admin/images/upload",
        );

        const field = document.getElementById("team-season-image-field");
        field.value = "77";
        field.dispatchEvent(new Event("change", { bubbles: true }));

        const preview = document.getElementById("team-season-image-preview");
        const heroVariant = document.getElementById(
            "team-season-hero-variant-btn",
        );

        expect(preview.style.display).toBe("block");
        expect(preview.querySelector("img").src).toContain(
            "/img/storage/77-hero.jpg",
        );
        expect(heroVariant.style.display).toBe("block");
        expect(heroVariant.getAttribute("href")).toBe(
            "/admin/images/crop-hero/77",
        );
    });

    test("adds popup-created options to the matching select only", async () => {
        await mountController();

        const teamSelect = controllerElement.querySelector("#team-id");
        const seasonSelect = controllerElement.querySelector("#season-id");
        document.dispatchEvent(
            new CustomEvent("admin:popup-option-added", {
                detail: {
                    targetSelectId: "team-id",
                    newOption: { value: 12, text: "New Team" },
                },
            }),
        );
        document.dispatchEvent(
            new CustomEvent("admin:popup-option-added", {
                detail: {
                    targetSelectId: "unknown-select",
                    newOption: { value: 13, text: "Ignored" },
                },
            }),
        );

        expect(teamSelect.value).toBe("12");
        expect(teamSelect.querySelector('option[value="12"]').textContent).toBe(
            "New Team",
        );
        expect(seasonSelect.value).toBe("");
        expect(seasonSelect.querySelector('option[value="13"]')).toBeNull();
    });

    test("uploads selected file from add-page button and updates preview", async () => {
        const controller = getController();
        const file = new window.File(["x"], "season.jpg", {
            type: "image/jpeg",
        });
        const fileInput = { files: [file] };

        await controller.uploadSelectedFile(fileInput);

        const field = document.getElementById("team-season-image-field");
        const preview = document.getElementById("team-season-image-preview");
        const uploadButton = document.getElementById(
            "select-team-season-image",
        );

        expect(window.fetch).toHaveBeenCalledWith(
            "/admin/images/upload",
            expect.objectContaining({ method: "POST" }),
        );
        expect(field.value).toBe("88");
        expect(preview.style.display).toBe("block");
        expect(preview.querySelector("img").src).toContain(
            "/img/storage/88-hero.jpg",
        );
        expect(uploadButton.disabled).toBe(false);
        expect(uploadButton.textContent).toBe("Select / Upload");
    });

    test("rejects oversized uploads before sending either upload request", async () => {
        const alertSpy = jest
            .spyOn(window, "alert")
            .mockImplementation(() => {});
        const controller = await mountController({ maxUploadBytesValue: 3 });
        const file = new window.File(["large"], "season.jpg", {
            type: "image/jpeg",
        });

        await controller.uploadSelectedFile({ files: [file] });

        expect(window.fetch).not.toHaveBeenCalled();
        expect(alertSpy).toHaveBeenCalledWith(
            "File too large. Maximum allowed: 0.0MB",
        );

        const uploadHandler =
            tinymceInitMock.mock.calls.at(-1)[0].images_upload_handler;
        await expect(
            uploadHandler(
                {
                    blob: () => new window.Blob(["large"]),
                    filename: () => "season.jpg",
                },
                jest.fn(),
            ),
        ).rejects.toBe("File too large. Maximum allowed: 0.0MB");

        alertSpy.mockRestore();
    });

    test("removes TinyMCE before turbo cache", () => {
        const removeMock = jest.fn();
        tinymceGetMock.mockImplementation(() => ({ remove: removeMock }));

        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(removeMock).toHaveBeenCalled();
    });

    test("aborts active image uploads and restores controls before Turbo caching", async () => {
        const controller = getController();
        window.fetch = jest.fn(
            (_url, { signal }) =>
                new Promise((_resolve, reject) => {
                    signal.addEventListener(
                        "abort",
                        () =>
                            reject(
                                Object.assign(new Error("aborted"), {
                                    name: "AbortError",
                                }),
                            ),
                        { once: true },
                    );
                }),
        );

        const upload = controller.uploadSelectedFile({
            files: [
                new window.File(["x"], "season.jpg", { type: "image/jpeg" }),
            ],
        });
        const signal = window.fetch.mock.calls[0][1].signal;
        const uploadButton = document.getElementById(
            "select-team-season-image",
        );
        expect(uploadButton.disabled).toBe(true);

        document.dispatchEvent(new Event("turbo:before-cache"));
        await upload;

        expect(signal.aborted).toBe(true);
        expect(controller.uploadRequest).toBeNull();
        expect(uploadButton.disabled).toBe(false);
        expect(uploadButton.textContent).toBe("Select / Upload");
    });

    test("retries TinyMCE init when unavailable and stops at retry cap", () => {
        const controller = getController();
        const setTimeoutSpy = jest.spyOn(window, "setTimeout");

        delete window.tinymce;
        controller.tinyMceRetryCount = 0;
        controller.initTinyMceWhenReady();

        expect(controller.tinyMceRetryCount).toBe(1);
        expect(setTimeoutSpy).toHaveBeenCalled();

        controller.tinyMceRetryTimer = null;
        controller.tinyMceRetryCount = 20;
        controller.initTinyMceWhenReady();
        expect(controller.tinyMceRetryCount).toBe(20);
        expect(setTimeoutSpy).toHaveBeenCalledTimes(1);

        setTimeoutSpy.mockRestore();
        window.tinymce = {
            get: tinymceGetMock,
            init: tinymceInitMock,
            remove: tinymceRemoveMock,
        };
    });

    test("handles missing editor targets and already-initialized editors", async () => {
        tinymceInitMock.mockClear();
        await mountController({
            includePreviewEditor: false,
            includeRecapEditor: false,
        });
        expect(tinymceInitMock).not.toHaveBeenCalled();

        const controller = await mountController();
        controller.previewEditorTarget.id = "";
        tinymceGetMock.mockImplementation((editorId) =>
            editorId === "team-season-recap" ? { id: editorId } : null,
        );
        tinymceInitMock.mockClear();

        controller.initTinyMceWhenReady();
        expect(tinymceInitMock).not.toHaveBeenCalled();
    });

    test("covers TinyMCE upload handler success, progress, and CSRF header", async () => {
        controllerElement.dataset.teamSeasonFormCsrfTokenValue = "csrf-123";
        const uploadHandler =
            tinymceInitMock.mock.calls[0][0].images_upload_handler;
        const progress = jest.fn();
        const blobInfo = {
            blob: () => new window.Blob(["x"], { type: "image/jpeg" }),
            filename: () => "upload.jpg",
        };
        let xhrInstance;
        class MockXHR {
            constructor() {
                this.upload = {};
                this.status = 0;
                this.responseText = "";
                this.open = jest.fn();
                this.setRequestHeader = jest.fn();
                this.send = jest.fn(() => {
                    this.upload.onprogress({
                        lengthComputable: true,
                        loaded: 25,
                        total: 100,
                    });
                    this.status = 200;
                    this.responseText = JSON.stringify({
                        success: true,
                        image: { url: "/img/storage/from-handler.jpg" },
                    });
                    this.onload();
                });
                xhrInstance = this;
            }
        }

        window.XMLHttpRequest = MockXHR;

        await expect(uploadHandler(blobInfo, progress)).resolves.toBe(
            "/img/storage/from-handler.jpg",
        );
        expect(progress).toHaveBeenCalledWith(25);
        expect(xhrInstance.setRequestHeader).toHaveBeenCalledWith(
            "X-CSRF-Token",
            "csrf-123",
        );
    });

    test("covers TinyMCE upload handler rejection branches", async () => {
        const uploadHandler =
            tinymceInitMock.mock.calls[0][0].images_upload_handler;
        const blobInfo = {
            blob: () => new window.Blob(["x"], { type: "image/jpeg" }),
            filename: () => "upload.jpg",
        };

        const scenarios = [
            {
                status: 500,
                responseText: "{}",
                expected: "HTTP Error: 500",
                triggerError: false,
            },
            {
                status: 200,
                responseText: "{bad json",
                expected: "Invalid JSON",
                triggerError: false,
            },
            {
                status: 200,
                responseText: JSON.stringify({ success: false, error: "nope" }),
                expected: "nope",
                triggerError: false,
            },
            {
                status: 200,
                responseText: JSON.stringify({ success: true, image: {} }),
                expected: "Upload failed",
                triggerError: false,
            },
            {
                status: 0,
                responseText: "{}",
                expected: "Image upload failed",
                triggerError: true,
            },
        ];

        let scenarioIndex = 0;
        let lastXhr;
        class MockXHR {
            constructor() {
                this.upload = {};
                this.open = jest.fn();
                this.setRequestHeader = jest.fn();
                this.send = jest.fn(() => {
                    const scenario = scenarios[scenarioIndex];
                    this.upload.onprogress({
                        lengthComputable: false,
                        loaded: 10,
                        total: 100,
                    });
                    if (scenario.triggerError) {
                        this.onerror();
                        return;
                    }
                    this.status = scenario.status;
                    this.responseText = scenario.responseText;
                    this.onload();
                });
                lastXhr = this;
            }
        }

        window.XMLHttpRequest = MockXHR;

        for (const scenario of scenarios) {
            const progress = jest.fn();
            await expect(uploadHandler(blobInfo, progress)).rejects.toBe(
                scenario.expected,
            );
            expect(progress).not.toHaveBeenCalled();
            expect(lastXhr.setRequestHeader).not.toHaveBeenCalled();
            scenarioIndex += 1;
        }
    });

    test("returns early on upload guard branches", async () => {
        const controller = getController();

        window.fetch.mockClear();
        await controller.uploadSelectedFile({ files: [] });
        expect(window.fetch).not.toHaveBeenCalled();

        const noFieldController = await mountController({
            includeImageField: false,
        });
        window.fetch.mockClear();
        await noFieldController.uploadSelectedFile({
            files: [
                new window.File(["x"], "season.jpg", { type: "image/jpeg" }),
            ],
        });
        expect(window.fetch).not.toHaveBeenCalled();
    });

    test("covers upload failure branches and button-missing path", async () => {
        const alertSpy = jest
            .spyOn(window, "alert")
            .mockImplementation(() => {});
        const errorSpy = jest
            .spyOn(console, "error")
            .mockImplementation(() => {});

        const noButtonController = await mountController({
            includeUploadButton: false,
        });
        window.fetch = jest.fn(async () => ({
            ok: false,
            json: async () => ({}),
        }));

        await noButtonController.uploadSelectedFile({
            files: [
                new window.File(["x"], "season.jpg", { type: "image/jpeg" }),
            ],
        });

        expect(alertSpy).toHaveBeenCalledWith("Upload failed: Upload failed");

        const controller = await mountController();
        window.fetch = jest.fn(async () => ({
            ok: true,
            json: async () => ({ success: false }),
        }));

        await controller.uploadSelectedFile({
            files: [
                new window.File(["x"], "season.jpg", { type: "image/jpeg" }),
            ],
        });

        expect(alertSpy).toHaveBeenCalledWith("Upload failed: Upload failed");
        expect(errorSpy).toHaveBeenCalled();

        alertSpy.mockRestore();
        errorSpy.mockRestore();
    });

    test("covers upload URL fallback fields and preview helper branches", async () => {
        const controller = await mountController({
            includePreviewImageTag: false,
        });

        window.fetch = jest.fn(async () => ({
            ok: true,
            json: async () => ({
                success: true,
                image: { id: 91, url: "", thumbnail_url: "", hero_url: "" },
            }),
        }));

        await controller.uploadSelectedFile({
            files: [
                new window.File(["x"], "season.jpg", { type: "image/jpeg" }),
            ],
        });

        expect(controller.imageFieldTarget.dataset.selectedImageUrl).toBe("");
        expect(
            controller.imageFieldTarget.dataset.selectedImageThumbnailUrl,
        ).toBe("");
        expect(controller.imageFieldTarget.dataset.selectedImageHeroUrl).toBe(
            "",
        );

        controller.imageFieldTarget.value = "not-a-number";
        controller.updateImagePreview();
        expect(controller.imagePreviewTarget.style.display).toBe("none");

        expect(controller.withCacheBust("")).toBe("");
        expect(controller.withCacheBust("/img/storage/pic.jpg")).toContain(
            "?_ts=",
        );
        expect(controller.withCacheBust("/img/storage/pic.jpg?v=1")).toContain(
            "&_ts=",
        );
    });

    test("covers updateHeroVariant guard and hide branch", async () => {
        const noHeroController = await mountController({
            includeHeroVariantButton: false,
        });
        expect(() => noHeroController.updateHeroVariantButton()).not.toThrow();

        const controller = await mountController();
        controller.imageFieldTarget.value = "0";
        controller.updateHeroVariantButton();

        expect(controller.heroVariantButtonTarget.style.display).toBe("none");
    });

    test("clears retry timer on disconnect", () => {
        const controller = getController();
        const clearTimeoutSpy = jest.spyOn(window, "clearTimeout");

        controller.tinyMceRetryTimer = 123;
        controller.disconnect();

        expect(clearTimeoutSpy).toHaveBeenCalledWith(123);
        expect(controller.tinyMceRetryTimer).toBeNull();

        clearTimeoutSpy.mockRestore();
    });

    test("covers disconnect branches and preview guard when targets are absent", async () => {
        const controller = await mountController({
            includeImageField: false,
            includeImagePreview: false,
            includeUploadButton: false,
        });

        expect(() => controller.updateImagePreview()).not.toThrow();
        expect(() => controller.disconnect()).not.toThrow();
    });

    test("destroyTinyMCE covers editors without getContent and missing editor ids branches", async () => {
        // Branch: editors exist but don't have getContent method
        const removeMock = jest.fn();
        tinymceGetMock.mockImplementation(() => ({
            remove: removeMock,
        }));

        const controller = await mountController();
        controller.destroyTinyMCE();

        // Should be called twice (for both preview and recap editors)
        expect(tinymceGetMock).toHaveBeenCalledWith("team-season-preview");
        expect(tinymceGetMock).toHaveBeenCalledWith("team-season-recap");
        expect(removeMock).toHaveBeenCalledTimes(2);

        // Branch: no editor ids (missing id attributes)
        removeMock.mockClear();
        tinymceGetMock.mockClear();

        const controllerNoIds = await mountController({
            includePreviewEditor: false,
            includeRecapEditor: false,
        });
        controllerNoIds.destroyTinyMCE();
        expect(tinymceGetMock).not.toHaveBeenCalled();
    });
});
