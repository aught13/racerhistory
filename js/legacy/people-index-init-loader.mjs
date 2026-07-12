import {
    cleanupPeopleIndexPage,
    initPeopleIndexPage,
} from "../lib/people_index_runtime.js";

function boot() {
    void initPeopleIndexPage();
}

function cleanupPeoplePage() {
    cleanupPeopleIndexPage();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
} else {
    boot();
}

document.addEventListener("turbo:before-cache", cleanupPeoplePage);
document.addEventListener("turbo:load", boot);

export { boot, cleanupPeoplePage };
