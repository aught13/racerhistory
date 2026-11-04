export default class TagModalAdapter {
    constructor() {
        this.pendingHides = new WeakMap();
    }

    show(modal) {
        const Modal = window.bootstrap?.Modal;
        if (Modal) {
            Modal.getOrCreateInstance(modal).show();

            return;
        }

        modal.classList.add("show");
        modal.style.display = "block";
        modal.removeAttribute("aria-hidden");
        modal.setAttribute("aria-modal", "true");
        modal.ownerDocument.body.classList.add("modal-open");
    }

    hide(modal, onHidden) {
        const Modal = window.bootstrap?.Modal;
        const instance = Modal?.getInstance(modal);
        if (instance) {
            if (this.pendingHides.has(modal)) {
                return;
            }

            let cleanedUp = false;
            const cleanup = () => {
                if (cleanedUp) {
                    return;
                }
                cleanedUp = true;
                modal.removeEventListener("hidden.bs.modal", cleanup);
                this.pendingHides.delete(modal);
                instance.dispose?.();
                modal.remove();
                onHidden?.();
            };

            const wasShown = modal.classList.contains("show");
            modal.addEventListener("hidden.bs.modal", cleanup);
            this.pendingHides.set(modal, cleanup);
            instance.hide();
            if (!wasShown) {
                cleanup();
            } else if (modal.classList.contains("show")) {
                modal.removeEventListener("hidden.bs.modal", cleanup);
                this.pendingHides.delete(modal);
            }

            return;
        }

        this.remove(modal);
        onHidden?.();
    }

    dispose(modal) {
        const Modal = window.bootstrap?.Modal;
        const instance = Modal?.getInstance(modal);
        if (this.pendingHides.has(modal)) {
            this.remove(modal);

            return;
        }

        if (instance) {
            if (modal.classList.contains("show")) {
                this.hide(modal);
                this.remove(modal);

                return;
            }

            instance.dispose?.();
        }

        this.remove(modal);
    }

    remove(modal) {
        const document = modal.ownerDocument;
        modal.classList.remove("show");
        modal.style.display = "none";
        modal.setAttribute("aria-hidden", "true");
        modal.removeAttribute("aria-modal");
        modal.remove();

        if (!document.querySelector(".modal.show")) {
            document.body.classList.remove("modal-open");
            document.body.style.removeProperty("overflow");
            document.body.style.removeProperty("padding-right");
            document
                .querySelectorAll(".modal-backdrop")
                .forEach((backdrop) => backdrop.remove());
        }
    }
}
