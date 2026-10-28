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
            >
                <input id="person-image-field" data-person-form-target="imageField" value="" />
                <div id="person-image-preview" data-person-form-target="imagePreview" style="display:none;">
                    <img src="" alt="preview" />
                </div>
            </div>
        `;

        application = Application.start();
        application.register("person-form", PersonFormController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
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

    test("disconnect removes the image change listener", async () => {
        await flush();

        const imageField = document.getElementById("person-image-field");
        const removeEventListenerSpy = jest.spyOn(
            imageField,
            "removeEventListener",
        );
        const controller = application.controllers.find(
            (item) => item.identifier === "person-form",
        );

        controller.disconnect();

        expect(removeEventListenerSpy).toHaveBeenCalledWith(
            "change",
            expect.any(Function),
        );
        removeEventListenerSpy.mockRestore();
    });

    test("withCacheBust appends query parameter with ampersand when URL has existing query", async () => {
        await flush();

        const controller = application.controllers.find(
            (item) => item.identifier === "person-form",
        );
        const urlWithQuery = "http://example.com/image.jpg?width=100";

        expect(controller.withCacheBust(urlWithQuery)).toContain("width=100");
        expect(controller.withCacheBust(urlWithQuery)).toContain("&_ts=");
    });

    test("updateImagePreview hides when image id is invalid", async () => {
        await flush();

        const imageField = document.getElementById("person-image-field");
        const imagePreview = document.getElementById("person-image-preview");
        imageField.value = "abc";
        imageField.dataset.selectedImageUrl = "/img/storage/fallback.jpg";
        imageField.dispatchEvent(new Event("change", { bubbles: true }));

        expect(imagePreview.style.display).toBe("none");
    });

    test("updateImagePreview handles missing img element", async () => {
        application.stop();
        application = null;
        document.body.innerHTML = `
            <div data-controller="person-form" data-person-form-initial-image-id-value="42" data-person-form-initial-preview-url-value="/img/42.jpg">
                <input id="person-image-field" data-person-form-target="imageField" value="42" />
                <div id="person-image-preview" data-person-form-target="imagePreview" style="display:none;"></div>
            </div>
        `;

        application = Application.start();
        application.register("person-form", PersonFormController);
        await flush();

        expect(
            document.getElementById("person-image-preview").style.display,
        ).toBe("block");
    });

    test("previewUrlForField prefers selected thumbnail url", async () => {
        await flush();

        const imageField = document.getElementById("person-image-field");
        imageField.value = "123";
        imageField.dataset.selectedImageThumbnailUrl =
            "/img/storage/123-thumb.jpg";
        imageField.dataset.selectedImageUrl = "/img/storage/123.jpg";
        const controller = application.controllers.find(
            (item) => item.identifier === "person-form",
        );

        expect(controller.previewUrlForField()).toBe(
            "/img/storage/123-thumb.jpg",
        );
    });

    test("withCacheBust returns empty string for falsy urls", async () => {
        await flush();

        const controller = application.controllers.find(
            (item) => item.identifier === "person-form",
        );

        expect(controller.withCacheBust("")).toBe("");
        expect(controller.withCacheBust(null)).toBe("");
        expect(controller.withCacheBust(undefined)).toBe("");
    });
});
