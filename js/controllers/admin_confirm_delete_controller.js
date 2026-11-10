import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["associated", "hiddenForm", "openTrigger"];

    connect() {
        this.modalContext = {};
    }

    onShow(event) {
        const trigger = event?.relatedTarget;
        if (
            !trigger ||
            trigger === this.openTriggerTarget ||
            !trigger.dataset
        ) {
            return;
        }

        this.setContext({
            deleteUrl: trigger.dataset.deleteUrl,
            itemType: trigger.dataset.itemType,
            associated:
                trigger.dataset.deleteAssociated || trigger.dataset.associated,
            ids: trigger.dataset.ids,
            idsName: trigger.dataset.idsName,
            sourceForm: trigger.form || null,
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

    open(event) {
        this.setContext(event?.detail || {});
        if (this.hasOpenTriggerTarget) {
            this.openTriggerTarget.click();
        }
    }

    setContext(opts) {
        this.modalContext = opts || {};
        this.renderAssociated(this.modalContext.associated);
    }

    renderAssociated(associated) {
        if (!this.hasAssociatedTarget) {
            return;
        }

        this.associatedTarget.replaceChildren();
        const list = this.parseAssociated(associated);
        list.forEach((row) => {
            const li = this.associatedTarget.ownerDocument.createElement("li");
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
        return this.modalContext?.sourceForm || null;
    }

    resolvePostAction(source) {
        const deleteUrl = this.modalContext?.deleteUrl || "";
        const usesExplicitSourceForm = Boolean(source);

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
        const document = source.ownerDocument;
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
        const document = this.element.ownerDocument;
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
