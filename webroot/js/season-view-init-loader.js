import initSeasonView from "./modules/season-view-init.mjs";

function boot(event) {
    if (event?.type === "turbo:frame-load") {
        const frame = event.target;
        if (frame instanceof Element) {
            initSeasonView({ root: frame });
            return;
        }
    }

    initSeasonView({ root: document });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
} else {
    boot();
}

document.addEventListener("turbo:load", boot);
document.addEventListener("turbo:frame-load", boot);
