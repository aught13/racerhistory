/**
 * Stats page initialization loader (legacy compatibility wrapper).
 *
 * New runtime logic lives in ../lib/stats_page_runtime.js.
 * This file keeps event-driven boot behavior for legacy tests and
 * compatibility imports while exporting the same public API.
 */

import {
    initStatsPage,
    initStatsDataTable,
    fixScrollXHeaderAlignment,
    initDragScroll,
    initCardHover,
    cleanupStatsPage,
    getSearchBuilderStateFromUrl,
    restoreSearchBuilderStateFromUrl,
    copySearchBuilderLinkToClipboard,
    NUMERIC_COLUMNS,
    SCROLLER_THRESHOLD,
} from "../lib/stats_page_runtime.js";

// Clean up before Turbo caches the page
document.addEventListener("turbo:before-cache", cleanupStatsPage);

// Initialize on page load events
document.addEventListener("DOMContentLoaded", initStatsPage);
document.addEventListener("turbo:load", initStatsPage);

// If this module was loaded during Turbo navigation (turbo:load already fired),
// run immediately so the first visit via Turbo still initialises.
if (document.readyState !== "loading") {
    initStatsPage();
}

export {
    initStatsPage,
    initStatsDataTable,
    fixScrollXHeaderAlignment,
    initDragScroll,
    initCardHover,
    cleanupStatsPage,
    getSearchBuilderStateFromUrl,
    restoreSearchBuilderStateFromUrl,
    copySearchBuilderLinkToClipboard,
    NUMERIC_COLUMNS,
    SCROLLER_THRESHOLD,
};
