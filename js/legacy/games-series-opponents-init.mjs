import {
    cleanupSeriesOpponentsTable,
    initSeriesOpponentsTable,
} from "../lib/series_opponents_runtime.js";

export { initSeriesOpponentsTable, cleanupSeriesOpponentsTable };

document.addEventListener("turbo:before-fetch", cleanupSeriesOpponentsTable);
document.addEventListener("turbo:before-cache", cleanupSeriesOpponentsTable);
document.addEventListener("DOMContentLoaded", initSeriesOpponentsTable);
document.addEventListener("turbo:load", initSeriesOpponentsTable);

if (document.readyState !== "loading") {
    initSeriesOpponentsTable();
}
