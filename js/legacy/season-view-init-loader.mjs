import { bootSeasonView as boot } from "../lib/season_view_runtime.js";

const doc = globalThis.document;

if (doc.readyState === "loading") {
    doc.addEventListener("DOMContentLoaded", boot);
} else {
    boot();
}

doc.addEventListener("turbo:load", boot);
doc.addEventListener("turbo:frame-load", boot);
