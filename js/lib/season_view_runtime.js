import initSeasonView from "../legacy/modules/season-view-init.mjs";
import { initSeasonStatsTables } from "./season_stats_tables.js";

export function initSeasonViewRoot(root = document) {
    initSeasonView({ root });
    initSeasonStatsTables(root);
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
