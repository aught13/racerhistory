/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import TagModalController from "../controllers/tag_modal_controller.js";

const flush = async () => {
    await Promise.resolve();
    await Promise.resolve();
};

describe("tag-modal controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = "";

        const wrapper = document.createElement("div");
        wrapper.setAttribute("data-controller", "tag-modal");
        wrapper.setAttribute("data-tag-modal-subject-value", "images");
        wrapper.setAttribute("data-tag-modal-subject-id-value", "123");

        const host = document.createElement("div");
        host.className = "tag-modal-host";
        wrapper.appendChild(host);

        document.body.appendChild(wrapper);

        application = Application.start();
        application.register("tag-modal", TagModalController);

        jest.spyOn(console, "error").mockImplementation(() => {});
        jest.spyOn(console, "warn").mockImplementation(() => {});
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete window.fetch;
        delete window.bootstrap;
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("fetches modal and removes it on save (no bootstrap)", async () => {
        // Mock fetch: first call returns modal markup, subsequent call returns JSON payload
        window.fetch = jest.fn(async (url) => {
            if (String(url).includes("/admin/tags/modal/")) {
                const html = `
                    <div class="modal" id="tag-modal-images-123">
                      <div class="modal-dialog"><div class="modal-content">
                        <button data-action="tag-modal#save">Save Tags</button>
                        <div data-tag-modal-fields="1" data-apply-url="/admin/tags/apply/images/123">
                          <input name="tags" value="" />
                        </div>
                      </div></div>
                    </div>`;

                return { ok: true, text: async () => html };
            }

            return {
                ok: true,
                json: async () => ({
                    tags: [{ name: "x" }],
                    formFields: { tags: "x" },
                }),
            };
        });

        const controller = application.controllers.find(
            (c) => c.identifier === "tag-modal",
        );

        // Open modal (fetches markup and appends to document.body)
        await controller.open();
        await flush();

        expect(document.getElementById("tag-modal-images-123")).not.toBeNull();

        // Save should POST and then remove modal from DOM (fallback path)
        await controller.save();
        await flush();

        expect(document.getElementById("tag-modal-images-123")).toBeNull();
        expect(window.fetch).toHaveBeenCalled();
    });

    test("works with bootstrap modal instance and removes after hidden.bs.modal", async () => {
        window.fetch = jest.fn(async (url) => {
            if (String(url).includes("/admin/tags/modal/")) {
                const html = `
                    <div class="modal" id="tag-modal-images-123">
                      <div class="modal-dialog"><div class="modal-content">
                        <button data-action="tag-modal#save">Save Tags</button>
                        <div data-tag-modal-fields="1" data-apply-url="/admin/tags/apply/images/123">
                          <input name="tags" value="" />
                        </div>
                      </div></div>
                    </div>`;

                return { ok: true, text: async () => html };
            }

            return {
                ok: true,
                json: async () => ({
                    tags: [{ name: "y" }],
                    formFields: { tags: "y" },
                }),
            };
        });

        // Create a bootstrap Modal instance stub that triggers hidden event when hide() is called
        const instance = {
            show: jest.fn(),
            hide: jest.fn(() => {
                const modalEl = document.getElementById("tag-modal-images-123");
                if (modalEl) {
                    modalEl.dispatchEvent(new Event("hidden.bs.modal"));
                }
            }),
        };

        window.bootstrap = {
            Modal: {
                getOrCreateInstance: jest.fn(() => instance),
                getInstance: jest.fn(() => instance),
            },
        };

        const controller = application.controllers.find(
            (c) => c.identifier === "tag-modal",
        );

        await controller.open();
        await flush();

        expect(window.bootstrap.Modal.getOrCreateInstance).toHaveBeenCalled();
        expect(instance.show).toHaveBeenCalled();

        await controller.save();
        await flush();

        // Instance.hide dispatches hidden.bs.modal, handler should remove modal
        expect(document.getElementById("tag-modal-images-123")).toBeNull();
    });
});
