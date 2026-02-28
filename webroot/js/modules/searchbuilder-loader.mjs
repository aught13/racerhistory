const SEARCH_BUILDER_SRC =
    "https://cdn.datatables.net/searchbuilder/1.4.2/js/dataTables.searchBuilder.min.js";
const DATATABLES_WAIT_MS = 50;
const DATATABLES_TIMEOUT_MS = 2000;
let loadPromise = null;

function isSearchBuilderAvailable() {
    return typeof window.$?.fn?.dataTable?.SearchBuilder === "function";
}

function hasDataTables() {
    return typeof window.$?.fn?.dataTable === "object";
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

        const waitForDataTables = () => {
            if (hasDataTables()) {
                injectScript();
                return;
            }

            if (Date.now() - startedAt >= DATATABLES_TIMEOUT_MS) {
                loadPromise = null;
                reject(new Error("DataTables not available for SearchBuilder"));
                return;
            }

            window.setTimeout(waitForDataTables, DATATABLES_WAIT_MS);
        };

        const injectScript = () => {
            const script = document.createElement("script");
            script.src = SEARCH_BUILDER_SRC;
            script.async = true;

            const cleanup = () => {
                script.removeEventListener("load", handleLoad);
                script.removeEventListener("error", handleError);
            };

            function handleLoad() {
                cleanup();
                if (isSearchBuilderAvailable()) {
                    resolve();
                } else {
                    reject(
                        new Error(
                            "SearchBuilder script loaded but constructor missing",
                        ),
                    );
                }
            }

            function handleError() {
                cleanup();
                loadPromise = null;
                reject(new Error("SearchBuilder script failed to load"));
            }

            script.addEventListener("load", handleLoad);
            script.addEventListener("error", handleError);
            document.head.appendChild(script);
        };

        waitForDataTables();
    });

    return loadPromise;
}

export function resetSearchBuilderLoaderForTests() {
    loadPromise = null;
    const scripts = Array.from(
        document.head.querySelectorAll(`script[src="${SEARCH_BUILDER_SRC}"]`),
    );
    scripts.forEach((el) => el.remove());
}

export { SEARCH_BUILDER_SRC };
