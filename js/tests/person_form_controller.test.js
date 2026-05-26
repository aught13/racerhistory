/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import PersonFormController from "../controllers/person_form_controller.js";

describe("person-form controller", () => {
    let application;

    function flush() {
        return new Promise((resolve) => {
            window.setTimeout(resolve, 0);
        });
    }

    beforeEach(() => {
        document.body.innerHTML = `
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
});
