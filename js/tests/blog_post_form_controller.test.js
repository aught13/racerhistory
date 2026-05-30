/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import BlogPostFormController from "../controllers/blog_post_form_controller.js";

describe("blog-post-form controller", () => {
    let application;
    let tinymceInitMock;
    let tinymceRemoveMock;
    let tinymceGetMock;

    beforeEach(() => {
        document.body.innerHTML = `
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
});
