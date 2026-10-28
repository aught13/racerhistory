/* eslint-disable security/detect-object-injection */
import { Controller } from "@hotwired/stimulus";

import TagModalAdapter from "../lib/tag_modal_adapter.js";

export default class extends Controller {
    static targets = ["host", "badges", "hiddenInputs"];

    static values = {
        subject: String,
        subjectId: Number,
        csrfToken: String,
    };

    connect() {
        this.isActive = true;
        this.isCaching = false;
        this.modalEl = null;
        this.saveButton = null;
        this.handleSaveClick = (event) => this.save(event);
        this.modalAdapter = new TagModalAdapter();
        this.modalRequest = null;
        this.saveRequest = null;
        this.boundBeforeCache = () => this.beforeCache();
        this.element.ownerDocument.addEventListener(
            "turbo:before-cache",
            this.boundBeforeCache,
        );
    }

    disconnect() {
        this.isActive = false;
        this.element.ownerDocument.removeEventListener(
            "turbo:before-cache",
            this.boundBeforeCache,
        );
        this.prepareForNavigation();
    }

    beforeCache() {
        this.prepareForNavigation();
    }

    prepareForNavigation() {
        this.isCaching = true;
        this.modalRequest?.abort();
        this.modalRequest = null;
        this.saveRequest?.abort();
        this.saveRequest = null;
        this.disposeDynamicModal();
    }

    get subject() {
        return (
            this.subjectValue ||
            this.element.dataset.tagModalSubject ||
            "generic"
        );
    }

    get subjectId() {
        return (
            this.subjectIdValue || this.element.dataset.tagModalSubjectId || "0"
        );
    }

    get modalId() {
        return `tag-modal-${this.subject}-${this.subjectId}`;
    }

    get applyActionUrl() {
        return `/admin/tags/apply/${encodeURIComponent(this.subject)}/${encodeURIComponent(this.subjectId)}`;
    }

    async open(event) {
        event?.preventDefault();
        const modalEl = await this.ensureModal();
        if (!modalEl) {
            return;
        }

        this.showModal(modalEl);
    }

    async ensureModal() {
        if (!this.isActive || this.isCaching) {
            return null;
        }

        let modalEl =
            this.modalEl ||
            (this.hasHostTarget
                ? this.hostTarget.querySelector(".modal")
                : null);

        if (!modalEl) {
            if (!this.hasHostTarget) {
                console.warn("No host element for tag modal");

                return null;
            }

            const url = `/admin/tags/modal/${encodeURIComponent(this.subject)}/${encodeURIComponent(this.subjectId)}`;
            this.modalRequest?.abort();
            const request = new AbortController();
            this.modalRequest = request;
            let html;
            try {
                const resp = await fetch(url, {
                    credentials: "same-origin",
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                    signal: request.signal,
                });
                if (
                    !this.isActive ||
                    this.isCaching ||
                    this.modalRequest !== request
                ) {
                    return null;
                }
                if (!resp.ok) {
                    console.error("Failed to load tag modal", resp.status);

                    return null;
                }
                html = await resp.text();
                if (
                    !this.isActive ||
                    this.isCaching ||
                    this.modalRequest !== request
                ) {
                    return null;
                }
            } catch (error) {
                if (error?.name === "AbortError") {
                    return null;
                }
                throw error;
            } finally {
                if (this.modalRequest === request) {
                    this.modalRequest = null;
                }
            }

            this.hostTarget.innerHTML = html;
            modalEl = this.hostTarget.querySelector(".modal");
            if (!modalEl) {
                console.error("Tag modal markup missing .modal root");

                return null;
            }
        }

        if (modalEl.parentElement !== this.element.ownerDocument.body) {
            this.element.ownerDocument.body.appendChild(modalEl);
        }

        this.modalEl = modalEl;
        this.bindSaveButton();

        return modalEl;
    }

    bindSaveButton() {
        if (!this.modalEl) {
            return;
        }

        const saveButton = this.modalEl.querySelector(
            'button[data-action*="tag-modal#save"]',
        );
        if (!saveButton) {
            return;
        }

        if (this.saveButton && this.saveButton !== saveButton) {
            this.saveButton.removeEventListener("click", this.handleSaveClick);
        }

        this.saveButton = saveButton;
        this.saveButton.removeEventListener("click", this.handleSaveClick);
        this.saveButton.addEventListener("click", this.handleSaveClick);
    }

    async save(event) {
        event?.preventDefault();

        if (!this.isActive || this.isCaching || this.saveRequest) {
            return;
        }

        const modalEl = this.modalEl || event?.target?.closest?.(".modal");
        if (!modalEl) {
            return;
        }

        const form = modalEl.querySelector("form");
        const fieldsRoot = modalEl.querySelector("[data-tag-modal-fields]");

        let action = this.applyActionUrl;
        let formData = null;

        if (form) {
            action = form.getAttribute("action") || this.applyActionUrl;
            formData = new FormData(form);
        } else if (fieldsRoot) {
            action =
                fieldsRoot.getAttribute("data-apply-url") ||
                this.applyActionUrl;
            formData = this.buildFormDataFromFields(fieldsRoot);
        }

        if (!formData) {
            return;
        }

        const request = new AbortController();
        this.saveRequest = request;

        try {
            const headers = {};
            if (this.hasCsrfTokenValue) {
                headers["X-CSRF-Token"] = this.csrfTokenValue;
            }

            const resp = await fetch(action, {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                headers,
                signal: request.signal,
            });
            if (
                !this.isActive ||
                this.isCaching ||
                this.saveRequest !== request
            ) {
                return;
            }
            if (!resp.ok) {
                const responseBody = await resp.text();
                if (
                    !this.isActive ||
                    this.isCaching ||
                    this.saveRequest !== request
                ) {
                    return;
                }
                console.error(
                    "Failed to apply tags",
                    resp.status,
                    responseBody,
                );

                const previousError = modalEl.querySelector(
                    "[data-tag-modal-error]",
                );
                previousError?.remove();

                const errorNotice =
                    this.element.ownerDocument.createElement("div");
                errorNotice.className = "alert alert-danger";
                errorNotice.setAttribute("role", "alert");
                errorNotice.dataset.tagModalError = "";
                errorNotice.textContent = `Unable to save tags (HTTP ${resp.status}). Your changes are still in the form. Check the browser console for server details.`;
                const errorContainer =
                    modalEl.querySelector(".modal-body") ||
                    modalEl.querySelector(".modal-content");
                errorContainer?.prepend(errorNotice);

                return;
            }

            const payload = await resp.json();
            if (
                !this.isActive ||
                this.isCaching ||
                this.saveRequest !== request
            ) {
                return;
            }
            this.updateBadges(payload);
            this.updateHiddenInputs(payload);

            this.hideModal(modalEl);

            const evt = new window.CustomEvent("tags:updated", {
                detail: payload,
            });
            this.element.ownerDocument.dispatchEvent(evt);
        } catch (e) {
            if (
                e?.name !== "AbortError" &&
                this.isActive &&
                !this.isCaching &&
                this.saveRequest === request
            ) {
                console.error("Error applying tags", e);
            }
        } finally {
            if (this.saveRequest === request) {
                this.saveRequest = null;
            }
        }
    }

    showModal(modalEl) {
        this.modalAdapter.show(modalEl);
    }

    hideModal(modalEl) {
        this.modalAdapter.hide(modalEl, () =>
            this.clearModalReference(modalEl),
        );
    }

    disposeDynamicModal() {
        if (this.saveButton) {
            this.saveButton.removeEventListener("click", this.handleSaveClick);
            this.saveButton = null;
        }
        if (this.modalEl) {
            this.modalAdapter.dispose(this.modalEl);
            this.modalEl = null;
        }
        this.hasHostTarget && this.hostTarget.replaceChildren();
    }

    clearModalReference(modalEl) {
        if (this.saveButton) {
            this.saveButton.removeEventListener("click", this.handleSaveClick);
            this.saveButton = null;
        }
        if (this.modalEl === modalEl) {
            this.modalEl = null;
        }
    }

    buildFormDataFromFields(root) {
        const formData = new FormData();
        const fields = root.querySelectorAll(
            "input[name], select[name], textarea[name]",
        );

        fields.forEach((field) => {
            if (field.disabled || !field.name) {
                return;
            }

            const tag = field.tagName.toLowerCase();
            if (tag === "input") {
                const type = (
                    field.getAttribute("type") || "text"
                ).toLowerCase();
                if (
                    (type === "checkbox" || type === "radio") &&
                    !field.checked
                ) {
                    return;
                }
                formData.append(field.name, field.value);

                return;
            }

            if (tag === "select" && field.multiple) {
                Array.from(field.selectedOptions).forEach((option) => {
                    formData.append(field.name, option.value);
                });

                return;
            }

            formData.append(field.name, field.value);
        });

        return formData;
    }

    updateBadges(payload) {
        if (!this.hasBadgesTarget || !payload || !Array.isArray(payload.tags)) {
            return;
        }

        const badgesEl = this.badgesTarget;
        badgesEl.replaceChildren();
        if (payload.tags.length === 0) {
            const empty = badgesEl.ownerDocument.createElement("span");
            empty.className = "text-muted small";
            empty.textContent = "No tags";
            badgesEl.appendChild(empty);

            return;
        }

        payload.tags.forEach((tag) => {
            const span = badgesEl.ownerDocument.createElement("span");
            span.className = "badge bg-secondary me-1 mb-1";
            span.textContent = tag.name || tag.slug || "";
            badgesEl.appendChild(span);
        });
    }

    updateHiddenInputs(payload) {
        if (!this.hasHiddenInputsTarget) {
            return;
        }

        const hiddenContainer = this.hiddenInputsTarget;
        hiddenContainer.replaceChildren();
        const formFields = (payload && payload.formFields) || {};
        const pushHidden = (name, value) => {
            if (value === null || value === undefined || value === "") {
                return;
            }

            if (Array.isArray(value)) {
                value.forEach((item) => {
                    const input =
                        hiddenContainer.ownerDocument.createElement("input");
                    input.type = "hidden";
                    input.name = name.endsWith("[]") ? name : `${name}[]`;
                    input.value = String(item);
                    hiddenContainer.appendChild(input);
                });

                return;
            }

            if (name === "tags") {
                const tagInput =
                    hiddenContainer.ownerDocument.createElement("input");
                tagInput.type = "hidden";
                tagInput.name = "tags";
                tagInput.value = String(value);
                hiddenContainer.appendChild(tagInput);

                const commonTagInput =
                    hiddenContainer.ownerDocument.createElement("input");
                commonTagInput.type = "hidden";
                commonTagInput.name = "common_tags";
                commonTagInput.value = String(value);
                hiddenContainer.appendChild(commonTagInput);

                return;
            }

            const input = hiddenContainer.ownerDocument.createElement("input");
            input.type = "hidden";
            input.name = name;
            input.value = String(value);
            hiddenContainer.appendChild(input);
        };

        const expectedKeys = [
            "person_select",
            "team_select",
            "teamseason_select",
            "game_select",
            "site_select",
            "opponent_select",
            "sport_select",
            "roster_select",
            "tags",
        ];

        expectedKeys.forEach((key) => {
            if (Object.prototype.hasOwnProperty.call(formFields, key)) {
                pushHidden(key, formFields[key]);
            }
        });
    }
}
