export function initTurboScrollBehavior() {
    // When a frame navigation swaps content, the browser keeps the current scroll
    // position (because this is not a full page navigation). For content pages
    // like Blog, that can land the user halfway down the newly rendered post.
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
