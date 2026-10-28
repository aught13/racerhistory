import initGameView from "../legacy/modules/game-view-init.mjs";

export function initGameViewRoot(root = document) {
    initGameView({ root });
}

export function bootGameView(event) {
    if (event?.type === "turbo:frame-load") {
        const frame = event.target;
        if (frame instanceof globalThis.Element) {
            initGameViewRoot(frame);
            return;
        }
    }

    initGameViewRoot(globalThis.document);
}
