/* global Blob, afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import PersonFormController from "../controllers/person_form_controller.js";

describe("person-form controller", () => {
    let application;
    let originalXmlHttpRequest;

    function flush() {
        return new Promise((resolve) => {
            window.setTimeout(resolve, 0);
        });
    }

    beforeEach(() => {
        originalXmlHttpRequest = window.XMLHttpRequest;
        document.body.innerHTML = `
            <meta name="csrfToken" content="csrf-value" />
            <div
                data-controller="person-form"
                data-person-form-initial-image-id-value="42"
                data-person-form-initial-preview-url-value="/img/storage/42-thumb.jpg"
                data-person-form-images-upload-url-value="/admin/images/upload"
            >
                <input id="person-image-field" data-person-form-target="imageField" value="" />
                <div id="person-image-preview" data-person-form-target="imagePreview" style="display:none;">
                    <img src="" alt="preview" />
                </div>
                <textarea id="bio-editor" data-person-form-target="bioEditor"></textarea>
            </div>
        `;

        window.tinymce = {
            get: jest.fn(() => null),
            init: jest.fn(),
            remove: jest.fn(),
        };

        application = Application.start();
        application.register("person-form", PersonFormController);
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

    test("initializes TinyMCE for bio editor", async () => {
        await flush();

        expect(window.tinymce.get).toHaveBeenCalledWith("bio-editor");
        expect(window.tinymce.init).toHaveBeenCalledTimes(1);

        const initConfig = window.tinymce.init.mock.calls[0][0];
        expect(initConfig.selector).toBe("#bio-editor");
        expect(initConfig.images_upload_url).toBe("/admin/images/upload");
    });

    test("updates image preview when selected image data is set", async () => {
        await flush();

        const imageField = document.getElementById("person-image-field");
        const imagePreview = document.getElementById("person-image-preview");
        const previewImg = imagePreview.querySelector("img");

        imageField.value = "123";
        imageField.dataset.selectedImageThumbnailUrl =
            "/img/storage/123-thumb.jpg";
        imageField.dispatchEvent(new Event("change", { bubbles: true }));

        expect(imagePreview.style.display).toBe("block");
        expect(previewImg.src).toContain("/img/storage/123-thumb.jpg");
        expect(previewImg.src).toContain("_ts=");
    });

    test("hides preview when image id is invalid and supports selected image url fallback", async () => {
        await flush();

        const imageField = document.getElementById("person-image-field");
        const imagePreview = document.getElementById("person-image-preview");
        const previewImg = imagePreview.querySelector("img");

        imageField.value = "abc";
        imageField.dataset.selectedImageUrl = "/img/storage/fallback.jpg";
        imageField.dispatchEvent(new Event("change", { bubbles: true }));

        expect(imagePreview.style.display).toBe("none");

        imageField.value = "31";
        imageField.dispatchEvent(new Event("change", { bubbles: true }));
        expect(imagePreview.style.display).toBe("block");
        expect(previewImg.src).toContain("/img/storage/fallback.jpg");
    });

    test("uses initial preview url when matching initial image id", async () => {
        await flush();

        const imageField = document.getElementById("person-image-field");
        const imagePreview = document.getElementById("person-image-preview");
        const previewImg = imagePreview.querySelector("img");

        imageField.value = "42";
        imageField.dispatchEvent(new Event("change", { bubbles: true }));

        expect(imagePreview.style.display).toBe("block");
        expect(previewImg.src).toContain("/img/storage/42-thumb.jpg");
    });

    test("removes TinyMCE before turbo cache", async () => {
        await flush();

        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(window.tinymce.remove).toHaveBeenCalledWith("#bio-editor");
    });

    test("skips TinyMCE init when editor has no id", async () => {
        application.stop();
        application = null;

        document.body.innerHTML = `
            <div data-controller="person-form">
                <textarea data-person-form-target="bioEditor"></textarea>
            </div>
        `;

        window.tinymce = {
            get: jest.fn(() => null),
            init: jest.fn(),
            remove: jest.fn(),
        };

        application = Application.start();
        application.register("person-form", PersonFormController);

        await flush();

        expect(window.tinymce.get).not.toHaveBeenCalled();
        expect(window.tinymce.init).not.toHaveBeenCalled();
    });

    test("handles TinyMCE image upload success and progress", async () => {
        await flush();

        const xhr = {
            upload: {},
            headers: {},
            open: jest.fn(),
            setRequestHeader: jest.fn((key, value) => {
                xhr.headers[key] = value;
            }),
            send: jest.fn((formData) => {
                xhr.upload.onprogress({
                    lengthComputable: true,
                    loaded: 5,
                    total: 10,
                });
                xhr.formData = formData;
                xhr.onload();
            }),
            status: 200,
            responseText: JSON.stringify({
                success: true,
                image: { url: "/img/storage/uploaded.jpg" },
            }),
        };
        window.XMLHttpRequest = jest.fn(() => xhr);

        const initConfig = window.tinymce.init.mock.calls[0][0];
        const progress = jest.fn();
        const blobInfo = {
            blob: () => new Blob(["img"], { type: "image/jpeg" }),
            filename: () => "upload.jpg",
        };

        await expect(
            initConfig.images_upload_handler(blobInfo, progress),
        ).resolves.toBe("/img/storage/uploaded.jpg");

        expect(progress).toHaveBeenCalledWith(50);
        expect(window.XMLHttpRequest).toHaveBeenCalledTimes(1);
        expect(xhr.open).toHaveBeenCalledWith("POST", "/admin/images/upload");
        expect(xhr.headers["X-CSRF-Token"]).toBe("csrf-value");
        expect(xhr.formData.get("upload")).toBeInstanceOf(Blob);
    });

    test("handles TinyMCE image upload error branches", async () => {
        await flush();

        const initConfig = window.tinymce.init.mock.calls[0][0];
        const blobInfo = {
            blob: () => new Blob(["img"], { type: "image/jpeg" }),
            filename: () => "upload.jpg",
        };

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
});
