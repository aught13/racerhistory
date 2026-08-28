/* global bootstrap */

import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["associated", "hiddenForm"];

    connect() {
        this.modalContext = {};
        this.globalShowHandler = (opts) => this.open(opts);

        if (typeof window !== "undefined") {
            window.__rhStimulusShowConfirmDelete = this.globalShowHandler;
        }
    }

    disconnect() {
        if (typeof window !== "undefined") {
            if (
                window.__rhStimulusShowConfirmDelete === this.globalShowHandler
            ) {
                delete window.__rhStimulusShowConfirmDelete;
            }
        }
    }

    onShow(event) {
        const trigger = event?.relatedTarget;
        if (!trigger || !trigger.dataset) {
            return;
        }

        this.setContext({
            deleteUrl: trigger.dataset.deleteUrl,
            itemType: trigger.dataset.itemType,
            associated:
                trigger.dataset.deleteAssociated || trigger.dataset.associated,
            ids: trigger.dataset.ids,
            idsName: trigger.dataset.idsName,
            formId: trigger.dataset.formId,
            bulkAction: trigger.dataset.bulkAction,
        });
    }

    confirmDelete(event) {
        event.preventDefault();

        const source = this.resolveSourceForm();
        const postAction = this.resolvePostAction(source);
        const extraFields = this.buildExtraFields();

        if (source) {
            this.submitSourceForm(source, postAction, extraFields);
        } else {
            this.submitTempForm(postAction, extraFields);
        }
    }

    open(opts) {
        this.setContext(opts || {});

        if (
            typeof bootstrap !== "undefined" &&
            bootstrap.Modal &&
            typeof bootstrap.Modal.getOrCreateInstance === "function"
        ) {
            bootstrap.Modal.getOrCreateInstance(this.element).show();
            return;
        }

        this.element.style.display = "block";
    }

    setContext(opts) {
        this.modalContext = opts || {};
        this.renderAssociated(this.modalContext.associated);
    }

    renderAssociated(associated) {
        if (!this.hasAssociatedTarget) {
            return;
        }

        this.associatedTarget.innerHTML = "";
        const list = this.parseAssociated(associated);
        list.forEach((row) => {
            const li = document.createElement("li");
            li.textContent =
                typeof row === "string"
                    ? row
                    : row?.label || row?.name || JSON.stringify(row);
            this.associatedTarget.appendChild(li);
        });
    }

    parseAssociated(associated) {
        if (!associated) {
            return [];
        }

        if (Array.isArray(associated)) {
            return associated;
        }

        if (typeof associated === "string") {
            try {
                const parsed = JSON.parse(associated);
                return Array.isArray(parsed) ? parsed : [parsed];
            } catch {
                return [associated];
            }
        }

        return [associated];
    }

    resolveSourceForm() {
        if (this.modalContext?.formId) {
            return document.getElementById(this.modalContext.formId);
        }
        // For single-item deletes, submit through a temporary form so the
        // action can change to data-delete-url without inheriting URL-bound
        // form-protection metadata from a pre-rendered page form.
        return null;
    }

    resolvePostAction(source) {
        const deleteUrl = this.modalContext?.deleteUrl || "";
        const usesExplicitSourceForm = Boolean(this.modalContext?.formId);

        if (!usesExplicitSourceForm && deleteUrl) {
            return deleteUrl;
        }

        let postAction = deleteUrl || "#";

        try {
            if (
                source &&
                typeof source.getAttribute === "function" &&
                source.getAttribute("action")
            ) {
                postAction = source.action;
            }
        } catch {
            return deleteUrl || "#";
        }

        return postAction;
    }

    submitSourceForm(source, postAction, extraFields) {
        source
            .querySelectorAll(".injected-delete")
            .forEach((node) => node.remove());
        source.action = postAction;

        extraFields.forEach((field) => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = field.name;
            input.value = field.value;
            input.className = "injected-delete";
            source.appendChild(input);
        });

        if (typeof source.requestSubmit === "function") {
            source.requestSubmit();
            return;
        }

        source.submit();
    }

    submitTempForm(postAction, extraFields) {
        const temp = document.createElement("form");
        temp.style.display = "none";
        temp.method = "post";
        temp.action = postAction;

        if (this.hasHiddenFormTarget) {
            this.hiddenFormTarget
                .querySelectorAll('input[type="hidden"]')
                .forEach((input) => {
                    // Skip Cake form-protection payloads on dynamic temp forms.
                    // The token can be action-bound and cause URL mismatch errors.
                    if (input.name && input.name.startsWith("_Token[")) {
                        return;
                    }
                    const clone = document.createElement("input");
                    clone.type = "hidden";
                    clone.name = input.name;
                    clone.value = input.value || "";
                    temp.appendChild(clone);
                });
        }

        extraFields.forEach((field) => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = field.name;
            input.value = field.value;
            temp.appendChild(input);
        });

        document.body.appendChild(temp);
        if (typeof temp.requestSubmit === "function") {
            temp.requestSubmit();
            return;
        }

        temp.submit();
    }

    buildExtraFields() {
        if (!this.modalContext?.ids || !this.modalContext?.idsName) {
            return this.modalContext?.bulkAction
                ? [{ name: "bulk_action", value: this.modalContext.bulkAction }]
                : [];
        }

        const ids = this.normalizeIds(this.modalContext.ids);
        const extra = ids
            .filter(
                (id) =>
                    id !== null && id !== undefined && String(id).trim() !== "",
            )
            .map((id) => ({
                name: this.modalContext.idsName,
                value: String(id).trim(),
            }));

        if (this.modalContext.bulkAction) {
            extra.push({
                name: "bulk_action",
                value: this.modalContext.bulkAction,
            });
        }

        return extra;
    }

    normalizeIds(ids) {
        if (Array.isArray(ids)) {
            return ids;
        }

        if (typeof ids === "string") {
            const trimmed = ids.trim();
            try {
                const parsed = JSON.parse(ids);
                return Array.isArray(parsed)
                    ? parsed
                    : parsed !== null && parsed !== undefined
                      ? [parsed]
                      : [];
            } catch {
                if (/^\s*[+-]?\d+\s*$/.test(trimmed)) {
                    return [parseInt(trimmed, 10)];
                }
                return [];
            }
        }

        return ids ? [ids] : [];
    }
}
