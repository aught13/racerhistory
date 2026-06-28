const DATATABLES_WAIT_MS = 50;
const DATATABLES_TIMEOUT_MS = 10000; // Increased from 2000ms to 10000ms for Turbo navigation
let loadPromise = null;

function isSearchBuilderAvailable() {
    return typeof window.$?.fn?.dataTable?.SearchBuilder === "function";
}

function hasDataTables() {
    return !!window.$?.fn?.dataTable;
}

export function ensureSearchBuilderLoaded() {
    if (isSearchBuilderAvailable()) {
        return Promise.resolve();
    }

    if (loadPromise) {
        return loadPromise;
    }

    loadPromise = new Promise((resolve, reject) => {
        const startedAt = Date.now();

        const waitForSearchBuilder = () => {
            if (isSearchBuilderAvailable()) {
                resolve();
                return;
            }

            if (Date.now() - startedAt >= DATATABLES_TIMEOUT_MS) {
                loadPromise = null;
                reject(new Error("SearchBuilder not available"));
                return;
            }

            window.setTimeout(waitForSearchBuilder, DATATABLES_WAIT_MS);
        };

        if (!hasDataTables()) {
            loadPromise = null;
            reject(new Error("DataTables not available for SearchBuilder"));
            return;
        }

        waitForSearchBuilder();
    });

    return loadPromise;
}

export function resetSearchBuilderLoader() {
    loadPromise = null;
}

export function resetSearchBuilderLoaderForTests() {
    loadPromise = null;
}

// Reset cached promise on Turbo navigation so SearchBuilder can re-attach
// after morph replaces the DOM.
document.addEventListener("turbo:before-cache", () => {
    loadPromise = null;
});

export const SEARCH_BUILDER_SRC = null;
