/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import TeamSeasonFormController from "../controllers/team_season_form_controller.js";

describe("team-season-form controller", () => {
    let application;
    let controllerElement;
    let tinymceInitMock;
    let tinymceRemoveMock;
    let tinymceGetMock;

    function getController() {
        return application.getControllerForElementAndIdentifier(
            controllerElement,
            "team-season-form",
        );
    }

    beforeEach(() => {
        controllerElement = document.createElement("div");
        controllerElement.setAttribute("data-controller", "team-season-form");
        controllerElement.setAttribute(
            "data-team-season-form-existing-image-id-value",
            "77",
        );
        controllerElement.setAttribute(
            "data-team-season-form-existing-preview-url-value",
            "/img/storage/77-hero.jpg",
        );
        controllerElement.setAttribute(
            "data-team-season-form-upload-url-value",
            "/admin/images/upload",
        );
        controllerElement.innerHTML = `
            <textarea id="team-season-preview" data-team-season-form-target="previewEditor"></textarea>
            <textarea id="team-season-recap" data-team-season-form-target="recapEditor"></textarea>
            <input id="team-season-image-field" data-team-season-form-target="imageField" value="" />
            <div id="team-season-image-preview" data-team-season-form-target="imagePreview" style="display:none;"><img src="" alt="preview"></div>
            <a id="team-season-hero-variant-btn" data-team-season-form-target="heroVariantButton" style="display:none;"></a>
            <button id="select-team-season-image" data-team-season-form-target="uploadButton">Select / Upload</button>
        `;
        document.body.appendChild(controllerElement);

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

        application = Application.start();
        application.register("team-season-form", TeamSeasonFormController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.tinymce;
        delete window.fetch;
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

    test("removes TinyMCE before turbo cache", () => {
        const removeMock = jest.fn();
        tinymceGetMock.mockImplementation(() => ({ remove: removeMock }));

        document.dispatchEvent(new Event("turbo:before-cache"));

        expect(removeMock).toHaveBeenCalled();
    });
});
