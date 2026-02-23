const doc = globalThis.document;
const El = globalThis.Element;

import initGameView from "./modules/game-view-init.mjs";

function boot(event) {
    if (event?.type === "turbo:frame-load") {
        const frame = event.target;
        if (frame instanceof El) {
            initGameView({ root: frame });
            return;
        }
    }

    initGameView({ root: doc });
}

if (doc.readyState === "loading") {
    doc.addEventListener("DOMContentLoaded", boot);
} else {
    boot();
}

doc.addEventListener("turbo:load", boot);
doc.addEventListener("turbo:frame-load", boot);
