import initPersonGameLogTabs from "../legacy/modules/person-game-log-tabs.mjs";

function getInitializer() {
    if (typeof window !== "undefined") {
        const override = window.__PERSON_GAME_LOG_TABS_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return initPersonGameLogTabs;
}

export function initPersonGameLogTabsRoot(root = document) {
    getInitializer()({ root });
}

export function bootPersonGameLogTabs(event) {
    if (event?.type === "turbo:frame-load") {
        const frame = event.target;
        if (frame instanceof globalThis.Element) {
            initPersonGameLogTabsRoot(frame);
            return;
        }
    }

    initPersonGameLogTabsRoot(globalThis.document);
}
