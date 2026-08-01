/* global Blob, afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import BlogPostFormController from "../controllers/blog_post_form_controller.js";

describe("blog-post-form controller", () => {
    let application;
    let tinymceInitMock;
    let tinymceRemoveMock;
    let tinymceGetMock;
    let originalXmlHttpRequest;

    const getController = async () => {
        const root = document.querySelector(
            '[data-controller="blog-post-form"]',
        );

        for (let i = 0; i < 4; i += 1) {
            const controller =
                application.getControllerForElementAndIdentifier(
                    root,
                    "blog-post-form",
                ) ||
                application.controllers.find(
                    (item) => item.identifier === "blog-post-form",
                );

            if (controller) {
                return controller;
            }

            await Promise.resolve();
        }

        return undefined;
    };

    beforeEach(() => {
        originalXmlHttpRequest = window.XMLHttpRequest;
        document.body.innerHTML = `
            <meta name="csrfToken" content="csrf-token" />
            <div
                data-controller="blog-post-form"
                data-blog-post-form-existing-hero-id-value="12"
                data-blog-post-form-existing-hero-url-value="/img/storage/12-hero.jpg"
                data-blog-post-form-images-upload-url-value="/admin/images/upload"
            >
                <textarea id="body-editor" data-blog-post-form-target="editor"></textarea>
                <input id="hero-image-field" data-blog-post-form-target="heroField" />
                <button id="unset-hero-btn" data-blog-post-form-target="unsetHeroButton"></button>
                <a id="hero-variant-btn" data-blog-post-form-target="heroVariantButton" style="display:none;"></a>
                <div id="hero-image-preview" data-blog-post-form-target="heroPreview" style="display:none;"><img src="" alt="preview"></div>
                <input id="inline-image-field" data-blog-post-form-target="inlineField" />
            </div>
        `;

        tinymceGetMock = jest.fn(() => null);
        tinymceInitMock = jest.fn();
        tinymceRemoveMock = jest.fn();

        window.tinymce = {
            get: tinymceGetMock,
            init: tinymceInitMock,
            remove: tinymceRemoveMock,
        };

        application = Application.start();
        application.register("blog-post-form", BlogPostFormController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        window.XMLHttpRequest = originalXmlHttpRequest;
        delete window.tinymce;
        document.body.innerHTML = "";
    });

    test("initializes TinyMCE and hero UI state", () => {
        expect(tinymceGetMock).toHaveBeenCalledWith("body-editor");
        expect(tinymceInitMock).toHaveBeenCalledTimes(1);

        const initConfig = tinymceInitMock.mock.calls[0][0];
        expect(initConfig.selector).toBe("#body-editor");
        expect(initConfig.images_upload_url).toBe("/admin/images/upload");

        const heroField = document.getElementById("hero-image-field");
        const heroPreview = document.getElementById("hero-image-preview");
        const heroVariantBtn = document.getElementById("hero-variant-btn");
        const unsetHeroBtn = document.getElementById("unset-hero-btn");

        heroField.value = "12";
        heroField.dataset.selectedImageHeroUrl = "/img/storage/12-selected.jpg";
        heroField.dispatchEvent(new Event("change", { bubbles: true }));

        expect(heroPreview.style.display).toBe("block");
        expect(heroPreview.querySelector("img").src).toContain(
            "/img/storage/12-selected.jpg",
        );
        expect(heroVariantBtn.style.display).toBe("block");
        expect(heroVariantBtn.getAttribute("href")).toBe(
            "/admin/images/crop-hero/12",
        );
        expect(unsetHeroBtn.style.display).toBe("inline-block");
    });

    test("inserts inline image into active editor and clears field", () => {
        const insertContent = jest.fn();
        window.tinymce.activeEditor = { insertContent };

        const inlineField = document.getElementById("inline-image-field");
        inlineField.value = "99";
        inlineField.dataset.selectedImageUrl = "/img/storage/99.jpg";
        inlineField.dispatchEvent(new Event("change", { bubbles: true }));

        expect(insertContent).toHaveBeenCalledWith(
            '<picture><img src="/img/storage/99.jpg" alt="" class="img-fluid" loading="lazy"></picture><p></p>',
        );
        expect(inlineField.value).toBe("");
        expect(inlineField.dataset.selectedImageUrl).toBeUndefined();
    });

    test("removes TinyMCE on turbo lifecycle events", () => {
        tinymceGetMock.mockReturnValue({ remove: tinymceRemoveMock });
        expect(tinymceRemoveMock).not.toHaveBeenCalled();
        document.dispatchEvent(new Event("turbo:before-cache"));
        expect(tinymceRemoveMock).toHaveBeenCalled();
    });

    test("uses existing hero image when field starts empty and supports unset button", () => {
        const heroField = document.getElementById("hero-image-field");
        const heroPreview = document.getElementById("hero-image-preview");
        const heroVariantBtn = document.getElementById("hero-variant-btn");
        const unsetHeroBtn = document.getElementById("unset-hero-btn");

        expect(heroField.value).toBe("12");
        expect(heroPreview.style.display).toBe("block");
        expect(heroPreview.querySelector("img").src).toContain(
            "/img/storage/12-hero.jpg",
        );
        expect(heroVariantBtn.getAttribute("href")).toBe(
            "/admin/images/crop-hero/12",
        );

        unsetHeroBtn.click();

        expect(heroField.value).toBe("");
        expect(heroPreview.style.display).toBe("none");
        expect(heroVariantBtn.style.display).toBe("none");
        expect(unsetHeroBtn.style.display).toBe("none");
    });

    test("handles inline image guard branches when id/url/editor are unavailable", () => {
        const inlineField = document.getElementById("inline-image-field");

        inlineField.value = "abc";
        inlineField.dataset.selectedImageUrl = "/img/storage/abc.jpg";
        inlineField.dispatchEvent(new Event("change", { bubbles: true }));

        inlineField.value = "7";
        delete inlineField.dataset.selectedImageUrl;
        inlineField.dispatchEvent(new Event("change", { bubbles: true }));

        inlineField.value = "7";
        inlineField.dataset.selectedImageUrl = "/img/storage/7.jpg";
        delete window.tinymce.activeEditor;
        inlineField.dispatchEvent(new Event("change", { bubbles: true }));

        expect(inlineField.value).toBe("7");
        expect(inlineField.dataset.selectedImageUrl).toBe("/img/storage/7.jpg");
    });

    test("handles TinyMCE upload success and error branches", async () => {
        const initConfig = tinymceInitMock.mock.calls[0][0];
        const blobInfo = {
            blob: () => new Blob(["img"], { type: "image/jpeg" }),
            filename: () => "hero.jpg",
        };

        const successXhr = {
            upload: {},
            headers: {},
            open: jest.fn(),
            setRequestHeader: jest.fn((key, value) => {
                successXhr.headers[key] = value;
            }),
            send: jest.fn((formData) => {
                successXhr.upload.onprogress({
                    lengthComputable: true,
                    loaded: 3,
                    total: 6,
                });
                successXhr.formData = formData;
                successXhr.onload();
            }),
            status: 200,
            responseText: JSON.stringify({
                success: true,
                image: { url: "/img/storage/hero.jpg" },
            }),
        };

        window.XMLHttpRequest = jest.fn(() => successXhr);
        const progress = jest.fn();

        await expect(
            initConfig.images_upload_handler(blobInfo, progress),
        ).resolves.toBe("/img/storage/hero.jpg");
        expect(progress).toHaveBeenCalledWith(50);
        expect(successXhr.open).toHaveBeenCalledWith(
            "POST",
            "/admin/images/upload",
        );
        expect(successXhr.headers["X-CSRF-Token"]).toBe("csrf-token");
        expect(successXhr.formData.get("upload")).toBeInstanceOf(Blob);

        const httpErrorXhr = {
            upload: {},
            open: jest.fn(),
            setRequestHeader: jest.fn(),
            send: jest.fn(() => httpErrorXhr.onload()),
            status: 500,
            responseText: "",
        };
        window.XMLHttpRequest = jest.fn(() => httpErrorXhr);
        await expect(
            initConfig.images_upload_handler(blobInfo, jest.fn()),
        ).rejects.toBe("HTTP Error: 500");

        const invalidJsonXhr = {
            upload: {},
            open: jest.fn(),
            setRequestHeader: jest.fn(),
            send: jest.fn(() => invalidJsonXhr.onload()),
            status: 200,
            responseText: "not-json",
        };
        window.XMLHttpRequest = jest.fn(() => invalidJsonXhr);
        await expect(
            initConfig.images_upload_handler(blobInfo, jest.fn()),
        ).rejects.toBe("Invalid JSON");

        const uploadErrorXhr = {
            upload: {},
            open: jest.fn(),
            setRequestHeader: jest.fn(),
            send: jest.fn(() => uploadErrorXhr.onload()),
            status: 200,
            responseText: JSON.stringify({ success: false, error: "Nope" }),
        };
        window.XMLHttpRequest = jest.fn(() => uploadErrorXhr);
        await expect(
            initConfig.images_upload_handler(blobInfo, jest.fn()),
        ).rejects.toBe("Nope");

        const networkErrorXhr = {
            upload: {},
            open: jest.fn(),
            setRequestHeader: jest.fn(),
            send: jest.fn(() => networkErrorXhr.onerror()),
            status: 200,
            responseText: "",
        };
        window.XMLHttpRequest = jest.fn(() => networkErrorXhr);
        await expect(
            initConfig.images_upload_handler(blobInfo, jest.fn()),
        ).rejects.toBe("Image upload failed");
    });

    test("connect and disconnect cover optional target guards and timer cleanup", () => {
        const clearTimeoutSpy = jest
            .spyOn(window, "clearTimeout")
            .mockImplementation(() => {});

        const fake = {
            hasHeroFieldTarget: false,
            hasUnsetHeroButtonTarget: false,
            hasInlineFieldTarget: false,
            updateHeroImageState: jest.fn(),
            initTinyMceWhenReady: jest.fn(),
            destroyTinyMCE: jest.fn(),
        };

        BlogPostFormController.prototype.connect.call(fake);
        expect(fake.updateHeroImageState).toHaveBeenCalledTimes(1);
        expect(fake.initTinyMceWhenReady).toHaveBeenCalledTimes(1);

        fake.tinyMceRetryTimer = 123;
        BlogPostFormController.prototype.disconnect.call(fake);
        expect(clearTimeoutSpy).toHaveBeenCalledWith(123);
        expect(fake.destroyTinyMCE).toHaveBeenCalledTimes(1);

        const fakeNoTimer = {
            hasHeroFieldTarget: false,
            hasUnsetHeroButtonTarget: false,
            hasInlineFieldTarget: false,
            updateHeroImageState: jest.fn(),
            initTinyMceWhenReady: jest.fn(),
            destroyTinyMCE: jest.fn(),
        };
        BlogPostFormController.prototype.connect.call(fakeNoTimer);
        fakeNoTimer.tinyMceRetryTimer = null;
        BlogPostFormController.prototype.disconnect.call(fakeNoTimer);
        expect(fakeNoTimer.destroyTinyMCE).toHaveBeenCalledTimes(1);

        clearTimeoutSpy.mockRestore();
    });

    test("connect hero target initialization branches handle empty and prefilled values", () => {
        const makeFake = (existingHeroId, heroValue) => ({
            hasHeroFieldTarget: true,
            heroFieldTarget: {
                value: heroValue,
                addEventListener: jest.fn(),
                removeEventListener: jest.fn(),
            },
            existingHeroIdValue: existingHeroId,
            hasUnsetHeroButtonTarget: false,
            hasInlineFieldTarget: false,
            updateHeroImageState: jest.fn(),
            initTinyMceWhenReady: jest.fn(),
        });

        const fillsEmpty = makeFake(12, "");
        BlogPostFormController.prototype.connect.call(fillsEmpty);
        expect(fillsEmpty.heroFieldTarget.value).toBe("12");

        const keepsPrefilled = makeFake(12, "22");
        BlogPostFormController.prototype.connect.call(keepsPrefilled);
        expect(keepsPrefilled.heroFieldTarget.value).toBe("22");

        const skipsWhenExistingNotPositive = makeFake(0, "");
        BlogPostFormController.prototype.connect.call(
            skipsWhenExistingNotPositive,
        );
        expect(skipsWhenExistingNotPositive.heroFieldTarget.value).toBe("");
    });

    test("initTinyMceWhenReady covers no-editor, missing-id, retry, and existing-editor branches", () => {
        expect(() =>
            BlogPostFormController.prototype.initTinyMceWhenReady.call({
                hasEditorTarget: false,
            }),
        ).not.toThrow();

        expect(() =>
            BlogPostFormController.prototype.initTinyMceWhenReady.call({
                hasEditorTarget: true,
                editorTarget: { id: "" },
            }),
        ).not.toThrow();

        const setTimeoutSpy = jest
            .spyOn(window, "setTimeout")
            .mockImplementation(() => 987);
        const retryContext = {
            hasEditorTarget: true,
            editorTarget: { id: "body-editor" },
            tinyMceRetryCount: 0,
            tinyMceRetryTimer: null,
            initTinyMceWhenReady: jest.fn(),
        };

        const originalTinymce = window.tinymce;
        delete window.tinymce;
        BlogPostFormController.prototype.initTinyMceWhenReady.call(
            retryContext,
        );
        expect(retryContext.tinyMceRetryCount).toBe(1);
        expect(setTimeoutSpy).toHaveBeenCalled();

        retryContext.tinyMceRetryCount = 20;
        setTimeoutSpy.mockClear();
        BlogPostFormController.prototype.initTinyMceWhenReady.call(
            retryContext,
        );
        expect(setTimeoutSpy).not.toHaveBeenCalled();

        const existingEditorInit = jest.fn();
        window.tinymce = {
            get: jest.fn(() => ({ id: "body-editor" })),
            init: existingEditorInit,
        };
        BlogPostFormController.prototype.initTinyMceWhenReady.call({
            hasEditorTarget: true,
            editorTarget: { id: "body-editor" },
            imagesUploadUrlValue: "/admin/images/upload",
            tinyMceRetryCount: 0,
            tinyMceRetryTimer: null,
        });
        expect(existingEditorInit).not.toHaveBeenCalled();

        window.tinymce = originalTinymce;
        setTimeoutSpy.mockRestore();
    });

    test("upload handler covers non-computable progress, missing csrf token, and generic upload failure fallback", async () => {
        const initConfig = tinymceInitMock.mock.calls[0][0];
        const blobInfo = {
            blob: () => new Blob(["img"], { type: "image/jpeg" }),
            filename: () => "hero.jpg",
        };

        const csrfMeta = document.querySelector('meta[name="csrfToken"]');
        csrfMeta?.remove();

        const fallbackXhr = {
            upload: {},
            headers: {},
            open: jest.fn(),
            setRequestHeader: jest.fn((key, value) => {
                fallbackXhr.headers[key] = value;
            }),
            send: jest.fn(() => {
                fallbackXhr.upload.onprogress({
                    lengthComputable: false,
                    loaded: 1,
                    total: 10,
                });
                fallbackXhr.onload();
            }),
            status: 200,
            responseText: JSON.stringify({ success: false }),
        };

        window.XMLHttpRequest = jest.fn(() => fallbackXhr);
        const progress = jest.fn();

        await expect(
            initConfig.images_upload_handler(blobInfo, progress),
        ).rejects.toBe("Upload failed");
        expect(progress).not.toHaveBeenCalled();
        expect(fallbackXhr.setRequestHeader).not.toHaveBeenCalled();
    });

    test("destroy, clear hero, update hero, inline guard, and cache bust utility cover fallback branches", async () => {
        const controller = await getController();

        const originalTinymce = window.tinymce;
        delete window.tinymce;
        expect(() => controller.destroyTinyMCE()).not.toThrow();

        window.tinymce = {
            get: jest.fn(() => null),
        };
        controller.destroyTinyMCE();
        expect(window.tinymce.get).toHaveBeenCalledWith("body-editor");
        window.tinymce = originalTinymce;

        expect(() =>
            BlogPostFormController.prototype.clearHeroImage.call({
                hasHeroFieldTarget: false,
            }),
        ).not.toThrow();

        expect(() =>
            BlogPostFormController.prototype.updateHeroImageState.call({
                hasHeroFieldTarget: false,
            }),
        ).not.toThrow();

        const previewOnly = document.createElement("div");
        const previewContext = {
            hasHeroFieldTarget: true,
            heroFieldTarget: {
                value: "7",
                dataset: {
                    selectedImageUrl: "/img/storage/7.jpg",
                },
            },
            existingHeroIdValue: 12,
            existingHeroUrlValue: "/img/storage/12-hero.jpg",
            hasHeroPreviewTarget: true,
            heroPreviewTarget: previewOnly,
            hasUnsetHeroButtonTarget: false,
            hasHeroVariantButtonTarget: false,
            withCacheBust: jest.fn((url) => url + "?_ts=1"),
        };
        BlogPostFormController.prototype.updateHeroImageState.call(
            previewContext,
        );
        expect(previewContext.withCacheBust).not.toHaveBeenCalled();
        expect(previewOnly.style.display).toBe("block");

        expect(() =>
            BlogPostFormController.prototype.onInlineFieldChange.call({
                hasInlineFieldTarget: false,
            }),
        ).not.toThrow();

        expect(controller.withCacheBust("")).toBe("");
        expect(controller.withCacheBust("/img/storage/1.jpg")).toContain(
            "?_ts=",
        );
        expect(
            controller.withCacheBust("/img/storage/1.jpg?size=hero"),
        ).toContain("&_ts=");
    });

    test("destroyTinyMCE covers editor without getContent and no hasEditorTarget branches", () => {
        tinymceGetMock.mockReturnValue({
            remove: tinymceRemoveMock,
        });

        // Branch: editor exists but hasEditorTarget is false
        const fakeNoTarget = {
            hasEditorTarget: false,
            destroyTinyMCE: BlogPostFormController.prototype.destroyTinyMCE,
        };
        fakeNoTarget.destroyTinyMCE();
        expect(tinymceRemoveMock).toHaveBeenCalled();
        tinymceRemoveMock.mockClear();

        // Branch: editor exists but doesn't have getContent method
        tinymceGetMock.mockReturnValue({
            remove: tinymceRemoveMock,
        });
        const fakeWithTarget = {
            hasEditorTarget: true,
            editorTarget: {
                value: "original",
            },
            destroyTinyMCE: BlogPostFormController.prototype.destroyTinyMCE,
        };
        fakeWithTarget.destroyTinyMCE();
        expect(tinymceRemoveMock).toHaveBeenCalled();
        expect(fakeWithTarget.editorTarget.value).toBe("original");
    });

    test("destroyTinyMCE covers null editor fallback and error handling branches", () => {
        // Branch: editor is null, should call remove with selector string
        tinymceGetMock.mockReturnValue(null);
        const removeWithSelectorMock = jest.fn();
        window.tinymce.remove = removeWithSelectorMock;

        const controller = {
            hasEditorTarget: true,
            editorTarget: {
                value: "test",
            },
            destroyTinyMCE: BlogPostFormController.prototype.destroyTinyMCE,
        };
        controller.destroyTinyMCE();

        // When editor is null, should call remove with selector
        expect(removeWithSelectorMock).toHaveBeenCalledWith("#body-editor");

        // Branch: error in try block is caught
        tinymceGetMock.mockImplementation(() => {
            throw new Error("Unexpected error");
        });
        const warnSpy = jest.spyOn(console, "warn").mockImplementation();

        controller.destroyTinyMCE();
        expect(warnSpy).toHaveBeenCalledWith(
            "Error destroying TinyMCE editor:",
            expect.any(Error),
        );

        warnSpy.mockRestore();
    });

    test("destroyTinyMCE with editor that has getContent saves content before removal", () => {
        // Branch: editor with getContent function - should save content
        const editorWithGetContent = {
            getContent: jest.fn(() => "saved content"),
            remove: jest.fn(),
        };
        tinymceGetMock.mockReturnValue(editorWithGetContent);

        const controller = {
            hasEditorTarget: true,
            editorTarget: {
                value: "",
            },
            destroyTinyMCE: BlogPostFormController.prototype.destroyTinyMCE,
        };

        controller.destroyTinyMCE();

        // Verify content was saved
        expect(editorWithGetContent.getContent).toHaveBeenCalled();
        expect(controller.editorTarget.value).toBe("saved content");
    });
});
