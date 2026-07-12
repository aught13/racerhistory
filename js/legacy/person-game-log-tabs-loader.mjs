import { bootPersonGameLogTabs as boot } from "../lib/person_game_log_tabs_runtime.js";

const doc = globalThis.document;
boot();

doc.addEventListener("DOMContentLoaded", boot, { once: true });

doc.addEventListener("turbo:load", boot);
doc.addEventListener("turbo:frame-load", boot);

export function __personGameLogTabsBoot(event) {
    boot(event);
}
