/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import TagModalController from "../controllers/tag_modal_controller.js";

const flush = async () => {
    await Promise.resolve();
    await Promise.resolve();
};

describe("tag-modal controller", () => {
    let application;

    const getController = () =>
        application.controllers.find((c) => c.identifier === "tag-modal");

    const modalHtml = ({ includeForm = false } = {}) => `
        <div class="modal" id="tag-modal-images-123">
            <div class="modal-dialog">
                <div class="modal-content">
                    <button data-action="tag-modal#save">Save Tags</button>
                    ${
                        includeForm
                            ? '<form action="/admin/tags/apply/images/123"><input name="tags" value="seed-tag" /></form>'
                            : '<div data-tag-modal-fields="1" data-apply-url="/admin/tags/apply/images/123"><input name="tags" value="" /></div>'
                    }
                </div>
            </div>
        </div>`;

    beforeEach(() => {
        document.body.innerHTML = "";

        const wrapper = document.createElement("div");
        wrapper.setAttribute("data-controller", "tag-modal");
        wrapper.setAttribute("data-tag-modal-subject-value", "images");
        wrapper.setAttribute("data-tag-modal-subject-id-value", "123");

        const host = document.createElement("div");
        host.className = "tag-modal-host";
        wrapper.appendChild(host);

        const badges = document.createElement("div");
        badges.className = "tag-badges";
        wrapper.appendChild(badges);

        const hidden = document.createElement("div");
        hidden.className = "tag-modal-hidden-inputs";
        wrapper.appendChild(hidden);

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
        document
            .querySelectorAll('meta[name="csrfToken"]')
            .forEach((el) => el.remove());
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("fetches modal and removes it on save (no bootstrap)", async () => {
        // Mock fetch: first call returns modal markup, subsequent call returns JSON payload
        window.fetch = jest.fn(async (url) => {
            if (String(url).includes("/admin/tags/modal/")) {
                return { ok: true, text: async () => modalHtml() };
            }

            return {
                ok: true,
                json: async () => ({
                    tags: [{ name: "x" }],
                    formFields: { tags: "x" },
                }),
            };
        });

        const controller = getController();

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
                return { ok: true, text: async () => modalHtml() };
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

        const controller = getController();

        await controller.open();
        await flush();

        expect(window.bootstrap.Modal.getOrCreateInstance).toHaveBeenCalled();
        expect(instance.show).toHaveBeenCalled();

        await controller.save();
        await flush();

        // Instance.hide dispatches hidden.bs.modal, handler should remove modal
        expect(document.getElementById("tag-modal-images-123")).toBeNull();
    });

    test("ensureModal returns null when host is missing", async () => {
        const controller = getController();
        controller.host = null;
        window.fetch = jest.fn();

        const result = await controller.ensureModal();

        expect(result).toBeNull();
        expect(console.warn).toHaveBeenCalledWith(
            "No host element for tag modal",
        );
        expect(window.fetch).not.toHaveBeenCalled();
    });

    test("ensureModal handles non-ok response and malformed modal html", async () => {
        const controller = getController();

        window.fetch = jest
            .fn()
            .mockResolvedValueOnce({ ok: false, status: 500 })
            .mockResolvedValueOnce({
                ok: true,
                text: async () => '<div class="not-modal"></div>',
            });

        await expect(controller.ensureModal()).resolves.toBeNull();
        await expect(controller.ensureModal()).resolves.toBeNull();

        expect(console.error).toHaveBeenCalledWith(
            "Failed to load tag modal",
            500,
        );
        expect(console.error).toHaveBeenCalledWith(
            "Tag modal markup missing .modal root",
        );
    });

    test("save posts form action, updates badges/hidden fields, and dispatches event", async () => {
        const controller = getController();

        const host = controller.element.querySelector(".tag-modal-host");
        host.innerHTML = modalHtml({ includeForm: true });
        const modalEl = host.querySelector(".modal");
        document.body.appendChild(modalEl);
        controller.modalEl = modalEl;

        const csrf = document.createElement("meta");
        csrf.setAttribute("name", "csrfToken");
        csrf.setAttribute("content", "token-123");
        document.head.appendChild(csrf);

        const payloads = [];
        document.addEventListener("tags:updated", (event) => {
            payloads.push(event.detail);
        });

        window.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({
                tags: [{ slug: "seed-tag", name: "Seed Tag" }],
                formFields: {
                    tags: "seed-tag",
                    person_select: [1, 2],
                    roster_select: 9,
                    site_select: "",
                },
            }),
        });

        await controller.save({ preventDefault: jest.fn(), target: modalEl });
        await flush();

        expect(window.fetch).toHaveBeenCalledWith(
            "/admin/tags/apply/images/123",
            expect.objectContaining({
                method: "POST",
                credentials: "same-origin",
                headers: expect.objectContaining({
                    "X-CSRF-Token": "token-123",
                }),
            }),
        );

        const badges =
            controller.element.querySelector(".tag-badges").textContent;
        expect(badges).toContain("Seed Tag");

        const hiddenPairs = Array.from(
            controller.element.querySelectorAll(
                ".tag-modal-hidden-inputs input",
            ),
        ).map((input) => `${input.name}:${input.value}`);

        expect(hiddenPairs).toContain("person_select[]:1");
        expect(hiddenPairs).toContain("person_select[]:2");
        expect(hiddenPairs).toContain("tags:seed-tag");
        expect(hiddenPairs).toContain("common_tags:seed-tag");
        expect(hiddenPairs).toContain("roster_select:9");
        expect(payloads).toHaveLength(1);
    });

    test("save exits when no form and no fields root", async () => {
        const controller = getController();
        const modal = document.createElement("div");
        modal.id = "tag-modal-images-123";
        modal.className = "modal";
        modal.innerHTML = "<div class='modal-content'>empty</div>";
        document.body.appendChild(modal);
        controller.modalEl = modal;

        window.fetch = jest.fn();
        await controller.save();

        expect(window.fetch).not.toHaveBeenCalled();
    });

    test("show/hide fallback toggles modal classes and removes element", () => {
        const controller = getController();
        const modal = document.createElement("div");
        modal.id = "tag-modal-images-123";
        modal.className = "modal";
        modal.innerHTML = '<button data-action="tag-modal#save">Save</button>';
        document.body.appendChild(modal);

        controller.modalEl = modal;
        controller.saveButton = modal.querySelector("button");

        controller.showModal(modal);
        expect(modal.classList.contains("show")).toBe(true);
        expect(modal.style.display).toBe("block");
        expect(document.body.classList.contains("modal-open")).toBe(true);

        controller.hideModal(modal);
        expect(document.getElementById("tag-modal-images-123")).toBeNull();
        expect(controller.modalEl).toBeNull();
        expect(controller.saveButton).toBeNull();
    });

    test("buildFormDataFromFields includes checked and selected values only", () => {
        const controller = getController();
        const root = document.createElement("div");
        root.innerHTML = `
            <input name="plain" value="alpha" />
            <input type="checkbox" name="cb_yes" value="1" checked />
            <input type="checkbox" name="cb_no" value="0" />
            <input type="radio" name="choice" value="x" checked />
            <input type="radio" name="choice" value="y" />
            <select name="team" multiple>
                <option value="a" selected>A</option>
                <option value="b" selected>B</option>
                <option value="c">C</option>
            </select>
            <textarea name="notes">hello</textarea>
            <input name="skip_disabled" value="disabled" disabled />
        `;

        const entries = Array.from(
            controller.buildFormDataFromFields(root).entries(),
        ).map(([k, v]) => `${k}:${v}`);

        expect(entries).toContain("plain:alpha");
        expect(entries).toContain("cb_yes:1");
        expect(entries).not.toContain("cb_no:0");
        expect(entries).toContain("choice:x");
        expect(entries).toContain("team:a");
        expect(entries).toContain("team:b");
        expect(entries).toContain("notes:hello");
        expect(entries).not.toContain("skip_disabled:disabled");
    });

    test("updateBadges handles missing payload and empty tag list", () => {
        const controller = getController();
        const badges = controller.element.querySelector(".tag-badges");

        controller.updateBadges(null);
        expect(badges.textContent).toBe("");

        controller.updateBadges({ tags: [] });
        expect(badges.textContent).toContain("No tags");
    });

    test("disconnect clears save button reference", async () => {
        window.fetch = jest.fn(async (url) => {
            if (String(url).includes("/admin/tags/modal/")) {
                return { ok: true, text: async () => modalHtml() };
            }

            return {
                ok: true,
                json: async () => ({ tags: [], formFields: {} }),
            };
        });

        const controller = getController();
        await controller.open();
        await flush();

        expect(controller.saveButton).not.toBeNull();
        controller.disconnect();
        expect(controller.saveButton).toBeNull();
        expect(controller.modalEl).toBeNull();
    });
});
