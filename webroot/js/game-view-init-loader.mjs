/* global Element */

import initGameView from "./modules/game-view-init.mjs";

function boot(event) {
    if (event?.type === "turbo:frame-load") {
        const frame = event.target;
        if (frame instanceof Element) {
            initGameView({ root: frame });
            return;
        }
    }

    initGameView({ root: document });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
} else {
    boot();
}

document.addEventListener("turbo:load", boot);
document.addEventListener("turbo:frame-load", boot);
