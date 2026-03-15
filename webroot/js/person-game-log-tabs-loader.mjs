const doc = globalThis.document;
const El = globalThis.Element;

import initPersonGameLogTabs from "./modules/person-game-log-tabs.mjs";

function getInit() {
    const override = globalThis.__PERSON_GAME_LOG_TABS_INIT__;
    if (typeof override === "function") {
        return override;
    }

    return initPersonGameLogTabs;
}

function boot(event) {
    if (event?.type === "turbo:frame-load") {
        const frame = event.target;
        if (frame instanceof El) {
            getInit()({ root: frame });
            return;
        }
    }

    getInit()({ root: doc });
}
boot();

doc.addEventListener("DOMContentLoaded", boot, { once: true });

doc.addEventListener("turbo:load", boot);
doc.addEventListener("turbo:frame-load", boot);

export function __personGameLogTabsBoot(event) {
    boot(event);
}
