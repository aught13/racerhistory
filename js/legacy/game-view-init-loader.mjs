import { bootGameView } from "../lib/game_view_runtime.js";

const doc = globalThis.document;

if (doc.readyState === "loading") {
    doc.addEventListener("DOMContentLoaded", bootGameView);
} else {
    bootGameView();
}

doc.addEventListener("turbo:load", bootGameView);
doc.addEventListener("turbo:frame-load", bootGameView);
