export function initTurboScrollBehavior() {
    document.addEventListener("turbo:frame-load", (event) => {
        const frame = event.target;

        if (!frame || typeof frame !== "object") {
            return;
        }

        if (frame.id !== "blog") {
            return;
        }

        window.scrollTo({ top: 0, left: 0, behavior: "auto" });
    });
}
