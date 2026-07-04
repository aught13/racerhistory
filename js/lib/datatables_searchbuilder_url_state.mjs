/**
 * DataTables SearchBuilder URL State Extension
 * Automatically adds URL-based state persistence to all SearchBuilder instances
 */

/**
 * Get SearchBuilder state from URL parameter
 * @returns {object|null} Parsed state object or null
 */
function getSearchBuilderStateFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const stateStr = params.get("searchBuilder");
    if (!stateStr) {
        return null;
    }
    try {
        return JSON.parse(decodeURIComponent(stateStr));
    } catch (err) {
        console.warn("Failed to parse searchBuilder URL state:", err);
        return null;
    }
}

/**
 * Update URL with current SearchBuilder state
 * @param {object} dt DataTables instance
 */
function updateUrlWithSearchBuilderState(dt) {
    if (!dt || !dt.searchBuilder) {
        return;
    }

    try {
        const sbInstance =
            dt.context && dt.context[0] && dt.context[0]._searchBuilder;
        if (!sbInstance || !sbInstance.s || !sbInstance.s.topGroup) {
            return;
        }

        const state = dt.searchBuilder.getDetails();
        const url = new URL(window.location);

        if (state && state.criteria && state.criteria.length > 0) {
            url.searchParams.set(
                "searchBuilder",
                encodeURIComponent(JSON.stringify(state)),
            );
        } else {
            url.searchParams.delete("searchBuilder");
        }

        window.history.replaceState({}, "", url);
    } catch (err) {
        console.warn("Failed to update URL with SearchBuilder state:", err);
    }
}

/**
 * Restore SearchBuilder state from URL
 * @param {object} dt DataTables instance
 */
async function restoreSearchBuilderStateFromUrl(dt) {
    if (!dt || !dt.searchBuilder) {
        return;
    }

    const state = getSearchBuilderStateFromUrl();
    if (!state || !state.criteria || !Array.isArray(state.criteria)) {
        return;
    }

    try {
        const sbInstance =
            dt.context && dt.context[0] && dt.context[0]._searchBuilder;
        if (!sbInstance || !sbInstance.s || !sbInstance.s.topGroup) {
            // Retry after a short delay if topGroup not ready
            setTimeout(() => restoreSearchBuilderStateFromUrl(dt), 100);
            return;
        }

        // Clear existing criteria
        const container = dt.searchBuilder.container();
        if (container) {
            container.empty();
        }

        // Rebuild with state
        dt.searchBuilder.rebuild(state);
        console.log("SearchBuilder state restored from URL:", state);

        await new Promise((resolve) => setTimeout(resolve, 0));
    } catch (err) {
        console.warn("Failed to restore SearchBuilder state from URL:", err);
    }
}

/**
 * Initialize URL state management for a DataTables instance with SearchBuilder
 * @param {object} settings DataTables settings object
 */
function initSearchBuilderUrlState(settings) {
    const dt = new window.$.fn.dataTable.Api(settings);

    // Check if this table has SearchBuilder
    const hasSearchBuilder =
        dt.context && dt.context[0] && dt.context[0]._searchBuilder;
    if (!hasSearchBuilder) {
        return;
    }

    // Wait for SearchBuilder to be fully initialized
    setTimeout(() => {
        if (!dt.searchBuilder) {
            return;
        }

        // Restore state from URL on init
        restoreSearchBuilderStateFromUrl(dt);

        // Update URL whenever SearchBuilder state changes
        dt.on("draw.dtSearchBuilderUrl", () => {
            updateUrlWithSearchBuilderState(dt);
        });

        // Also listen for SearchBuilder-specific rebuild events
        const sbInstance =
            dt.context && dt.context[0] && dt.context[0]._searchBuilder;
        if (sbInstance && sbInstance.dom && sbInstance.dom.container) {
            window
                .$(sbInstance.dom.container)
                .on("click.dtSearchBuilderUrl", "button", () => {
                    setTimeout(() => updateUrlWithSearchBuilderState(dt), 100);
                });
        }
    }, 500);
}

/**
 * Register the extension with DataTables
 */
export function registerSearchBuilderUrlStateExtension() {
    if (
        typeof window === "undefined" ||
        !window.$ ||
        !window.$.fn ||
        !window.$.fn.dataTable
    ) {
        console.warn(
            "DataTables not available for SearchBuilder URL state extension",
        );
        return;
    }

    // Hook into DataTables initialization using init.dt event (fires after full init)
    window.$(document).on("init.dt", (e, settings) => {
        initSearchBuilderUrlState(settings);
    });

    console.log("SearchBuilder URL state extension registered");
}

/**
 * Copy current SearchBuilder state to clipboard
 * @param {object} dt DataTables instance
 * @returns {Promise<string|null>} URL string or null
 */
export async function copySearchBuilderLinkToClipboard(dt) {
    if (!dt || !dt.searchBuilder) {
        return null;
    }

    try {
        const sbInstance =
            dt.context && dt.context[0] && dt.context[0]._searchBuilder;
        if (!sbInstance || !sbInstance.s || !sbInstance.s.topGroup) {
            console.warn("SearchBuilder not fully initialized");
            return null;
        }

        const state = dt.searchBuilder.getDetails();
        const url = new URL(window.location);
        url.searchParams.set(
            "searchBuilder",
            encodeURIComponent(JSON.stringify(state)),
        );

        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(url.toString());
            console.log("Filter link copied to clipboard");
        }

        return url.toString();
    } catch (err) {
        console.error("Failed to copy SearchBuilder link:", err);
        return null;
    }
}
