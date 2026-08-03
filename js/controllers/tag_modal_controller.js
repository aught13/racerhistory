/* eslint-disable security/detect-object-injection */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        subject: String,
        subjectId: Number,
    };

    connect() {
        this.host = this.element.querySelector(".tag-modal-host");
        this.modalEl = null;
        this.saveButton = null;
        this.handleSaveClick = (event) => this.save(event);
    }

    disconnect() {
        if (this.saveButton) {
            this.saveButton.removeEventListener("click", this.handleSaveClick);
            this.saveButton = null;
        }
        this.modalEl = null;
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
        let modalEl = document.getElementById(this.modalId);

        if (!modalEl) {
            if (!this.host) {
                console.warn("No host element for tag modal");

                return null;
            }

            const url = `/admin/tags/modal/${encodeURIComponent(this.subject)}/${encodeURIComponent(this.subjectId)}`;
            const resp = await fetch(url, {
                credentials: "same-origin",
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (!resp.ok) {
                console.error("Failed to load tag modal", resp.status);

                return null;
            }

            this.host.innerHTML = await resp.text();
            modalEl = this.host.querySelector(".modal");
            if (!modalEl) {
                console.error("Tag modal markup missing .modal root");

                return null;
            }
        }

        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
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

        const modalEl =
            this.modalEl ||
            event?.target?.closest?.(".modal") ||
            document.getElementById(this.modalId);
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

        try {
            const csrfMeta = document.querySelector('meta[name="csrfToken"]');
            const headers = {};
            if (csrfMeta) {
                headers["X-CSRF-Token"] =
                    csrfMeta.getAttribute("content") || "";
            }

            const resp = await fetch(action, {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                headers,
            });
            if (!resp.ok) {
                console.error("Failed to apply tags", resp.status);

                return;
            }

            const payload = await resp.json();
            this.updateBadges(payload);
            this.updateHiddenInputs(payload);

            this.hideModal(modalEl);

            const evt = new window.CustomEvent("tags:updated", {
                detail: payload,
            });
            document.dispatchEvent(evt);
        } catch (e) {
            console.error("Error applying tags", e);
        }
    }

    showModal(modalEl) {
        const bootstrapModal = window.bootstrap?.Modal;
        if (bootstrapModal) {
            bootstrapModal.getOrCreateInstance(modalEl).show();

            return;
        }

        modalEl.classList.add("show");
        modalEl.style.display = "block";
        modalEl.removeAttribute("aria-hidden");
        modalEl.setAttribute("aria-modal", "true");
        document.body.classList.add("modal-open");
    }

    hideModal(modalEl) {
        const bootstrapModal = window.bootstrap?.Modal;
        if (bootstrapModal) {
            const instance = bootstrapModal.getInstance(modalEl);
            if (instance) {
                // Wait for Bootstrap's hidden event, then remove the modal
                // from the DOM so it will be re-fetched and re-initialized
                // the next time it is opened.
                const onHidden = () => {
                    modalEl.removeEventListener("hidden.bs.modal", onHidden);
                    try {
                        if (this.saveButton) {
                            this.saveButton.removeEventListener(
                                "click",
                                this.handleSaveClick,
                            );
                        }
                    } catch {
                        // ignore
                    }
                    if (modalEl.parentElement) {
                        modalEl.parentElement.removeChild(modalEl);
                    }
                    if (this.modalEl === modalEl) {
                        this.modalEl = null;
                    }
                    this.saveButton = null;
                };

                modalEl.addEventListener("hidden.bs.modal", onHidden);
                instance.hide();

                return;
            }
        }

        // Non-Bootstrap fallback: hide and remove immediately so next open
        // will fetch a fresh modal markup and re-run Stimulus connect.
        modalEl.classList.remove("show");
        modalEl.style.display = "none";
        modalEl.setAttribute("aria-hidden", "true");
        modalEl.removeAttribute("aria-modal");
        document.body.classList.remove("modal-open");

        try {
            if (this.saveButton) {
                this.saveButton.removeEventListener(
                    "click",
                    this.handleSaveClick,
                );
            }
        } catch {
            // ignore
        }

        if (modalEl.parentElement) {
            modalEl.parentElement.removeChild(modalEl);
        }
        if (this.modalEl === modalEl) {
            this.modalEl = null;
        }
        this.saveButton = null;
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
        const badgesEl = this.element.querySelector(".tag-badges");
        if (!badgesEl || !payload || !Array.isArray(payload.tags)) {
            return;
        }

        badgesEl.innerHTML = "";
        if (payload.tags.length === 0) {
            badgesEl.innerHTML =
                '<span class="text-muted small">No tags</span>';

            return;
        }

        payload.tags.forEach((tag) => {
            const span = document.createElement("span");
            span.className = "badge bg-secondary me-1 mb-1";
            span.textContent = tag.name || tag.slug || "";
            badgesEl.appendChild(span);
        });
    }

    updateHiddenInputs(payload) {
        const hiddenContainer = this.element.querySelector(
            ".tag-modal-hidden-inputs",
        );
        if (!hiddenContainer) {
            return;
        }

        hiddenContainer.innerHTML = "";
        const formFields = (payload && payload.formFields) || {};
        const pushHidden = (name, value) => {
            if (value === null || value === undefined || value === "") {
                return;
            }

            if (Array.isArray(value)) {
                value.forEach((item) => {
                    const input = document.createElement("input");
                    input.type = "hidden";
                    input.name = name.endsWith("[]") ? name : `${name}[]`;
                    input.value = String(item);
                    hiddenContainer.appendChild(input);
                });

                return;
            }

            if (name === "tags") {
                const tagInput = document.createElement("input");
                tagInput.type = "hidden";
                tagInput.name = "tags";
                tagInput.value = String(value);
                hiddenContainer.appendChild(tagInput);

                const commonTagInput = document.createElement("input");
                commonTagInput.type = "hidden";
                commonTagInput.name = "common_tags";
                commonTagInput.value = String(value);
                hiddenContainer.appendChild(commonTagInput);

                return;
            }

            const input = document.createElement("input");
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
