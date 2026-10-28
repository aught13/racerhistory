export default class TagModalAdapter {
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
            let cleanedUp = false;
            const cleanup = () => {
                if (cleanedUp) {
                    return;
                }
                cleanedUp = true;
                modal.removeEventListener("hidden.bs.modal", cleanup);
                instance.dispose?.();
                modal.remove();
                onHidden?.();
            };

            modal.addEventListener("hidden.bs.modal", cleanup);
            instance.hide();
            if (!modal.classList.contains("show")) {
                cleanup();
            }

            return;
        }

        this.remove(modal);
        onHidden?.();
    }

    dispose(modal) {
        const Modal = window.bootstrap?.Modal;
        const instance = Modal?.getInstance(modal);
        if (instance) {
            instance.hide();
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
