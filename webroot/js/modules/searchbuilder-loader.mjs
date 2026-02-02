const SEARCH_BUILDER_SRC =
    "https://cdn.datatables.net/searchbuilder/1.4.2/js/dataTables.searchBuilder.min.js";
let loadPromise = null;

function isSearchBuilderAvailable() {
    return typeof window.$?.fn?.dataTable?.SearchBuilder === "function";
}

export function ensureSearchBuilderLoaded() {
    if (isSearchBuilderAvailable()) {
        return Promise.resolve();
    }

    if (loadPromise) {
        return loadPromise;
    }

    const script = document.createElement("script");
    script.src = SEARCH_BUILDER_SRC;
    script.async = true;

    loadPromise = new Promise((resolve, reject) => {
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
            reject(new Error("SearchBuilder script failed to load"));
        }

        script.addEventListener("load", handleLoad);
        script.addEventListener("error", handleError);
        document.head.appendChild(script);
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
