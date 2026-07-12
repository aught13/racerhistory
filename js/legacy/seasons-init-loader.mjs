/**
 * Seasons page initialization loader (legacy compatibility wrapper).
 *
 * New runtime logic lives in ../lib/seasons_page_runtime.js.
 * This file keeps event-driven boot behavior for legacy tests and
 * compatibility imports while exporting the same public API.
 */

import {
    enhancedBoot,
    boot,
    cleanupSeasonsPage,
    buildOptions,
    inferActiveTable,
    countTableColumns,
    buildStandardColumnLabels,
    buildSplitsColumnLabels,
    getCellDataAttr,
    isDataTablesAvailable,
    ensureDataTablesLoaded,
} from "../lib/seasons_page_runtime.js";

document.addEventListener("turbo:before-cache", cleanupSeasonsPage);

document.addEventListener("DOMContentLoaded", enhancedBoot);
document.addEventListener("turbo:load", enhancedBoot);
document.addEventListener("turbo:frame-load", enhancedBoot);

// If this module was loaded during Turbo navigation (turbo:load already fired),
// run immediately so the first visit via Turbo still initialises.
if (document.readyState !== "loading") {
    enhancedBoot();
}

export {
    enhancedBoot,
    boot,
    cleanupSeasonsPage,
    buildOptions,
    inferActiveTable,
    countTableColumns,
    buildStandardColumnLabels,
    buildSplitsColumnLabels,
    getCellDataAttr,
    isDataTablesAvailable,
    ensureDataTablesLoaded,
};
