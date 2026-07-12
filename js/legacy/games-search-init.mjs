/**
 * Games search page initialization loader (legacy compatibility wrapper).
 *
 * New runtime logic lives in ../lib/games_search_runtime.js.
 * This file keeps event-driven boot behavior for legacy tests and
 * compatibility imports while exporting the same public API.
 */

import {
    initGamesPage,
    initGamesDataTable,
    initDragScroll,
    initCardHover,
    cleanupGamesPage,
    calculateRecord,
    updateRecordDisplay,
    getSearchBuilderStateFromUrl,
    restoreSearchBuilderStateFromUrl,
    copySearchBuilderLinkToClipboard,
    resetGamesSearchRuntimeState,
    NUMERIC_COLUMNS,
    SCROLLER_THRESHOLD,
} from "../lib/games_search_runtime.js";

function cleanupAndReset() {
    cleanupGamesPage();
    resetGamesSearchRuntimeState();
}

// Clear initialization tracking when navigating away
document.addEventListener("turbo:before-fetch", cleanupAndReset);
document.addEventListener("turbo:before-cache", cleanupAndReset);

// Initialize on DOMContentLoaded and turbo:load
document.addEventListener("DOMContentLoaded", initGamesPage);
document.addEventListener("turbo:load", initGamesPage);

// If this module was loaded during Turbo navigation (turbo:load already fired),
// run immediately so the first visit via Turbo still initialises.
if (document.readyState !== "loading") {
    initGamesPage();
}

export {
    initGamesPage,
    initGamesDataTable,
    initDragScroll,
    initCardHover,
    cleanupGamesPage,
    calculateRecord,
    updateRecordDisplay,
    getSearchBuilderStateFromUrl,
    restoreSearchBuilderStateFromUrl,
    copySearchBuilderLinkToClipboard,
    NUMERIC_COLUMNS,
    SCROLLER_THRESHOLD,
};
