import initSeasonView from "../legacy/modules/season-view-init.mjs";

export function initSeasonViewRoot(root = document) {
    initSeasonView({ root });
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
