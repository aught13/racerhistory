import initSeasonView from "./modules/season-view-init.mjs";

function boot() {
    initSeasonView();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
} else {
    boot();
}

document.addEventListener("turbo:load", boot);
