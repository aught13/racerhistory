import initSeasonView from "../legacy/modules/season-view-init.mjs";

function getInitializer() {
    if (typeof window !== "undefined") {
        const override = window.__SEASON_VIEW_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return initSeasonView;
}

export function initSeasonViewRoot(root = document) {
    getInitializer()({ root });
}

export function bootSeasonView(event) {
    if (event?.type === "turbo:frame-load") {
        const frame = event.target;
        if (frame instanceof globalThis.Element) {
            initSeasonViewRoot(frame);
            return;
        }
    }

    initSeasonViewRoot(globalThis.document);
}
