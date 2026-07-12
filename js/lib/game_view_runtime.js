import initGameView from "../legacy/modules/game-view-init.mjs";

function getInitializer() {
    if (typeof window !== "undefined") {
        const override = window.__GAME_VIEW_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return initGameView;
}

export function initGameViewRoot(root = document) {
    getInitializer()({ root });
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
