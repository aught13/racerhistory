import initBlogInteractions from "./modules/blog-interactions.mjs";

function boot(event) {
    if (event?.type === "turbo:frame-load") {
        const frame = event.target;
        if (frame instanceof Element) {
            initBlogInteractions({ root: frame });
            return;
        }
    }

    initBlogInteractions({ root: document });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
} else {
    boot();
}

document.addEventListener("turbo:load", boot);
document.addEventListener("turbo:frame-load", boot);
